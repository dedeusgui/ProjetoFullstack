<?php
// Configurações do banco de dados (com suporte a variáveis de ambiente)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_NAME', getenv('DB_NAME') ?: 'doitly');
define('DB_PORT', (int) (getenv('DB_PORT') ?: 3306));

// Criar conexão MySQLi
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// Verificar conexão
if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    http_response_code(500);
    exit('Erro interno de conexao com o banco de dados.');
}

// Definir charset para UTF-8
$conn->set_charset('utf8mb4');
