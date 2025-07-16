<?php

namespace App\MessageHandler;

use App\Message\LineReplyMessage;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Clients\MessagingApi\ApiException;
use LINE\Clients\MessagingApi\Configuration;
use LINE\Clients\MessagingApi\Model\ReplyMessageRequest;
use LINE\Clients\MessagingApi\Model\TextMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class LineReplyMessageHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
        #[Autowire('%env(LINE_ACCESS_TOKEN)%')]
        private readonly string $lineAccessToken
    ) {
    }

    /**
     * @throws ApiException
     */
    public function __invoke(LineReplyMessage $message): void
    {
        $config = Configuration::getDefaultConfiguration();
        $config->setAccessToken($this->lineAccessToken);
        $messagingApiApi = new MessagingApiApi(
            config: $config,
        );

        try {
            $messagingApiApi->replyMessage(new ReplyMessageRequest([
                'replyToken' => $message->getReplyToken(),
                'messages' => [
                    (new TextMessage(['text' => $message->getText()]))->setType('text'),
                ],
            ]));
            
            $this->logger->info('LINE reply message sent successfully', [
                'replyToken' => $message->getReplyToken(),
                'text' => $message->getText()
            ]);
        } catch (ApiException $e) {
            $this->logger->error('Failed to send LINE reply message', [
                'error' => $e->getMessage(),
                'replyToken' => $message->getReplyToken()
            ]);
            throw $e;
        }
    }
}