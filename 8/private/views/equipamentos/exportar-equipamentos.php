<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

try {

    /*
        Ligação à base de dados.
    */
    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST .
            ";dbname=" . MYSQL_DATABASE .
            ";charset=utf8",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );

    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    /*
        Consulta dos equipamentos para exportação.
    */
    $sql = "SELECT
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
            LEFT JOIN localizacoes l
                ON e.localizacao_id = l.id
            LEFT JOIN fornecedores f
                ON e.fornecedor_id = f.id
            ORDER BY e.codigo_inventario ASC";

    $stmt = $ligacao->prepare($sql);
    $stmt->execute();

    $equipamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /*
        Cabeçalhos para obrigar o browser a descarregar o ficheiro CSV.
        O Excel abre este ficheiro normalmente.
    */
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=equipamentos.csv');

    /*
        Abre a saída do PHP como ficheiro.
    */
    $output = fopen('php://output', 'w');

    /*
        BOM UTF-8 para o Excel reconhecer acentos corretamente.
    */
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    /*
        Cabeçalho das colunas.
    */
    fputcsv($output, [
        'Código',
        'Designação',
        'Categoria',
        'Marca',
        'Modelo',
        'Número de Série',
        'Fabricante',
        'Data de Aquisição',
        'Ano de Fabrico',
        'Custo de Aquisição',
        'Estado',
        'Criticidade',
        'Edifício',
        'Piso',
        'Serviço',
        'Sala',
        'Fornecedor'
    ], ';');

    /*
        Linhas com os dados dos equipamentos.
    */
    foreach ($equipamentos as $equipamento) {

        fputcsv($output, [
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
        ], ';');
    }

    fclose($output);
    exit;

} catch (PDOException $err) {

    echo 'Não foi possível exportar os equipamentos.';
    exit;
}

?>