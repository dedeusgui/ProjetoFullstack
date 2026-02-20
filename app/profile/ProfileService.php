<?php

require_once __DIR__ . '/../repository/UserRepository.php';
require_once __DIR__ . '/../repository/UserSettingsRepository.php';

class ProfileService
{
    private mysqli $conn;
    private UserRepository $userRepository;
    private UserSettingsRepository $userSettingsRepository;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->userRepository = new UserRepository($conn);
        $this->userSettingsRepository = new UserSettingsRepository($conn);
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

        $this->conn->begin_transaction();

        try {
            $updated = $shouldUpdatePassword
                ? $this->userRepository->updateProfileWithPassword($userId, $email, $avatarUrl, (string) $passwordHash)
                : $this->userRepository->updateProfileWithoutPassword($userId, $email, $avatarUrl);

            if (!$updated) {
                throw new Exception('Falha ao atualizar dados básicos do usuário.');
            }

            if (!$this->userSettingsRepository->upsertAppearance($userId, $theme, $primaryColor, $accentColor, $textScale)) {
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
        $ok = $this->userSettingsRepository->upsertAppearance($userId, 'light', '#4A74FF', '#59D186', 1.00);

        if ($ok) {
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

        if ($avatarUrl !== '' && !$this->isSafeAvatarUrl($avatarUrl)) {
            return 'A URL da imagem de perfil não é permitida. Use um domínio público com HTTP/HTTPS.';
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

        if ($this->userRepository->emailExists($email, $userId)) {
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

        $currentHash = $this->userRepository->findPasswordHashById($userId);
        if (!$currentHash || !password_verify($currentPassword, $currentHash)) {
            return 'A senha atual está incorreta.';
        }

        return null;
    }

    private function isSafeAvatarUrl(string $avatarUrl): bool
    {
        $parts = parse_url($avatarUrl);
        if (!is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === '' || $host === 'localhost') {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return !$this->isPrivateOrReservedIp($host);
        }

        $resolvedIp = gethostbyname($host);
        if ($resolvedIp === $host) {
            return false;
        }

        return !$this->isPrivateOrReservedIp($resolvedIp);
    }

    private function isPrivateOrReservedIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
