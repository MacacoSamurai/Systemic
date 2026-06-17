<?php

declare(strict_types=1);

namespace Automax\Controllers;

use Automax\Config\Database;
use Automax\Config\DatabaseException;

/**
 * Agendamento de serviços (/pedir).
 *
 * Desde a integração com o painel do cliente, todo agendamento é vinculado
 * à conta autenticada (id_cliente) e, opcionalmente, a um dos veículos
 * cadastrados do cliente (id_veiculo).
 */
class AgendamentoController
{
    private const TURNOS_VALIDOS = ['manha', 'tarde'];

    public static function criar(): void
    {
        self::validar_csrf();

        $id_cliente = (int) ($_SESSION['cliente_id'] ?? 0);
        $body       = self::ler_body();

        if ($body === null) {
            self::json(400, ['ok' => false, 'erro' => 'Corpo inválido.']);
            return;
        }

        $erros = self::validar($body);
        if (!empty($erros)) {
            self::json(422, ['ok' => false, 'erro' => implode(' ', $erros)]);
            return;
        }

        try {
            $db = Database::get_instance();

            $id_veiculo = self::resolver_veiculo($db, $id_cliente, $body);

            $db->execute(
                'INSERT INTO agendamentos
                    (id_cliente, id_veiculo, nome, telefone, email, placa, marca, modelo, ano,
                     combustivel, km, servico, sintomas, descricao, data_preferida, turno)
                 VALUES
                    (:id_cliente, :id_veiculo, :nome, :telefone, :email, :placa, :marca, :modelo, :ano,
                     :combustivel, :km, :servico, :sintomas, :descricao, :data_preferida, :turno)',
                [
                    ':id_cliente'     => $id_cliente,
                    ':id_veiculo'     => $id_veiculo,
                    ':nome'           => trim($body['nome']),
                    ':telefone'       => trim($body['telefone']),
                    ':email'          => trim($body['email'] ?? '') ?: null,
                    ':placa'          => strtoupper(trim($body['placa'] ?? '')) ?: null,
                    ':marca'          => trim($body['marca']),
                    ':modelo'         => trim($body['modelo']),
                    ':ano'            => self::ou_null_int($body['ano'] ?? ''),
                    ':combustivel'    => trim($body['combustivel'] ?? '') ?: null,
                    ':km'             => self::ou_null_int($body['km'] ?? ''),
                    ':servico'        => trim($body['servico'] ?? ''),
                    ':sintomas'       => trim($body['sintomas'] ?? '') ?: null,
                    ':descricao'      => trim($body['descricao'] ?? '') ?: null,
                    ':data_preferida' => $body['data_preferida'],
                    ':turno'          => $body['turno'] ?? null,
                ]
            );

            self::json(201, ['ok' => true]);
        } catch (DatabaseException $e) {
            error_log('[AgendamentoController] criar: ' . $e->getMessage());
            self::json(503, ['ok' => false, 'erro' => 'Serviço indisponível. Tente novamente.']);
        }
    }

    private static function validar(array $body): array
    {
        $erros = [];

        if (empty(trim($body['nome']     ?? ''))) $erros[] = 'Nome é obrigatório.';
        if (empty(trim($body['telefone'] ?? ''))) $erros[] = 'Telefone é obrigatório.';
        if (empty(trim($body['marca']    ?? ''))) $erros[] = 'Marca do veículo é obrigatória.';
        if (empty(trim($body['modelo']   ?? ''))) $erros[] = 'Modelo do veículo é obrigatório.';

        $servico = trim($body['servico'] ?? '');
        if ($servico === '' || mb_strlen($servico) > 100) {
            $erros[] = 'Selecione o serviço desejado.';
        }

        $data_preferida = $body['data_preferida'] ?? '';
        if (!self::data_valida($data_preferida)) {
            $erros[] = 'Data preferida inválida.';
        }

        $turno = $body['turno'] ?? null;
        if ($turno !== null && $turno !== '' && !in_array($turno, self::TURNOS_VALIDOS, true)) {
            $erros[] = 'Turno inválido.';
        }

        $email = $body['email'] ?? '';
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = 'E-mail inválido.';
        }

        return $erros;
    }

    private static function data_valida(string $data): bool
    {
        $partes = \DateTime::createFromFormat('Y-m-d', $data);
        return $partes !== false && $partes->format('Y-m-d') === $data;
    }

    /**
     * Se o agendamento informar a placa de um veículo já cadastrado pelo
     * cliente autenticado, vincula o agendamento a esse veículo (id_veiculo).
     * Caso contrário (placa nova ou não informada), retorna null.
     */
    private static function resolver_veiculo(Database $db, int $id_cliente, array $body): ?int
    {
        $placa = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $body['placa'] ?? ''));
        if ($placa === '' || $id_cliente <= 0) {
            return null;
        }

        $veiculo = $db->query_one(
            'SELECT id_veiculo FROM veiculos WHERE placa = :placa AND id_cliente = :id_cliente LIMIT 1',
            [':placa' => $placa, ':id_cliente' => $id_cliente]
        );

        return $veiculo !== null ? (int) $veiculo['id_veiculo'] : null;
    }

    private static function ou_null_int(mixed $valor): ?int
    {
        return $valor !== '' && $valor !== null ? (int) $valor : null;
    }

    private static function ler_body(): ?array
    {
        $raw = $GLOBALS['_test_input'] ?? file_get_contents('php://input');
        if (empty($raw)) return null;

        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    private static function validar_csrf(): void
    {
        $token_header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $token_sessao = $_SESSION['csrf_token'] ?? '';

        if (!$token_sessao || !hash_equals($token_sessao, $token_header)) {
            self::json(403, ['ok' => false, 'erro' => 'Token inválido.']);
            exit;
        }
    }

    private static function json(int $status, mixed $data): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}