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

    $stmt = $ligacao->prepare(
        "SELECT
            l.edificio,
            l.piso,
            l.servico,
            l.sala,
            l.observacoes,
            COUNT(e.id) AS total_equipamentos
         FROM localizacoes l
         LEFT JOIN equipamentos e ON e.localizacao_id = l.id
         GROUP BY l.id
         ORDER BY l.edificio, l.piso, l.servico, l.sala"
    );
    $stmt->execute();
    $localizacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=localizacoes.csv');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    fputcsv($output, [
        'Edifício',
        'Piso',
        'Serviço / Departamento',
        'Sala',
        'N.º Equipamentos',
        'Observações'
    ], ';');

    foreach ($localizacoes as $localizacao) {
        fputcsv($output, [
            $localizacao['edificio'],
            $localizacao['piso'],
            $localizacao['servico'],
            $localizacao['sala'],
            $localizacao['total_equipamentos'],
            $localizacao['observacoes']
        ], ';');
    }

    fclose($output);
    exit;
} catch (PDOException $err) {
    echo 'Não foi possível exportar as localizações.';
    exit;
}

?>
