<?php

declare(strict_types=1);

namespace Automax\Controllers;

use Automax\Config\Database;
use Automax\Config\DatabaseException;

/**
 * Área autenticada do cliente: gerenciamento dos próprios veículos
 * e consulta dos próprios agendamentos.
 *
 * Todas as operações são restritas ao id_cliente da sessão — um cliente
 * nunca recebe ou altera dados de outro cliente.
 */
class ClienteController
{
    public static function listar_veiculos(): void
    {
        $id_cliente = self::id_cliente_sessao();

        try {
            $db = Database::get_instance();
            $veiculos = $db->query_all(
                'SELECT id_veiculo, marca, cor, ano, modelo, placa
                   FROM veiculos
                  WHERE id_cliente = :id_cliente
                  ORDER BY id_veiculo DESC',
                [':id_cliente' => $id_cliente]
            );
            self::json(200, $veiculos);
        } catch (DatabaseException $e) {
            error_log('[ClienteController] listar_veiculos: ' . $e->getMessage());
            self::json(503, ['erro' => 'Serviço indisponível.']);
        }
    }

    public static function criar_veiculo(): void
    {
        self::validar_csrf();

        $id_cliente = self::id_cliente_sessao();
        $body       = self::ler_body();

        if ($body === null) {
            self::json(400, ['erro' => 'Corpo inválido.']);
            return;
        }

        [$marca, $modelo, $ano, $cor, $placa, $erros] = self::extrair_e_validar_veiculo($body);
        if (!empty($erros)) {
            self::json(422, ['erro' => implode(' ', $erros)]);
            return;
        }

        try {
            $db = Database::get_instance();

            if (self::placa_em_uso($db, $placa)) {
                self::json(409, ['erro' => 'Esta placa já está cadastrada no sistema.']);
                return;
            }

            $id_veiculo = $db->insert(
                'INSERT INTO veiculos (marca, cor, ano, modelo, placa, id_cliente)
                 VALUES (:marca, :cor, :ano, :modelo, :placa, :id_cliente)',
                [
                    ':marca'      => $marca,
                    ':cor'        => $cor,
                    ':ano'        => $ano,
                    ':modelo'     => $modelo,
                    ':placa'      => $placa,
                    ':id_cliente' => $id_cliente,
                ]
            );

            self::json(201, [
                'id_veiculo' => $id_veiculo,
                'marca'      => $marca,
                'cor'        => $cor,
                'ano'        => $ano,
                'modelo'     => $modelo,
                'placa'      => $placa,
            ]);
        } catch (DatabaseException $e) {
            error_log('[ClienteController] criar_veiculo: ' . $e->getMessage());
            self::json(503, ['erro' => 'Serviço indisponível.']);
        }
    }

    public static function atualizar_veiculo(array $params): void
    {
        self::validar_csrf();

        $id_cliente = self::id_cliente_sessao();
        $id_veiculo = self::validar_id($params['id'] ?? '');

        if ($id_veiculo === false) {
            self::json(400, ['erro' => 'ID inválido.']);
            return;
        }

        $body = self::ler_body();
        if ($body === null) {
            self::json(400, ['erro' => 'Corpo inválido.']);
            return;
        }

        [$marca, $modelo, $ano, $cor, $placa, $erros] = self::extrair_e_validar_veiculo($body);
        if (!empty($erros)) {
            self::json(422, ['erro' => implode(' ', $erros)]);
            return;
        }

        try {
            $db = Database::get_instance();

            if (self::placa_em_uso($db, $placa, $id_veiculo)) {
                self::json(409, ['erro' => 'Esta placa já está cadastrada no sistema.']);
                return;
            }

            $afetados = $db->execute(
                'UPDATE veiculos
                    SET marca = :marca, cor = :cor, ano = :ano, modelo = :modelo, placa = :placa
                  WHERE id_veiculo = :id_veiculo AND id_cliente = :id_cliente',
                [
                    ':marca'      => $marca,
                    ':cor'        => $cor,
                    ':ano'        => $ano,
                    ':modelo'     => $modelo,
                    ':placa'      => $placa,
                    ':id_veiculo' => $id_veiculo,
                    ':id_cliente' => $id_cliente,
                ]
            );

            if ($afetados === 0) {
                self::json(404, ['erro' => 'Veículo não encontrado.']);
                return;
            }

            self::json(200, [
                'id_veiculo' => $id_veiculo,
                'marca'      => $marca,
                'cor'        => $cor,
                'ano'        => $ano,
                'modelo'     => $modelo,
                'placa'      => $placa,
            ]);
        } catch (DatabaseException $e) {
            error_log('[ClienteController] atualizar_veiculo: ' . $e->getMessage());
            self::json(503, ['erro' => 'Serviço indisponível.']);
        }
    }

    public static function deletar_veiculo(array $params): void
    {
        self::validar_csrf();

        $id_cliente = self::id_cliente_sessao();
        $id_veiculo = self::validar_id($params['id'] ?? '');

        if ($id_veiculo === false) {
            self::json(400, ['erro' => 'ID inválido.']);
            return;
        }

        try {
            $db = Database::get_instance();

            $afetados = $db->execute(
                'DELETE FROM veiculos WHERE id_veiculo = :id_veiculo AND id_cliente = :id_cliente',
                [':id_veiculo' => $id_veiculo, ':id_cliente' => $id_cliente]
            );

            if ($afetados === 0) {
                self::json(404, ['erro' => 'Veículo não encontrado.']);
                return;
            }

            self::json(200, ['ok' => true]);
        } catch (DatabaseException $e) {
            error_log('[ClienteController] deletar_veiculo: ' . $e->getMessage());
            self::json(503, ['erro' => 'Serviço indisponível.']);
        }
    }

    public static function listar_agendamentos(): void
    {
        $id_cliente = self::id_cliente_sessao();

        try {
            $db = Database::get_instance();
            $agendamentos = $db->query_all(
                'SELECT id, servico, marca, modelo, placa, data_preferida, turno, status, criado_em
                   FROM agendamentos
                  WHERE id_cliente = :id_cliente
                  ORDER BY data_preferida DESC, id DESC',
                [':id_cliente' => $id_cliente]
            );
            self::json(200, $agendamentos);
        } catch (DatabaseException $e) {
            error_log('[ClienteController] listar_agendamentos: ' . $e->getMessage());
            self::json(503, ['erro' => 'Serviço indisponível.']);
        }
    }

    /**
     * @return array{0:string,1:string,2:string,3:string,4:string,5:string[]}
     *         [marca, modelo, ano, cor, placa, erros]
     */
    private static function extrair_e_validar_veiculo(array $data): array
    {
        $marca  = trim($data['marca']  ?? '');
        $modelo = trim($data['modelo'] ?? '');
        $ano    = trim($data['ano']    ?? '');
        $cor    = trim($data['cor']    ?? '');
        $placa  = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $data['placa'] ?? ''));

        $erros = [];

        if (strlen($marca) < 2 || strlen($marca) > 100) {
            $erros[] = 'Marca do veículo inválida.';
        }

        if (strlen($modelo) < 2 || strlen($modelo) > 100) {
            $erros[] = 'Modelo do veículo inválido.';
        }

        if (!preg_match('/^(19|20)\d{2}(\/\d{2,4})?$/', $ano)) {
            $erros[] = 'Ano do veículo inválido.';
        }

        if (strlen($cor) < 2 || strlen($cor) > 50) {
            $erros[] = 'Cor do veículo inválida.';
        }

        if (!self::placa_valida($placa)) {
            $erros[] = 'Placa inválida. Use o formato ABC-1234 ou ABC1D23.';
        }

        return [$marca, $modelo, $ano, $cor, $placa, $erros];
    }

    private static function placa_valida(string $placa): bool
    {
        $old_format      = '/^[A-Z]{3}\d{4}$/';
        $mercosul_format = '/^[A-Z]{3}\d[A-Z]\d{2}$/';
        return (bool) (preg_match($old_format, $placa) || preg_match($mercosul_format, $placa));
    }

    private static function placa_em_uso(Database $db, string $placa, ?int $excluir_id = null): bool
    {
        if ($excluir_id !== null) {
            $row = $db->query_one(
                'SELECT 1 FROM veiculos WHERE placa = :placa AND id_veiculo != :id LIMIT 1',
                [':placa' => $placa, ':id' => $excluir_id]
            );
        } else {
            $row = $db->query_one(
                'SELECT 1 FROM veiculos WHERE placa = :placa LIMIT 1',
                [':placa' => $placa]
            );
        }
        return $row !== null;
    }

    private static function id_cliente_sessao(): int
    {
        return (int) ($_SESSION['cliente_id'] ?? 0);
    }

    private static function validar_id(mixed $raw): int|false
    {
        return filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
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
            self::json(403, ['erro' => 'Token inválido.']);
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