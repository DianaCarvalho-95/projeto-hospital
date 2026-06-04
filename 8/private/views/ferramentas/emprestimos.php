<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$erro = '';
$resultados = [];

$ordenar = isset($_GET['ordenar']) ? $_GET['ordenar'] : 'data';
$direcao = isset($_GET['direcao']) && $_GET['direcao'] == 'asc' ? 'asc' : 'desc';

$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;

if ($pagina < 1) {
    $pagina = 1;
}

$registos_por_pagina = 5;

$colunas_permitidas = [
    'codigo' => 'e.codigo_inventario',
    'equipamento' => 'e.designacao',
    'origem' => 'ep.servico_origem',
    'destino' => 'ep.servico_destino',
    'data' => 'ep.data_emprestimo',
    'prevista' => 'ep.data_prevista_devolucao',
    'devolucao' => 'ep.data_devolucao',
    'responsavel' => 'ep.responsavel',
    'estado' => 'ep.estado'
];

$coluna_sql = isset($colunas_permitidas[$ordenar])
    ? $colunas_permitidas[$ordenar]
    : 'ep.data_emprestimo';

try {

    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST .
            ";dbname=" . MYSQL_DATABASE .
            ";charset=utf8",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );

    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    /*
        Consulta dos empréstimos entre serviços.
        Junta a tabela emprestimos com equipamentos para apresentar
        o código e a designação do equipamento.
    */
    $sql = "SELECT
                ep.*,
                e.codigo_inventario,
                e.designacao
            FROM emprestimos ep
            INNER JOIN equipamentos e
                ON ep.equipamento_id = e.id
            ORDER BY $coluna_sql $direcao";

    $stmt = $ligacao->prepare($sql);
    $stmt->execute();

    $resultados = $stmt->fetchAll(PDO::FETCH_OBJ);

} catch (PDOException $err) {

    $erro = 'Aconteceu um erro ao carregar os empréstimos entre serviços.';
}

$ligacao = null;

/*
    Define a cor da badge consoante o estado do empréstimo.
*/
function classe_estado_emprestimo($estado)
{
    if ($estado == 'Devolvido') {
        return 'success';
    }

    if ($estado == 'Em atraso') {
        return 'danger';
    }

    if ($estado == 'Ativo') {
        return 'warning';
    }

    return 'secondary';
}

/*
    Gera os links de ordenação das colunas.
*/
function link_ordenacao_emprestimos($campo, $ordenar, $direcao)
{
    $nova_direcao = 'asc';

    if ($ordenar == $campo && $direcao == 'asc') {
        $nova_direcao = 'desc';
    }

    return '?ordenar=' . $campo .
        '&direcao=' . $nova_direcao;
}

/*
    Mostra o ícone de ordenação correto.
*/
function icone_ordenacao_emprestimos($campo, $ordenar, $direcao)
{
    if ($ordenar != $campo) {
        return '<i class="fa-solid fa-sort ms-1"></i>';
    }

    if ($direcao == 'asc') {
        return '<i class="fa-solid fa-sort-up ms-1"></i>';
    }

    return '<i class="fa-solid fa-sort-down ms-1"></i>';
}

/*
    Paginação: mostra 5 registos por página.
*/
$total_registos = count($resultados);
$total_paginas = ceil($total_registos / $registos_por_pagina);
$offset = ($pagina - 1) * $registos_por_pagina;
$resultados_pagina = array_slice($resultados, $offset, $registos_por_pagina);

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<style>
    .pagination-wrapper {
        display: flex;
        justify-content: center;
        margin-top: 22px;
        margin-bottom: 14px;
    }

    .pagination .page-link {
        color: #0d6efd;
        border-radius: 8px;
        margin: 0 2px;
    }

    .pagination .page-item.active .page-link {
        background-color: #0d6efd;
        border-color: #0d6efd;
        color: #fff;
    }

    .btn-voltar-wrapper {
        display: flex;
        justify-content: center;
        margin-top: 8px;
    }

    .btn-voltar-custom {
        background: #6c757d;
        color: #fff;
        border-radius: 8px;
        padding: 9px 22px;
        text-decoration: none;
        box-shadow: 0 6px 14px rgba(0, 0, 0, 0.15);
    }

    .btn-voltar-custom:hover {
        background: #5c636a;
        color: #fff;
    }
</style>

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 p-4">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="mb-0">
                    <i class="fa-solid fa-handshake me-2"></i>
                    Empréstimos entre Serviços
                </h2>
            </div>

            <p class="text-muted">
                Controlo de equipamentos emprestados entre serviços hospitalares.
            </p>

            <?php if (!empty($erro)) : ?>

                <div class="alert alert-danger">
                    <?= htmlspecialchars($erro) ?>
                </div>

            <?php elseif ($total_registos == 0) : ?>

                <div class="alert alert-info">
                    Não existem empréstimos para apresentar.
                </div>

            <?php else : ?>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">

                        <thead class="table-dark">
                            <tr>
                                <th>
                                    <a href="<?= link_ordenacao_emprestimos('codigo', $ordenar, $direcao) ?>"
                                       class="text-white text-decoration-none">
                                        Código <?= icone_ordenacao_emprestimos('codigo', $ordenar, $direcao) ?>
                                    </a>
                                </th>

                                <th>
                                    <a href="<?= link_ordenacao_emprestimos('equipamento', $ordenar, $direcao) ?>"
                                       class="text-white text-decoration-none">
                                        Equipamento <?= icone_ordenacao_emprestimos('equipamento', $ordenar, $direcao) ?>
                                    </a>
                                </th>

                                <th>
                                    <a href="<?= link_ordenacao_emprestimos('origem', $ordenar, $direcao) ?>"
                                       class="text-white text-decoration-none">
                                        Origem <?= icone_ordenacao_emprestimos('origem', $ordenar, $direcao) ?>
                                    </a>
                                </th>

                                <th>
                                    <a href="<?= link_ordenacao_emprestimos('destino', $ordenar, $direcao) ?>"
                                       class="text-white text-decoration-none">
                                        Destino <?= icone_ordenacao_emprestimos('destino', $ordenar, $direcao) ?>
                                    </a>
                                </th>

                                <th>
                                    <a href="<?= link_ordenacao_emprestimos('data', $ordenar, $direcao) ?>"
                                       class="text-white text-decoration-none">
                                        Empréstimo <?= icone_ordenacao_emprestimos('data', $ordenar, $direcao) ?>
                                    </a>
                                </th>

                                <th>
                                    <a href="<?= link_ordenacao_emprestimos('prevista', $ordenar, $direcao) ?>"
                                       class="text-white text-decoration-none">
                                        Prev. Devolução <?= icone_ordenacao_emprestimos('prevista', $ordenar, $direcao) ?>
                                    </a>
                                </th>

                                <th>
                                    <a href="<?= link_ordenacao_emprestimos('devolucao', $ordenar, $direcao) ?>"
                                       class="text-white text-decoration-none">
                                        Devolução <?= icone_ordenacao_emprestimos('devolucao', $ordenar, $direcao) ?>
                                    </a>
                                </th>

                                <th>
                                    <a href="<?= link_ordenacao_emprestimos('responsavel', $ordenar, $direcao) ?>"
                                       class="text-white text-decoration-none">
                                        Responsável <?= icone_ordenacao_emprestimos('responsavel', $ordenar, $direcao) ?>
                                    </a>
                                </th>

                                <th>
                                    <a href="<?= link_ordenacao_emprestimos('estado', $ordenar, $direcao) ?>"
                                       class="text-white text-decoration-none">
                                        Estado <?= icone_ordenacao_emprestimos('estado', $ordenar, $direcao) ?>
                                    </a>
                                </th>

                                <th>Observações</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($resultados_pagina as $emprestimo) : ?>

                                <tr>
                                    <td><?= htmlspecialchars($emprestimo->codigo_inventario) ?></td>

                                    <td><?= htmlspecialchars($emprestimo->designacao) ?></td>

                                    <td><?= htmlspecialchars($emprestimo->servico_origem) ?></td>

                                    <td><?= htmlspecialchars($emprestimo->servico_destino) ?></td>

                                    <td>
                                        <?= !empty($emprestimo->data_emprestimo)
                                            ? date('d/m/Y', strtotime($emprestimo->data_emprestimo))
                                            : '-' ?>
                                    </td>

                                    <td>
                                        <?= !empty($emprestimo->data_prevista_devolucao)
                                            ? date('d/m/Y', strtotime($emprestimo->data_prevista_devolucao))
                                            : '-' ?>
                                    </td>

                                    <td>
                                        <?= !empty($emprestimo->data_devolucao)
                                            ? date('d/m/Y', strtotime($emprestimo->data_devolucao))
                                            : '-' ?>
                                    </td>

                                    <td><?= htmlspecialchars($emprestimo->responsavel ?? '-') ?></td>

                                    <td>
                                        <span class="badge bg-<?= classe_estado_emprestimo($emprestimo->estado) ?>">
                                            <?= htmlspecialchars($emprestimo->estado) ?>
                                        </span>
                                    </td>

                                    <td><?= htmlspecialchars($emprestimo->observacoes ?? '-') ?></td>
                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>
                </div>

                <?php if ($total_paginas > 1) : ?>

                    <div class="pagination-wrapper">
                        <nav>
                            <ul class="pagination pagination-sm mb-0">

                                <?php for ($i = 1; $i <= $total_paginas; $i++) : ?>

                                    <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                                        <a class="page-link"
                                           href="?pagina=<?= $i ?>&ordenar=<?= urlencode($ordenar) ?>&direcao=<?= urlencode($direcao) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>

                                <?php endfor; ?>

                            </ul>
                        </nav>
                    </div>

                <?php endif; ?>

                <div class="btn-voltar-wrapper">
                    <a href="ferramentas.php" class="btn-voltar-custom">
                        <i class="fa-solid fa-arrow-left me-1"></i>
                        Voltar
                    </a>
                </div>

            <?php endif; ?>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>