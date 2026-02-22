<?php

namespace App\Repository;

class UserSettingsRepository
{
    private \mysqli $conn;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function upsertAppearance(int $userId, string $theme, string $primaryColor, string $accentColor, float $textScale): bool
    {
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
        $stmt->bind_param('isssd', $userId, $theme, $primaryColor, $accentColor, $textScale);

        return $stmt->execute();
    }
}
