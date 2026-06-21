<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

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
    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST . ";dbname=" . MYSQL_DATABASE . ";charset=utf8mb4",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );
    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $ligacao->prepare(
        "SELECT
            e.codigo_inventario,
            e.designacao,
            e.categoria,
            e.marca,
            e.modelo,
            e.numero_serie,
            e.fabricante,
            e.data_aquisicao,
            e.ano_fabrico,
            e.custo_aquisicao,
            e.estado,
            e.criticidade,
            l.edificio,
            l.piso,
            l.servico,
            l.sala,
            f.nome_empresa
         FROM equipamentos e
         LEFT JOIN localizacoes l ON e.localizacao_id = l.id
         LEFT JOIN fornecedores f ON e.fornecedor_id = f.id
         WHERE e.estado <> 'Inativo'
         ORDER BY e.codigo_inventario ASC"
    );
    $stmt->execute();
    $equipamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $colunas = [
        'Código', 'Designação', 'Categoria', 'Marca', 'Modelo', 'Número de Série',
        'Fabricante', 'Data de Aquisição', 'Ano de Fabrico', 'Custo de Aquisição',
        'Estado', 'Criticidade', 'Edifício', 'Piso', 'Serviço', 'Sala', 'Fornecedor'
    ];

    $linhas = [];
    foreach ($equipamentos as $equipamento) {
        $linhas[] = [
            $equipamento['codigo_inventario'],
            $equipamento['designacao'],
            $equipamento['categoria'],
            $equipamento['marca'],
            $equipamento['modelo'],
            $equipamento['numero_serie'],
            $equipamento['fabricante'],
            $equipamento['data_aquisicao'],
            $equipamento['ano_fabrico'],
            $equipamento['custo_aquisicao'],
            $equipamento['estado'],
            $equipamento['criticidade'],
            $equipamento['edificio'],
            $equipamento['piso'],
            $equipamento['servico'],
            $equipamento['sala'],
            $equipamento['nome_empresa']
        ];
    }

    $formato = $_GET['formato'] ?? 'excel';
    if ($formato === 'csv') {
        enviar_csv('equipamentos', $colunas, $linhas);
    }
    if ($formato === 'imprimir') {
        enviar_impressao('Exportação de Equipamentos', $colunas, $linhas);
    }
    enviar_excel('equipamentos', 'Exportação de Equipamentos', $colunas, $linhas);
} catch (PDOException $err) {
    echo 'Não foi possível exportar os equipamentos.';
    exit;
}

?>

