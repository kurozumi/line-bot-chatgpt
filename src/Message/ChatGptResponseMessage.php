<?php

namespace App\Message;

final class ChatGptResponseMessage
{
    public function __construct(
        private readonly string $userId,
        private readonly string $userMessage
    ) {
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getUserMessage(): string
    {
        return $this->userMessage;
    }
}