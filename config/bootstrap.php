<?php

/**
 * Inicialização compartilhada da aplicação web.
 *
 * - Garante sessão ativa
 * - Carrega utilitários comuns
 * - Opcionalmente carrega conexão com banco
 */
function bootApp(bool $loadDatabase = true): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . '/auth.php';
    require_once __DIR__ . '/helpers.php';
    require_once __DIR__ . '/action_helpers.php';

    if ($loadDatabase) {
        require_once __DIR__ . '/conexao.php';
    }
}
