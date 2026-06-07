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
                gc.tipo_contrato,
                gc.entidade_responsavel,
                gc.data_inicio,
                gc.data_fim,
                gc.periodicidade
            FROM equipamentos e
            LEFT JOIN garantias_contratos gc
                ON gc.equipamento_id = e.id
            ORDER BY e.codigo_inventario";

    $stmt = $ligacao->prepare($sql);
    $stmt->execute();

    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (ob_get_length()) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="garantias_contratos.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    fwrite($output, "\xEF\xBB\xBF");

    fputcsv($output, [
        'Código',
        'Equipamento',
        'Tipo de Contrato',
        'Entidade',
        'Data Início',
        'Data Fim',
        'Periodicidade',
        'Estado'
    ], ';');

    foreach ($dados as $linha) {

        $estado = 'Sem registo';

        if (!empty($linha['tipo_contrato'])) {

            if (!empty($linha['data_fim'])) {

                $hoje = date('Y-m-d');

                if ($linha['data_fim'] < $hoje) {
                    $estado = 'Expirado';
                }
                elseif ($linha['data_fim'] <= date('Y-m-d', strtotime('+30 days'))) {
                    $estado = 'A expirar';
                }
                else {
                    $estado = 'Ativo';
                }

            } else {

                $estado = 'Sem data';
            }
        }

        fputcsv($output, [
            $linha['codigo_inventario'],
            $linha['designacao'],
            $linha['tipo_contrato'],
            $linha['entidade_responsavel'],
            $linha['data_inicio'],
            $linha['data_fim'],
            $linha['periodicidade'],
            $estado
        ], ';');
    }

    fclose($output);
    exit;

} catch (PDOException $err) {

    echo 'Não foi possível exportar as garantias e contratos.';
    exit;
}