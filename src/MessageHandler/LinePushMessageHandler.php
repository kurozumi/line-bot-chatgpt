<?php

namespace App\MessageHandler;

use App\Message\LinePushMessage;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Clients\MessagingApi\ApiException;
use LINE\Clients\MessagingApi\Configuration;
use LINE\Clients\MessagingApi\Model\PushMessageRequest;
use LINE\Clients\MessagingApi\Model\TextMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class LinePushMessageHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
        #[Autowire('%env(LINE_ACCESS_TOKEN)%')]
        private readonly string $lineAccessToken
    ) {
    }

    public function __invoke(LinePushMessage $message): void
    {
        $config = Configuration::getDefaultConfiguration();
        $config->setAccessToken($this->lineAccessToken);
        $messagingApiApi = new MessagingApiApi(
            config: $config,
        );

        try {
            $messagingApiApi->pushMessage(new PushMessageRequest([
                'to' => $message->getUserId(),
                'messages' => [
                    (new TextMessage(['text' => $message->getText()]))->setType('text'),
                ],
            ]));
            
            $this->logger->info('LINE push message sent successfully', [
                'userId' => $message->getUserId(),
                'text' => $message->getText()
            ]);
        } catch (ApiException $e) {
            $this->logger->error('Failed to send LINE push message', [
                'error' => $e->getMessage(),
                'userId' => $message->getUserId()
            ]);
            throw $e;
        }
    }
}