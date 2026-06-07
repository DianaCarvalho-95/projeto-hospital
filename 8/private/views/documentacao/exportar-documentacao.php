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
                d.*,
                e.codigo_inventario,
                e.designacao,
                COALESCE(f.nome_empresa, 'Sem fornecedor') AS nome_empresa
            FROM documentacao d
            INNER JOIN equipamentos e
                ON d.equipamento_id = e.id
            LEFT JOIN fornecedores f
                ON d.fornecedor_id = f.id
            ORDER BY d.nome_documento";

    $stmt = $ligacao->prepare($sql);
    $stmt->execute();

    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (ob_get_length()) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="documentacao.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    fwrite($output, "\xEF\xBB\xBF");

    fputcsv($output, [
        'Nome Documento',
        'Tipo Documento',
        'Código Equipamento',
        'Equipamento',
        'Fornecedor',
        'Data Documento',
        'Data Validade',
        'Observações'
    ], ';');

    foreach ($dados as $linha) {

        fputcsv($output, [
            $linha['nome_documento'] ?? '',
            $linha['tipo_documento'] ?? '',
            $linha['codigo_inventario'] ?? '',
            $linha['designacao'] ?? '',
            $linha['nome_empresa'] ?? 'Sem fornecedor',
            $linha['data_documento'] ?? '',
            $linha['data_validade'] ?? '',
            $linha['observacoes'] ?? ''
        ], ';');
    }

    fclose($output);
    exit;

} catch (PDOException $err) {

    echo 'Erro ao exportar: ' . $err->getMessage();
    exit;
}

?>