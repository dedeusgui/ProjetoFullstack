<?php

namespace App\Repository;

use App\Support\Exceptions\InfrastructureException;

trait InteractsWithDatabase
{
    private function prepareOrFail(string $sql): \mysqli_stmt
    {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt instanceof \mysqli_stmt) {
            throw new InfrastructureException('Falha ao preparar comando de banco de dados.');
        }

        return $stmt;
    }

    private function executeOrFail(\mysqli_stmt $stmt): void
    {
        if (!$stmt->execute()) {
            throw new InfrastructureException('Falha ao executar comando de banco de dados.');
        }
    }

    private function getResultOrFail(\mysqli_stmt $stmt): \mysqli_result
    {
        $result = $stmt->get_result();
        if (!$result instanceof \mysqli_result) {
            throw new InfrastructureException('Falha ao obter resultado da consulta.');
        }

        return $result;
    }

    private function queryOrFail(string $sql): \mysqli_result
    {
        $result = $this->conn->query($sql);
        if (!$result instanceof \mysqli_result) {
            throw new InfrastructureException('Falha ao executar consulta de banco de dados.');
        }

        return $result;
    }
}
