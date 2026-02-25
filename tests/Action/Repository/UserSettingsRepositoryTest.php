<?php

declare(strict_types=1);

namespace Tests\Action\Repository;

use App\Repository\UserSettingsRepository;
use Tests\Support\ActionTestCase;

final class UserSettingsRepositoryTest extends ActionTestCase
{
    public function testUpsertAppearanceUpdatesExistingSettingsRow(): void
    {
        $userId = $this->fixtures->createUser();
        $repository = new UserSettingsRepository($this->conn());

        self::assertTrue($repository->upsertAppearance($userId, 'dark', '#112233', '#AABBCC', 1.1));

        $row = $this->db()->fetchOne('SELECT theme, primary_color, accent_color, text_scale FROM user_settings WHERE user_id = ' . (int) $userId);
        self::assertSame('dark', $row['theme'] ?? null);
        self::assertSame('#112233', strtoupper((string) ($row['primary_color'] ?? '')));
        self::assertSame('#AABBCC', strtoupper((string) ($row['accent_color'] ?? '')));
        self::assertSame('1.10', number_format((float) ($row['text_scale'] ?? 0), 2, '.', ''));
    }
}
