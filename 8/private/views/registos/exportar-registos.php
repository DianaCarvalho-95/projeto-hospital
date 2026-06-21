<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

if (($_SESSION['profile'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . '/private/views/dashboard/dashboard.php');
    exit;
}

function export_h($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function enviar_csv($nome_ficheiro, $colunas, $linhas)
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $nome_ficheiro . '.csv');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($output, $colunas, ';');
    foreach ($linhas as $linha) {
        fputcsv($output, $linha, ';');
    }
    fclose($output);
    exit;
}

function enviar_excel($nome_ficheiro, $titulo, $colunas, $linhas)
{
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $nome_ficheiro . '.xls');
    echo "\xEF\xBB\xBF";
    echo '<table border="1"><thead><tr><th colspan="' . count($colunas) . '">' . export_h($titulo) . '</th></tr><tr>';
    foreach ($colunas as $coluna) {
        echo '<th>' . export_h($coluna) . '</th>';
    }
    echo '</tr></thead><tbody>';
    foreach ($linhas as $linha) {
        echo '<tr>';
        foreach ($linha as $valor) {
            echo '<td>' . export_h($valor) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table>';
    exit;
}

function enviar_impressao($titulo, $colunas, $linhas)
{
    ?>
    <!doctype html>
    <html lang="pt">
    <head>
        <meta charset="utf-8">
        <title><?= export_h($titulo) ?></title>
        <link rel="stylesheet" href="/PROJETO-HOSPITAL/8/private/assets/css/1232099.css?v=20260617-export-print">
    </head>
    <body class="export-print-page">
        <div class="export-print-top">
            <h1 class="export-print-title"><?= export_h($titulo) ?></h1>
            <button class="export-print-btn" onclick="window.print()">Guardar em PDF / Imprimir</button>
        </div>
        <table class="export-print-table">
            <thead><tr><?php foreach ($colunas as $coluna) : ?><th><?= export_h($coluna) ?></th><?php endforeach; ?></tr></thead>
            <tbody>
                <?php foreach ($linhas as $linha) : ?>
                    <tr><?php foreach ($linha as $valor) : ?><td><?= export_h($valor) ?></td><?php endforeach; ?></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    exit;
}

try {
    $pesquisa = isset($_GET['pesquisa']) ? trim($_GET['pesquisa']) : '';
    $modulo = isset($_GET['modulo']) ? trim($_GET['modulo']) : '';
    $acao = isset($_GET['acao']) ? trim($_GET['acao']) : '';

    $where = [];
    $params = [];
    if ($pesquisa !== '') {
        $where[] = '(utilizador LIKE :pesquisa OR descricao LIKE :pesquisa OR entidade LIKE :pesquisa)';
        $params[':pesquisa'] = '%' . $pesquisa . '%';
    }
    if ($modulo !== '') {
        $where[] = 'modulo = :modulo';
        $params[':modulo'] = $modulo;
    }
    if ($acao !== '') {
        $where[] = 'acao = :acao';
        $params[':acao'] = $acao;
    }
    $where_sql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST . ";port=" . MYSQL_PORT . ";dbname=" . MYSQL_DATABASE . ";charset=utf8mb4",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );
    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $ligacao->prepare(
        "SELECT data_evento, utilizador, perfil, modulo, acao, entidade, entidade_id, descricao, ip
         FROM eventos_logs
         $where_sql
         ORDER BY data_evento DESC, id DESC"
    );
    foreach ($params as $chave => $valor) {
        $stmt->bindValue($chave, $valor);
    }
    $stmt->execute();
    $registos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $colunas = ['Data', 'Utilizador', 'Perfil', 'Módulo', 'Ação', 'Entidade', 'ID', 'Descrição', 'IP'];
    $linhas = [];
    foreach ($registos as $registo) {
        $linhas[] = [
            date('d/m/Y H:i', strtotime($registo['data_evento'])),
            $registo['utilizador'] ?: 'Sistema',
            $registo['perfil'] ?: 'Sem perfil',
            $registo['modulo'],
            $registo['acao'],
            $registo['entidade'] ?: '-',
            $registo['entidade_id'] ?: '-',
            $registo['descricao'] ?: '-',
            $registo['ip'] ?: '-'
        ];
    }

    $formato = $_GET['formato'] ?? 'excel';
    if ($formato === 'csv') {
        enviar_csv('registos-eventos', $colunas, $linhas);
    }
    if ($formato === 'imprimir') {
        enviar_impressao('Exportação de Registos de Eventos', $colunas, $linhas);
    }
    enviar_excel('registos-eventos', 'Exportação de Registos de Eventos', $colunas, $linhas);
} catch (PDOException $err) {
    echo 'Não foi possível exportar os registos.';
    exit;
}

?>


