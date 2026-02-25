<?php

declare(strict_types=1);

namespace Tests\Action\Repository;

use App\Repository\UserRepository;
use Tests\Support\ActionTestCase;

final class UserRepositoryTest extends ActionTestCase
{
    public function testFindActiveByEmailReturnsOnlyActiveUser(): void
    {
        $userId = $this->fixtures->createUser(['email' => 'active@example.com', 'is_active' => 1]);
        $this->fixtures->createUser(['email' => 'inactive@example.com', 'is_active' => 0, '_counter' => 2]);
        $repository = new UserRepository($this->conn());

        $active = $repository->findActiveByEmail('active@example.com');
        $inactive = $repository->findActiveByEmail('inactive@example.com');

        self::assertSame($userId, (int) ($active['id'] ?? 0));
        self::assertNull($inactive);
    }

    public function testEmailExistsSupportsExcludeUserId(): void
    {
        $user1 = $this->fixtures->createUser(['email' => 'user1@example.com', '_counter' => 1]);
        $user2 = $this->fixtures->createUser(['email' => 'user2@example.com', '_counter' => 2]);
        $repository = new UserRepository($this->conn());

        self::assertTrue($repository->emailExists('user1@example.com'));
        self::assertFalse($repository->emailExists('user1@example.com', $user1));
        self::assertTrue($repository->emailExists('user2@example.com', $user1));
        self::assertFalse($repository->emailExists('missing@example.com'));
    }

    public function testCreateUserAndLookupHelpers(): void
    {
        $repository = new UserRepository($this->conn());
        $passwordHash = password_hash('abc123', PASSWORD_BCRYPT);
        $userId = $repository->createUser('Repo User', 'repo@example.com', (string) $passwordHash);

        self::assertNotNull($userId);
        self::assertSame('America/Sao_Paulo', $repository->findTimezoneById($userId));
        self::assertSame($passwordHash, $repository->findPasswordHashById($userId));
    }

    public function testUpdateLastLoginAndProfileUpdatesPersist(): void
    {
        $userId = $this->fixtures->createUser(['email' => 'before@example.com']);
        $repository = new UserRepository($this->conn());

        $repository->updateLastLogin($userId);
        $repository->updateProfileWithoutPassword($userId, 'after@example.com', 'https://example.com/a.png');
        $row = $this->db()->fetchOne('SELECT email, avatar_url, last_login, password FROM users WHERE id = ' . (int) $userId);
        self::assertSame('after@example.com', $row['email'] ?? null);
        self::assertSame('https://example.com/a.png', $row['avatar_url'] ?? null);
        self::assertNotNull($row['last_login'] ?? null);

        $newHash = password_hash('newpass', PASSWORD_BCRYPT);
        $repository->updateProfileWithPassword($userId, 'final@example.com', '', (string) $newHash);
        $updated = $this->db()->fetchOne('SELECT email, avatar_url, password FROM users WHERE id = ' . (int) $userId);
        self::assertSame('final@example.com', $updated['email'] ?? null);
        self::assertSame('', (string) ($updated['avatar_url'] ?? ''));
        self::assertTrue(password_verify('newpass', (string) ($updated['password'] ?? '')));
    }
}
