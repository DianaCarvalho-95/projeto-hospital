<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

try {

    /* Ligação à base de dados */
    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST .
            ";dbname=" . MYSQL_DATABASE .
            ";charset=utf8",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );

    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    /* Consulta dos fornecedores para exportação */
    $stmt = $ligacao->query(
        "SELECT
            nome_empresa,
            nif,
            telefone,
            email,
            morada,
            website,
            pessoa_contacto,
            telefone_contacto,
            tipo_fornecedor,
            observacoes
         FROM fornecedores
         ORDER BY nome_empresa"
    );

    $fornecedores = $stmt->fetchAll(PDO::FETCH_ASSOC);


    /* Cabeçalhos para descarregar o ficheiro CSV */
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=fornecedores.csv');


    /* Abre a saída do PHP como ficheiro */
    $output = fopen('php://output', 'w');


    /* BOM UTF-8 para o Excel reconhecer acentos */
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));


    /* Cabeçalho das colunas */
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
        'Observações'
    ], ';');


    /* Linhas dos fornecedores */
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