<?php

namespace App\RemoteEvent;

use App\Message\ChatGptResponseMessage;
use App\Message\LineReplyMessage;
use LINE\Webhook\Model\MessageEvent;
use LINE\Webhook\Model\TextMessageContent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RemoteEvent\Attribute\AsRemoteEventConsumer;
use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;

#[AsRemoteEventConsumer('line')]
final class LineWebhookConsumer implements ConsumerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $messageBus
    ) {

    }

    public function consume(RemoteEvent $event): void
    {
        foreach ($event->getPayload() as $lineEvent) {
            if (!($lineEvent instanceof MessageEvent)) {
                continue;
            }

            $message = $lineEvent->getMessage();
            if (!($message instanceof TextMessageContent)) {
                continue;
            }

            $userMessage = $message->getText();
            
            // 即座に「考え中...」メッセージを返信
            try {
                $this->messageBus->dispatch(new LineReplyMessage(
                    $lineEvent->getReplyToken(),
                    '考え中... 少々お待ちください 🤔'
                ));
            } catch (ExceptionInterface $e) {
                $this->logger->error($e->getMessage());
            }

            // ChatGPTの応答を非同期で生成・送信
            try {
                $this->messageBus->dispatch(new ChatGptResponseMessage(
                    $lineEvent->getSource()->getUserId(),
                    $userMessage
                ));
            } catch (ExceptionInterface $e) {
                $this->logger->error('Failed to dispatch ChatGPT response message', [
                    'error' => $e->getMessage(),
                    'userId' => $lineEvent->getSource()->getUserId()
                ]);
            }
            
            $this->logger->info('LINE reply message dispatched', [
                'replyToken' => $lineEvent->getReplyToken(),
                'user_message' => $userMessage,
                'immediate_reply' => '考え中... 少々お待ちください 🤔'
            ]);
        }
    }
}
