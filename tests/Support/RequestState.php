<?php

declare(strict_types=1);

namespace Tests\Support;

final class RequestState
{
    private array $serverBackup;
    private array $postBackup;
    private array $sessionBackup;

    public function __construct()
    {
        $this->serverBackup = $_SERVER ?? [];
        $this->postBackup = $_POST ?? [];
        $this->sessionBackup = $_SESSION ?? [];
    }

    public function apply(array $server = [], array $post = [], array $session = []): void
    {
        $_SERVER = array_merge([
            'REQUEST_METHOD' => 'POST',
            'HTTP_HOST' => 'localhost',
            'SCRIPT_NAME' => '/actions/test.php',
        ], $server);

        $_POST = $post;
        $_SESSION = $session;
    }

    public function restore(): void
    {
        $_SERVER = $this->serverBackup;
        $_POST = $this->postBackup;
        $_SESSION = $this->sessionBackup;
    }
}
