<?php

namespace App\Webhook;

use LINE\Constants\HTTPHeader;
use LINE\Parser\EventRequestParser;
use LINE\Parser\Exception\InvalidEventRequestException;
use LINE\Parser\Exception\InvalidSignatureException;
use Symfony\Component\HttpFoundation\ChainRequestMatcher;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\MethodRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Webhook\Client\AbstractRequestParser;
use Symfony\Component\Webhook\Exception\RejectWebhookException;

final class LineRequestParser extends AbstractRequestParser
{
    protected function getRequestMatcher(): RequestMatcherInterface
    {
        return new ChainRequestMatcher([
            new IsJsonRequestMatcher(),
            new MethodRequestMatcher('POST'),
        ]);
    }

    /**
     * @throws JsonException
     */
    protected function doParse(Request $request, #[\SensitiveParameter] string $secret): ?RemoteEvent
    {
        $this->validateHeaders($request->headers);

        try {
            $parsedEvents = EventRequestParser::parseEventRequest(
                body: $request->getContent(),
                channelSecret:  $secret,
                signature: $request->headers->get(HTTPHeader::LINE_SIGNATURE),
            );

        } catch (InvalidSignatureException|InvalidEventRequestException $exception) {
            throw new RejectWebhookException(406, $exception->getMessage());
        }

        return new RemoteEvent(
            name: 'line.bot',
            id: $parsedEvents->getDestination(),
            payload: $parsedEvents->getEvents(),
        );
    }

    private function validateHeaders(HeaderBag $headers): void
    {
        if (!$headers->has(HTTPHeader::LINE_SIGNATURE)) {
            throw new RejectWebhookException(Response::HTTP_UNAUTHORIZED, sprintf('Missing "%s" HTTP request signature header.', HTTPHeader::LINE_SIGNATURE));
        }
    }
}
