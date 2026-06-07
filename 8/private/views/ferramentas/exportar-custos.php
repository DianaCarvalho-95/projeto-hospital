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
                e.codigo_inventario,
                e.designacao,
                m.tipo_manutencao,
                COALESCE(f.nome_empresa, 'Sem fornecedor') AS fornecedor,
                m.data_manutencao,
                m.responsavel,
                m.custo
            FROM manutencoes m
            INNER JOIN equipamentos e
                ON m.equipamento_id = e.id
            LEFT JOIN fornecedores f
                ON m.fornecedor_id = f.id
            ORDER BY m.data_manutencao DESC";

    $stmt = $ligacao->prepare($sql);
    $stmt->execute();

    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    
    /* Limpa qualquer saída anterior para não impedir os headers */
    if (ob_get_length()) {
        ob_end_clean();
    }


    /* Cabeçalhos para download do ficheiro CSV */
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="estimativa_custos.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');


    /* BOM UTF-8 para o Excel reconhecer acentos */
    fwrite($output, "\xEF\xBB\xBF");


    /* Cabeçalho das colunas */
    fputcsv($output, [
        'Código',
        'Equipamento',
        'Tipo de Manutenção',
        'Fornecedor',
        'Data da Manutenção',
        'Responsável',
        'Custo'
    ], ';');


    /* Dados */
    foreach ($dados as $linha) {

        fputcsv($output, [
            $linha['codigo_inventario'],
            $linha['designacao'],
            $linha['tipo_manutencao'],
            $linha['fornecedor'],
            $linha['data_manutencao'],
            $linha['responsavel'],
            number_format((float)$linha['custo'], 2, ',', '.')
        ], ';');
    }

    fclose($output);
    exit;

} catch (PDOException $err) {

    echo 'Não foi possível exportar os custos.';
    exit;
}