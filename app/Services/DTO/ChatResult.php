<?php

namespace App\Services\DTO;

final readonly class ChatResult
{
    public function __construct(
        public string $content,
        public string $model,
        public ?int $promptTokens,
        public ?int $completionTokens,
        public ?float $costUsd,
        public int $durationMs,
    ) {}
}
