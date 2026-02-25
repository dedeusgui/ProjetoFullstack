<?php

namespace App\Actions;

final class ActionResponse
{
    private function __construct(
        private string $type,
        private int $statusCode = 200,
        private ?string $redirectPath = null,
        private array $flash = [],
        private array $payload = []
    ) {
    }

    public static function redirect(string $path, array $flash = [], int $statusCode = 302): self
    {
        return new self('redirect', $statusCode, $path, $flash);
    }

    public static function json(array $payload, int $statusCode = 200): self
    {
        return new self('json', $statusCode, null, [], $payload);
    }

    public static function csv(string $filename, string $content, int $statusCode = 200): self
    {
        return new self('csv', $statusCode, null, [], [
            'filename' => $filename,
            'content' => $content,
        ]);
    }

    public function isRedirect(): bool
    {
        return $this->type === 'redirect';
    }

    public function isJson(): bool
    {
        return $this->type === 'json';
    }

    public function isCsv(): bool
    {
        return $this->type === 'csv';
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getRedirectPath(): ?string
    {
        return $this->redirectPath;
    }

    public function getFlash(): array
    {
        return $this->flash;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}
