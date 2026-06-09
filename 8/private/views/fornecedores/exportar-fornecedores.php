<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

try {
    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST . ";dbname=" . MYSQL_DATABASE . ";charset=utf8",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );
    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $ligacao->query(
        "SELECT
            f.nome_empresa,
            f.nif,
            f.telefone,
            f.email,
            f.morada,
            f.website,
            f.pessoa_contacto,
            f.telefone_contacto,
            f.tipo_fornecedor,
            COUNT(e.id) AS total_equipamentos,
            f.observacoes
         FROM fornecedores f
         LEFT JOIN equipamentos e ON e.fornecedor_id = f.id
         GROUP BY f.id
         ORDER BY f.nome_empresa"
    );

    $fornecedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=fornecedores.csv');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    fputcsv($output, [
        'Empresa',
        'NIF',
        'Telefone',
        'Email',
        'Morada',
        'Website',
        'Pessoa de Contacto',
        'Telefone de Contacto',
        'Tipo de Fornecedor',
        'N.º Equipamentos',
        'Observações'
    ], ';');

    foreach ($fornecedores as $fornecedor) {
        fputcsv($output, [
            $fornecedor['nome_empresa'],
            $fornecedor['nif'],
            $fornecedor['telefone'],
            $fornecedor['email'],
            $fornecedor['morada'],
            $fornecedor['website'],
            $fornecedor['pessoa_contacto'],
            $fornecedor['telefone_contacto'],
            $fornecedor['tipo_fornecedor'],
            $fornecedor['total_equipamentos'],
            $fornecedor['observacoes']
        ], ';');
    }

    fclose($output);
    exit;
} catch (PDOException $err) {
    echo 'Não foi possível exportar os fornecedores.';
    exit;
}

?>
