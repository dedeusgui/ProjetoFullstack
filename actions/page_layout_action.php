<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

require_once '../config/conexao.php';
require_once '../config/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Faça login para salvar layouts personalizados.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido.'
    ]);
    exit;
}

$rawBody = file_get_contents('php://input');
$payload = json_decode($rawBody, true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Payload inválido.'
    ]);
    exit;
}

$action = $payload['action'] ?? '';
$pageKey = trim((string) ($payload['page_key'] ?? ''));

if (!preg_match('/^[a-z0-9_-]{2,80}$/i', $pageKey)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Identificador de página inválido.'
    ]);
    exit;
}

$allowedPages = ['landing'];
if (!in_array($pageKey, $allowedPages, true)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Esta página não permite personalização de blocos.'
    ]);
    exit;
}

$layoutTableSql = "
    CREATE TABLE IF NOT EXISTS user_page_layouts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        page_key VARCHAR(80) NOT NULL,
        layout_json LONGTEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_user_page_layout (user_id, page_key),
        CONSTRAINT fk_user_page_layouts_user
            FOREIGN KEY (user_id) REFERENCES users(id)
            ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";

if (!$conn->query($layoutTableSql)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Falha ao preparar armazenamento de layout.'
    ]);
    exit;
}

$userId = (int) getUserId();

if ($action === 'reset') {
    $deleteStmt = $conn->prepare('DELETE FROM user_page_layouts WHERE user_id = ? AND page_key = ?');
    if (!$deleteStmt) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Falha ao preparar reset de layout.'
        ]);
        exit;
    }

    $deleteStmt->bind_param('is', $userId, $pageKey);
    $deleteStmt->execute();

    echo json_encode([
        'success' => true,
        'message' => 'Layout restaurado para o padrão.'
    ]);
    exit;
}

if ($action !== 'save') {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Ação inválida.'
    ]);
    exit;
}

$order = $payload['order'] ?? [];
if (!is_array($order) || !$order) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'A ordem dos blocos é obrigatória.'
    ]);
    exit;
}

$normalizedOrder = [];
foreach ($order as $item) {
    if (!is_string($item)) {
        continue;
    }

    $blockId = trim($item);
    if ($blockId === '' || strlen($blockId) > 120) {
        continue;
    }

    if (!preg_match('/^[a-z0-9_-]+$/i', $blockId)) {
        continue;
    }

    $normalizedOrder[$blockId] = $blockId;
}

if (!$normalizedOrder) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Nenhum bloco válido foi enviado.'
    ]);
    exit;
}

$layoutJson = json_encode([
    'order' => array_values($normalizedOrder)
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$upsertStmt = $conn->prepare(
    'INSERT INTO user_page_layouts (user_id, page_key, layout_json)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE
        layout_json = VALUES(layout_json),
        updated_at = CURRENT_TIMESTAMP'
);

if (!$upsertStmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Falha ao preparar salvamento de layout.'
    ]);
    exit;
}

$upsertStmt->bind_param('iss', $userId, $pageKey, $layoutJson);

if (!$upsertStmt->execute()) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Não foi possível salvar o layout.'
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Layout salvo com sucesso.'
]);
