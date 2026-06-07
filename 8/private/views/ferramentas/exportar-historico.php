<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

try {

    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST .
        ";dbname=" . MYSQL_DATABASE .
        ";charset=utf8",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );

    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT
                mv.*,
                e.codigo_inventario,
                e.designacao
            FROM movimentacoes mv
            INNER JOIN equipamentos e
                ON mv.equipamento_id = e.id
            ORDER BY mv.data_movimentacao DESC";

    $stmt = $ligacao->prepare($sql);
    $stmt->execute();

    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (ob_get_length()) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="historico_movimentacoes.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    fwrite($output, "\xEF\xBB\xBF");

    fputcsv($output, [
        'Código',
        'Equipamento',
        'Origem',
        'Destino',
        'Data',
        'Responsável',
        'Motivo',
        'Observações'
    ], ';');

    foreach ($dados as $linha) {

        fputcsv($output, [
            $linha['codigo_inventario'] ?? '',
            $linha['designacao'] ?? '',
            $linha['local_origem'] ?? '',
            $linha['local_destino'] ?? '',
            $linha['data_movimentacao'] ?? '',
            $linha['responsavel'] ?? '',
            $linha['motivo'] ?? '',
            $linha['observacoes'] ?? ''
        ], ';');
    }

    fclose($output);
    exit;

} catch (PDOException $err) {

    echo 'Não foi possível exportar o histórico.';
    exit;
}