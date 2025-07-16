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
        private readonly LoggerInterface $logger
    ) {
        $this->client = (new Factory())->withApiKey($this->apiKey)->make();
    }

    public function generateResponse(string $message): string
    {
        try {
            $response = $this->client->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'あなたは親切で丁寧な日本語のアシスタントです。簡潔で分かりやすい返答を心がけてください。'
                    ],
                    [
                        'role' => 'user',
                        'content' => $message
                    ]
                ],
                'max_tokens' => 1000,
                'temperature' => 0.7,
            ]);

            $reply = $response->choices[0]->message->content;
            
            $this->logger->info('ChatGPT response generated', [
                'user_message' => $message,
                'response_length' => strlen($reply),
                'model' => 'gpt-3.5-turbo'
            ]);

            return $reply;
        } catch (\Exception $e) {
            $this->logger->error('ChatGPT API error', [
                'error' => $e->getMessage(),
                'user_message' => $message
            ]);
            
            if (str_contains($e->getMessage(), 'not active')) {
                return 'OpenAIアカウントの支払い情報を確認してください。';
            }
            
            return 'すみません、一時的に返答できません。しばらく経ってから再度お試しください。';
        }
    }
}