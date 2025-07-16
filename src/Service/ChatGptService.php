<?php

namespace App\Service;

use OpenAI\Client;
use OpenAI\Factory;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ChatGptService
{
    private Client $client;

    public function __construct(
        #[Autowire('%env(OPENAI_API_KEY)%')]
        private readonly string $apiKey,
        private readonly LoggerInterface $logger,
        #[Autowire('%chatgpt%')]
        private readonly array $config
    ) {
        $this->client = (new Factory())->withApiKey($this->apiKey)->make();
    }

    public function generateResponse(string $message): string
    {
        try {
            $response = $this->client->chat()->create([
                'model' => $this->config['model'],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->config['system_message']
                    ],
                    [
                        'role' => 'user',
                        'content' => $message
                    ]
                ],
                'max_tokens' => $this->config['max_tokens'],
                'temperature' => $this->config['temperature'],
            ]);

            $reply = $response->choices[0]->message->content;
            
            $this->logger->info('ChatGPT response generated', [
                'user_message' => $message,
                'response_length' => strlen($reply),
                'model' => $this->config['model']
            ]);

            return $reply;
        } catch (\Exception $e) {
            $this->logger->error('ChatGPT API error', [
                'error' => $e->getMessage(),
                'user_message' => $message
            ]);
            
            if (str_contains($e->getMessage(), 'not active')) {
                return $this->config['error_messages']['payment_required'];
            }
            
            return $this->config['error_messages']['general_error'];
        }
    }
}