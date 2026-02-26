<?php

declare(strict_types=1);

namespace Tests\Action\Api;

use App\Api\Internal\AchievementsApiPayloadBuilder;
use Tests\Support\ActionTestCase;

final class AchievementsApiPayloadBuilderTest extends ActionTestCase
{
    public function testBuildReturnsExpectedPageDataShape(): void
    {
        $userId = $this->fixtures->createUser(['timezone' => 'UTC']);

        $payload = AchievementsApiPayloadBuilder::build($this->conn(), $userId);

        self::assertTrue($payload['success'] ?? false);
        self::assertArrayHasKey('data', $payload);

        $data = $payload['data'] ?? [];
        self::assertArrayHasKey('hero', $data);
        self::assertArrayHasKey('achievements', $data);
        self::assertArrayHasKey('highlights', $data);
        self::assertArrayHasKey('stats', $data);

        self::assertIsArray($data['achievements']);
        self::assertArrayHasKey('unlocked_count', $data['hero']);
        self::assertArrayHasKey('total_available', $data['hero']);
        self::assertArrayHasKey('progress_percent', $data['hero']);
        self::assertArrayHasKey('xp_progress_percent', $data['hero']);
        self::assertArrayHasKey('xp_to_next_level', $data['hero']);

        self::assertArrayHasKey('latest_unlocked', $data['highlights']);
        self::assertArrayHasKey('rarest_unlocked', $data['highlights']);
        self::assertArrayHasKey('next_achievement', $data['highlights']);

        self::assertArrayHasKey('legendary_unlocked', $data['stats']);
        self::assertArrayHasKey('overall_progress_percent', $data['stats']);
        self::assertArrayHasKey('total_habits', $data['stats']);
    }
}
