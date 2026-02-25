<?php

declare(strict_types=1);

namespace Tests\Action\Repository;

use App\Repository\CategoryRepository;
use Tests\Support\ActionTestCase;

final class CategoryRepositoryTest extends ActionTestCase
{
    public function testFindIdByNameReturnsCategoryId(): void
    {
        $repository = new CategoryRepository($this->conn());

        self::assertSame(10, $repository->findIdByName('Outros'));
    }

    public function testFindIdByNameReturnsNullWhenMissing(): void
    {
        $repository = new CategoryRepository($this->conn());

        self::assertNull($repository->findIdByName('NaoExiste'));
    }
}
