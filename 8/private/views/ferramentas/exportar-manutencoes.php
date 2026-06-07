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
                m.data_manutencao,
                m.proxima_manutencao,
                m.responsavel,
                COALESCE(f.nome_empresa,'Sem fornecedor') AS fornecedor
            FROM equipamentos e
            LEFT JOIN manutencoes m
                ON m.equipamento_id = e.id
            LEFT JOIN fornecedores f
                ON m.fornecedor_id = f.id
            ORDER BY m.proxima_manutencao ASC";

    $stmt = $ligacao->prepare($sql);
    $stmt->execute();

    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (ob_get_length()) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="proximas_manutencoes.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    fwrite($output, "\xEF\xBB\xBF");

    fputcsv($output, [
        'Código',
        'Equipamento',
        'Tipo de Manutenção',
        'Última Manutenção',
        'Próxima Manutenção',
        'Fornecedor',
        'Responsável',
        'Estado'
    ], ';');

    foreach ($dados as $linha) {

        $estado = 'Sem registo';

        if (!empty($linha['proxima_manutencao'])) {

            $hoje = date('Y-m-d');

            if ($linha['proxima_manutencao'] < $hoje) {
                $estado = 'Em atraso';
            }
            elseif ($linha['proxima_manutencao'] <= date('Y-m-d', strtotime('+30 days'))) {
                $estado = 'Próxima';
            }
            else {
                $estado = 'Agendada';
            }
        }

        fputcsv($output, [
            $linha['codigo_inventario'],
            $linha['designacao'],
            $linha['tipo_manutencao'],
            $linha['data_manutencao'],
            $linha['proxima_manutencao'],
            $linha['fornecedor'],
            $linha['responsavel'],
            $estado
        ], ';');
    }

    fclose($output);
    exit;

} catch (PDOException $err) {

    echo 'Não foi possível exportar as manutenções.';
    exit;
}