<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Actions\ActionResponse;

abstract class ActionTestCase extends DatabaseTestCase
{
    private ?RequestState $requestState = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requestState = new RequestState();
    }

    protected function tearDown(): void
    {
        $this->requestState?->restore();
        $this->requestState = null;
        parent::tearDown();
    }

    protected function setRequest(array $server = [], array $post = [], array $session = []): void
    {
        $this->requestState?->apply($server, $post, $session);
    }

    protected function assertRedirect(ActionResponse $response, string $path): void
    {
        self::assertTrue($response->isRedirect(), 'Expected redirect response.');
        self::assertSame($path, $response->getRedirectPath());
    }
}
