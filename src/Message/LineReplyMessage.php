<?php

namespace App\Message;

final class LineReplyMessage
{
    public function __construct(
        private readonly string $replyToken,
        private readonly string $text
    ) {
    }

    public function getReplyToken(): string
    {
        return $this->replyToken;
    }

    public function getText(): string
    {
        return $this->text;
    }
}