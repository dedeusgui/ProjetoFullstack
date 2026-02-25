<?php

declare(strict_types=1);

namespace Tests\Action\Config;

use Tests\Support\ActionTestCase;

final class AuthHelpersDbTest extends ActionTestCase
{
    public function testGetAuthenticatedUserRecordReturnsNullWhenNotLoggedIn(): void
    {
        $userId = $this->fixtures->createUser();
        self::assertGreaterThan(0, $userId);

        $this->setRequest([], [], []);

        self::assertNull(getAuthenticatedUserRecord($this->conn()));
    }

    public function testGetAuthenticatedUserRecordReturnsJoinedUserAndSettingsData(): void
    {
        $userId = $this->fixtures->createUser([
            'name' => 'Helper DB User',
            'email' => 'helperdb@example.com',
        ]);
        $this->db()->execute(
            "UPDATE user_settings SET theme = 'dark', primary_color = '#112233', accent_color = '#AABBCC', text_scale = 1.1 WHERE user_id = " . (int) $userId
        );
        $this->setRequest([], [], ['user_id' => $userId]);

        $record = getAuthenticatedUserRecord($this->conn());

        self::assertNotNull($record);
        self::assertSame((string) $userId, (string) ($record['id'] ?? ''));
        self::assertSame('Helper DB User', $record['name'] ?? null);
        self::assertSame('helperdb@example.com', $record['email'] ?? null);
        self::assertSame('dark', $record['theme'] ?? null);
        self::assertArrayHasKey('level', $record);
        self::assertArrayHasKey('experience_points', $record);
    }
}
