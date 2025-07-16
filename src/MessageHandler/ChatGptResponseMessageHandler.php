<?php

namespace App\MessageHandler;

use App\Message\ChatGptResponseMessage;
use App\Message\LinePushMessage;
use App\Service\ChatGptService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final class ChatGptResponseMessageHandler
{
    public function __construct(
        private readonly ChatGptService $chatGptService,
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(ChatGptResponseMessage $message): void
    {
        // 少し待機してから処理を開始（より自然な流れにするため）
        sleep(3);
        
        $this->logger->info('Starting ChatGPT response generation', [
            'userId' => $message->getUserId(),
            'userMessage' => $message->getUserMessage()
        ]);

        // ChatGPTの応答を生成
        $replyText = $this->chatGptService->generateResponse($message->getUserMessage());
        
        // Push Messageで実際の回答を送信
        try {
            $this->messageBus->dispatch(new LinePushMessage(
                $message->getUserId(),
                $replyText
            ));
            
            $this->logger->info('ChatGPT response sent via push message', [
                'userId' => $message->getUserId(),
                'responseLength' => strlen($replyText)
            ]);
        } catch (ExceptionInterface $e) {
            $this->logger->error('Failed to dispatch push message', [
                'error' => $e->getMessage(),
                'userId' => $message->getUserId()
            ]);
        }
    }
}