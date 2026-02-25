<?php

declare(strict_types=1);

namespace Tests\Action\Auth;

use App\Auth\AuthService;
use Tests\Support\DatabaseTestCase;

final class AuthServiceTest extends DatabaseTestCase
{
    public function testAuthenticateReturnsUserForValidCredentials(): void
    {
        $userId = $this->fixtures->createUser([
            'name' => 'Alice Test',
            'email' => 'alice@example.com',
            'password' => password_hash('secret123', PASSWORD_BCRYPT),
        ]);

        $service = new AuthService($this->conn());
        $user = $service->authenticate('alice@example.com', 'secret123');

        self::assertNotNull($user);
        self::assertSame($userId, $user['id'] ?? null);
        self::assertSame('Alice Test', $user['name'] ?? null);
        self::assertSame('alice@example.com', $user['email'] ?? null);
    }

    public function testAuthenticateReturnsNullForWrongPassword(): void
    {
        $this->fixtures->createUser([
            'email' => 'wrongpass@example.com',
            'password' => password_hash('secret123', PASSWORD_BCRYPT),
        ]);

        $service = new AuthService($this->conn());

        self::assertNull($service->authenticate('wrongpass@example.com', 'bad-pass'));
    }

    public function testAuthenticateReturnsNullForUnknownEmail(): void
    {
        $service = new AuthService($this->conn());

        self::assertNull($service->authenticate('missing@example.com', 'secret123'));
    }

    public function testAuthenticateNormalizesEmailForLookup(): void
    {
        $userId = $this->fixtures->createUser([
            'email' => 'normalized@example.com',
        ]);

        $service = new AuthService($this->conn());
        $user = $service->authenticate('  NORMALIZED@example.com  ', 'secret123');

        self::assertSame($userId, $user['id'] ?? null);
    }

    public function testEmailExistsNormalizesEmail(): void
    {
        $this->fixtures->createUser([
            'email' => 'exists@example.com',
        ]);

        $service = new AuthService($this->conn());

        self::assertTrue($service->emailExists('  EXISTS@EXAMPLE.COM '));
        self::assertFalse($service->emailExists('missing@example.com'));
    }

    public function testRegisterCreatesUserWithNormalizedEmailAndHashedPassword(): void
    {
        $service = new AuthService($this->conn());

        $user = $service->register('  New User  ', '  NEW.USER@Example.com ', 'abc123');

        self::assertNotNull($user);
        self::assertSame('New User', $user['name'] ?? null);
        self::assertSame('new.user@example.com', $user['email'] ?? null);

        $row = $this->db()->fetchOne("SELECT id, name, email, password FROM users WHERE email = 'new.user@example.com' LIMIT 1");
        self::assertNotNull($row);
        self::assertSame((string) ($user['id'] ?? ''), (string) ($row['id'] ?? ''));
        self::assertSame('New User', $row['name'] ?? null);
        self::assertNotSame('abc123', $row['password'] ?? null);
        self::assertTrue(password_verify('abc123', (string) ($row['password'] ?? '')));
    }

    public function testRegisterThrowsMysqliExceptionWhenRepositoryInsertFails(): void
    {
        $this->fixtures->createUser([
            'email' => 'duplicate@example.com',
        ]);

        $service = new AuthService($this->conn());

        $this->expectException(\mysqli_sql_exception::class);
        $service->register('Duplicate', 'duplicate@example.com', 'secret123');
    }

    public function testUpdateLastLoginUpdatesTimestamp(): void
    {
        $userId = $this->fixtures->createUser([
            'email' => 'lastlogin@example.com',
        ]);
        $service = new AuthService($this->conn());

        $before = $this->db()->fetchOne('SELECT last_login FROM users WHERE id = ' . (int) $userId);
        self::assertNull($before['last_login'] ?? null);

        $service->updateLastLogin($userId);

        $after = $this->db()->fetchOne('SELECT last_login FROM users WHERE id = ' . (int) $userId);
        self::assertNotNull($after);
        self::assertNotNull($after['last_login'] ?? null);
    }
}
