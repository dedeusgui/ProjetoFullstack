<?php

declare(strict_types=1);

namespace Tests\Action\Profile;

use App\Profile\ProfileService;
use Tests\Support\ActionTestCase;

final class ProfileServiceTest extends ActionTestCase
{
    public function testUpdateProfileRejectsInvalidEmail(): void
    {
        $userId = $this->fixtures->createUser();
        $service = new ProfileService($this->conn());

        $result = $service->updateProfile($userId, $this->validInput([
            'email' => 'invalid-email',
        ]));

        self::assertFalse($result['success'] ?? true);
        self::assertStringContainsString('e-mail', (string) ($result['message'] ?? ''));
    }

    public function testUpdateProfileRejectsUnsafeAvatarUrl(): void
    {
        $userId = $this->fixtures->createUser();
        $service = new ProfileService($this->conn());

        $result = $service->updateProfile($userId, $this->validInput([
            'avatar_url' => 'http://localhost/avatar.png',
        ]));

        self::assertFalse($result['success'] ?? true);
        self::assertStringContainsString('imagem de perfil', (string) ($result['message'] ?? ''));
    }

    public function testUpdateProfileRejectsDuplicateEmail(): void
    {
        $userId = $this->fixtures->createUser(['email' => 'owner@example.com', '_counter' => 1]);
        $this->fixtures->createUser(['email' => 'other@example.com', '_counter' => 2]);
        $service = new ProfileService($this->conn());

        $result = $service->updateProfile($userId, $this->validInput([
            'email' => 'other@example.com',
        ]));

        self::assertFalse($result['success'] ?? true);
        self::assertStringContainsString('e-mail', (string) ($result['message'] ?? ''));
    }

    public function testUpdateProfileRequiresCurrentPasswordWhenChangingPassword(): void
    {
        $userId = $this->fixtures->createUser();
        $service = new ProfileService($this->conn());

        $result = $service->updateProfile($userId, $this->validInput([
            'new_password' => 'newsecret',
            'confirm_password' => 'newsecret',
            'current_password' => '',
        ]));

        self::assertFalse($result['success'] ?? true);
        self::assertStringContainsString('senha atual', (string) ($result['message'] ?? ''));
    }

    public function testUpdateProfileRejectsWrongCurrentPassword(): void
    {
        $userId = $this->fixtures->createUser();
        $service = new ProfileService($this->conn());

        $result = $service->updateProfile($userId, $this->validInput([
            'new_password' => 'newsecret',
            'confirm_password' => 'newsecret',
            'current_password' => 'wrong-password',
        ]));

        self::assertFalse($result['success'] ?? true);
        self::assertStringContainsString('senha atual', (string) ($result['message'] ?? ''));
    }

    public function testUpdateProfileSucceedsWithoutPasswordChangeAndUpdatesAppearance(): void
    {
        $userId = $this->fixtures->createUser([
            'email' => 'before@example.com',
        ]);
        $service = new ProfileService($this->conn());

        $result = $service->updateProfile($userId, $this->validInput([
            'email' => 'updated@example.com',
            'avatar_url' => '',
            'theme' => 'dark',
            'primary_color' => '#112233',
            'accent_color' => '#AABBCC',
            'text_scale' => '1.1',
        ]));

        self::assertTrue($result['success'] ?? false);
        self::assertSame('updated@example.com', $result['email'] ?? null);

        $user = $this->db()->fetchOne('SELECT email, avatar_url, password FROM users WHERE id = ' . (int) $userId);
        self::assertSame('updated@example.com', $user['email'] ?? null);
        self::assertSame('', (string) ($user['avatar_url'] ?? ''));
        self::assertTrue(password_verify('secret123', (string) ($user['password'] ?? '')));

        $settings = $this->db()->fetchOne('SELECT theme, primary_color, accent_color, text_scale FROM user_settings WHERE user_id = ' . (int) $userId);
        self::assertSame('dark', $settings['theme'] ?? null);
        self::assertSame('#112233', strtoupper((string) ($settings['primary_color'] ?? '')));
        self::assertSame('#AABBCC', strtoupper((string) ($settings['accent_color'] ?? '')));
        self::assertSame('1.10', number_format((float) ($settings['text_scale'] ?? 0), 2, '.', ''));
    }

    public function testUpdateProfileSucceedsWithPasswordChange(): void
    {
        $userId = $this->fixtures->createUser([
            'email' => 'pwdchange@example.com',
        ]);
        $service = new ProfileService($this->conn());

        $result = $service->updateProfile($userId, $this->validInput([
            'email' => 'pwdchange@example.com',
            'new_password' => 'newsecret123',
            'confirm_password' => 'newsecret123',
            'current_password' => 'secret123',
        ]));

        self::assertTrue($result['success'] ?? false);

        $row = $this->db()->fetchOne('SELECT password FROM users WHERE id = ' . (int) $userId);
        self::assertNotNull($row);
        self::assertTrue(password_verify('newsecret123', (string) ($row['password'] ?? '')));
        self::assertFalse(password_verify('secret123', (string) ($row['password'] ?? '')));
    }

    public function testUpdateProfileRollsBackWhenSettingsUpsertFails(): void
    {
        $userId = 999999;
        $service = new ProfileService($this->conn());

        $result = $service->updateProfile($userId, $this->validInput([
            'email' => 'ghost@example.com',
        ]));

        self::assertFalse($result['success'] ?? true);
        self::assertStringContainsString('configura', strtolower((string) ($result['message'] ?? '')));
        self::assertNull($this->db()->fetchOne("SELECT id FROM users WHERE email = 'ghost@example.com'"));
        self::assertNull($this->db()->fetchOne('SELECT user_id FROM user_settings WHERE user_id = ' . $userId));
    }

    public function testResetAppearanceSucceedsForExistingUser(): void
    {
        $userId = $this->fixtures->createUser();
        $this->db()->execute("UPDATE user_settings SET theme = 'dark', primary_color = '#000000', accent_color = '#FFFFFF', text_scale = 1.2 WHERE user_id = " . (int) $userId);
        $service = new ProfileService($this->conn());

        $result = $service->resetAppearance($userId);

        self::assertTrue($result['success'] ?? false);
        $settings = $this->db()->fetchOne('SELECT theme, primary_color, accent_color, text_scale FROM user_settings WHERE user_id = ' . (int) $userId);
        self::assertSame('light', $settings['theme'] ?? null);
        self::assertSame('#4A74FF', strtoupper((string) ($settings['primary_color'] ?? '')));
        self::assertSame('#59D186', strtoupper((string) ($settings['accent_color'] ?? '')));
        self::assertSame('1.00', number_format((float) ($settings['text_scale'] ?? 0), 2, '.', ''));
    }

    public function testResetAppearanceFailsForMissingUser(): void
    {
        $service = new ProfileService($this->conn());

        $result = $service->resetAppearance(999999);

        self::assertFalse($result['success'] ?? true);
        self::assertStringContainsString('apar', strtolower((string) ($result['message'] ?? '')));
    }

    private function validInput(array $overrides = []): array
    {
        return array_merge([
            'email' => 'profile@example.com',
            'avatar_url' => '',
            'new_password' => '',
            'confirm_password' => '',
            'current_password' => '',
            'theme' => 'light',
            'primary_color' => '#4A74FF',
            'accent_color' => '#59D186',
            'text_scale' => '1.0',
        ], $overrides);
    }
}
