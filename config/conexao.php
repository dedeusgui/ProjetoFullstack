<?php
// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'doitly');

// Criar conexão MySQLi
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Verificar conexão
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// Definir charset para UTF-8
$conn->set_charset("utf8mb4");

// Função helper para escapar strings
function escape($conn, $string) {
    return $conn->real_escape_string($string);
}

// Função helper para executar query e retornar resultado
function query($conn, $sql) {
    $result = $conn->query($sql);
    if (!$result) {
        error_log("Erro na query: " . $conn->error);
        return false;
    }
    return $result;
}

// Função helper para executar prepared statement
function prepare_execute($conn, $sql, $types, $params) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Erro ao preparar statement: " . $conn->error);
        return false;
    }
    
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    return $stmt;
}
