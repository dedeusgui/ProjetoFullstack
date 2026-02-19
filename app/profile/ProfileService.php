<?php

class ProfileService
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function updateProfile(int $userId, array $input): array
    {
        $email = trim($input['email'] ?? '');
        $avatarUrl = trim($input['avatar_url'] ?? '');
        $newPassword = trim($input['new_password'] ?? '');
        $confirmPassword = trim($input['confirm_password'] ?? '');
        $currentPassword = trim($input['current_password'] ?? '');
        $theme = ($input['theme'] ?? 'light') === 'dark' ? 'dark' : 'light';
        $primaryColor = strtoupper(trim($input['primary_color'] ?? '#4A74FF'));
        $accentColor = strtoupper(trim($input['accent_color'] ?? '#59D186'));
        $textScale = (float) ($input['text_scale'] ?? 1.00);

        $validationError = $this->validateProfileInput(
            $userId,
            $email,
            $avatarUrl,
            $newPassword,
            $confirmPassword,
            $currentPassword,
            $primaryColor,
            $accentColor,
            $textScale
        );

        if ($validationError !== null) {
            return ['success' => false, 'message' => $validationError];
        }

        $shouldUpdatePassword = $newPassword !== '' || $confirmPassword !== '';
        $passwordHash = $shouldUpdatePassword ? password_hash($newPassword, PASSWORD_BCRYPT) : null;

        if ($shouldUpdatePassword) {
            $updateStmt = $this->conn->prepare('UPDATE users SET email = ?, avatar_url = ?, password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
            $updateStmt->bind_param('sssi', $email, $avatarUrl, $passwordHash, $userId);
        } else {
            $updateStmt = $this->conn->prepare('UPDATE users SET email = ?, avatar_url = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
            $updateStmt->bind_param('ssi', $email, $avatarUrl, $userId);
        }

        $this->conn->begin_transaction();

        try {
            if (!$updateStmt->execute()) {
                throw new Exception('Falha ao atualizar dados básicos do usuário.');
            }

            $settingsStmt = $this->conn->prepare(
                'INSERT INTO user_settings (user_id, theme, primary_color, accent_color, text_scale)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    theme = VALUES(theme),
                    primary_color = VALUES(primary_color),
                    accent_color = VALUES(accent_color),
                    text_scale = VALUES(text_scale),
                    updated_at = CURRENT_TIMESTAMP'
            );
            $settingsStmt->bind_param('isssd', $userId, $theme, $primaryColor, $accentColor, $textScale);

            if (!$settingsStmt->execute()) {
                throw new Exception('Falha ao atualizar preferências visuais do usuário.');
            }

            $this->conn->commit();

            return [
                'success' => true,
                'message' => $shouldUpdatePassword
                    ? 'Configurações atualizadas com sucesso! E sua senha foi alterada.'
                    : 'Configurações atualizadas com sucesso!',
                'email' => $email
            ];
        } catch (Throwable $exception) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Não foi possível salvar as configurações. Tente novamente.'];
        }
    }

    public function resetAppearance(int $userId): array
    {
        $defaultTheme = 'light';
        $defaultPrimaryColor = '#4A74FF';
        $defaultAccentColor = '#59D186';
        $defaultTextScale = 1.00;

        $stmt = $this->conn->prepare(
            'INSERT INTO user_settings (user_id, theme, primary_color, accent_color, text_scale)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                theme = VALUES(theme),
                primary_color = VALUES(primary_color),
                accent_color = VALUES(accent_color),
                text_scale = VALUES(text_scale),
                updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->bind_param('isssd', $userId, $defaultTheme, $defaultPrimaryColor, $defaultAccentColor, $defaultTextScale);

        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Aparência restaurada para o padrão do site.'];
        }

        return ['success' => false, 'message' => 'Não foi possível restaurar as configurações de aparência.'];
    }

    private function validateProfileInput(
        int $userId,
        string $email,
        string $avatarUrl,
        string $newPassword,
        string $confirmPassword,
        string $currentPassword,
        string $primaryColor,
        string $accentColor,
        float $textScale
    ): ?string {
        if ($email === '') {
            return 'O e-mail é obrigatório.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Informe um e-mail válido.';
        }

        if ($avatarUrl !== '' && !filter_var($avatarUrl, FILTER_VALIDATE_URL)) {
            return 'A URL da imagem de perfil é inválida.';
        }

        if (!preg_match('/^#[0-9A-F]{6}$/', $primaryColor)) {
            return 'A cor principal é inválida.';
        }

        if (!preg_match('/^#[0-9A-F]{6}$/', $accentColor)) {
            return 'A cor de destaque é inválida.';
        }

        if ($textScale < 0.9 || $textScale > 1.2) {
            return 'O ajuste de tamanho de texto está fora do limite permitido.';
        }

        $checkEmailStmt = $this->conn->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
        $checkEmailStmt->bind_param('si', $email, $userId);
        $checkEmailStmt->execute();
        if ($checkEmailStmt->get_result()->num_rows > 0) {
            return 'Este e-mail já está em uso por outro usuário.';
        }

        $shouldUpdatePassword = $newPassword !== '' || $confirmPassword !== '';
        if (!$shouldUpdatePassword) {
            return null;
        }

        if ($newPassword === '' || $confirmPassword === '') {
            return 'Para alterar a senha, preencha os campos de nova senha e confirmação.';
        }

        if (strlen($newPassword) < 6) {
            return 'A nova senha deve ter ao menos 6 caracteres.';
        }

        if ($newPassword !== $confirmPassword) {
            return 'A confirmação da nova senha não confere.';
        }

        if ($currentPassword === '') {
            return 'Informe a senha atual para confirmar a alteração.';
        }

        $currentPasswordStmt = $this->conn->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
        $currentPasswordStmt->bind_param('i', $userId);
        $currentPasswordStmt->execute();
        $currentPasswordResult = $currentPasswordStmt->get_result()->fetch_assoc();

        if (!$currentPasswordResult || !password_verify($currentPassword, $currentPasswordResult['password'])) {
            return 'A senha atual está incorreta.';
        }

        return null;
    }
}
