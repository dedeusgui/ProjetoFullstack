<?php

declare(strict_types=1);

namespace Tests\Action\Profile;

use App\Actions\Profile\ExportUserDataCsvActionHandler;
use Tests\Support\ActionTestCase;

final class ExportUserDataCsvActionHandlerTest extends ActionTestCase
{
    public function testExportRequiresAuthentication(): void
    {
        $handler = new ExportUserDataCsvActionHandler();
        $this->setRequest(['REQUEST_METHOD' => 'GET'], [], []);

        $response = $handler->handle($this->conn(), $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/login.php');
    }

    public function testExportRedirectsWithErrorWhenUserRecordIsMissing(): void
    {
        $handler = new ExportUserDataCsvActionHandler();
        $this->setRequest(['REQUEST_METHOD' => 'GET'], [], ['user_id' => 999999]);

        $response = $handler->handle($this->conn(), $_SERVER, $_SESSION);

        $this->assertRedirect($response, '../public/dashboard.php');
        self::assertArrayHasKey('error_message', $response->getFlash());
    }

    public function testExportReturnsCsvResponseWithEmptySectionsWhenUserHasNoHabits(): void
    {
        $userId = $this->fixtures->createUser(['name' => 'Csv Empty', 'email' => 'csvempty@example.com']);
        $handler = new ExportUserDataCsvActionHandler();
        $this->setRequest(['REQUEST_METHOD' => 'GET'], [], ['user_id' => $userId]);

        $response = $handler->handle($this->conn(), $_SERVER, $_SESSION);

        self::assertTrue($response->isCsv());
        $payload = $response->getPayload();
        self::assertMatchesRegularExpression('/^resumo_dados_csv_empty_\\d{8}_\\d{6}\\.csv$/', (string) ($payload['filename'] ?? ''));

        $content = (string) ($payload['content'] ?? '');
        self::assertStringStartsWith("\xEF\xBB\xBF", $content);
        self::assertStringContainsString('Resumo geral', $content);
        self::assertStringContainsString('Nenhum habito cadastrado', $content);
        self::assertStringContainsString('Nenhuma conquista desbloqueada', $content);
    }

    public function testExportReturnsCsvSummaryForRepresentativeHabitFixture(): void
    {
        $userId = $this->fixtures->createUser(['name' => 'Csv User', 'email' => 'csvuser@example.com']);
        $habitId = $this->fixtures->createHabit($userId, [
            'title' => 'Drink Water',
            'is_active' => 1,
            'total_completions' => 1,
            'current_streak' => 1,
            'longest_streak' => 1,
        ]);
        $today = date('Y-m-d');
        $this->fixtures->createCompletion($habitId, $userId, ['completion_date' => $today]);
        $handler = new ExportUserDataCsvActionHandler();
        $this->setRequest(['REQUEST_METHOD' => 'GET'], [], ['user_id' => $userId]);

        $response = $handler->handle($this->conn(), $_SERVER, $_SESSION);

        self::assertTrue($response->isCsv());
        $content = (string) ($response->getPayload()['content'] ?? '');

        self::assertStringContainsString('Drink Water', $content);
        self::assertStringContainsString('Fez hoje', $content);
        self::assertStringContainsString('Conquistas desbloqueadas', $content);
        self::assertStringContainsString('1,1,1,0,1', str_replace("\r\n", "\n", $content));
    }
}
