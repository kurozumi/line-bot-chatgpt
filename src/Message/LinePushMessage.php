<?php

namespace App\Message;

final class LinePushMessage
{
    public function __construct(
        private readonly string $userId,
        private readonly string $text
    ) {
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getText(): string
    {
        return $this->text;
    }
}