<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\RequestContext;
use PHPUnit\Framework\TestCase;

final class RequestContextTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->resetRequestId();
        parent::tearDown();
    }

    public function testEnsureRequestIdGeneratesStableNonEmptyId(): void
    {
        $id1 = RequestContext::ensureRequestId();
        $id2 = RequestContext::getRequestId();

        self::assertSame($id1, $id2);
        self::assertMatchesRegularExpression('/^[a-f0-9]{16}$/', $id1);
    }

    public function testSetRequestIdOverridesGeneratedValueWhenNonEmpty(): void
    {
        RequestContext::setRequestId(' custom-id ');

        self::assertSame('custom-id', RequestContext::getRequestId());
    }

    public function testSetRequestIdIgnoresEmptyValues(): void
    {
        RequestContext::setRequestId('seed-id');
        RequestContext::setRequestId('   ');

        self::assertSame('seed-id', RequestContext::getRequestId());
    }

    private function resetRequestId(): void
    {
        $ref = new \ReflectionProperty(RequestContext::class, 'requestId');
        $ref->setValue(null, null);
    }
}
