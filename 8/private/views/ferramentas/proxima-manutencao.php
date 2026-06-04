<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$erro = '';
$resultados = [];
$resultados_filtrados = [];

$ordenar = isset($_GET['ordenar']) ? $_GET['ordenar'] : 'codigo';
$direcao = isset($_GET['direcao']) && $_GET['direcao'] == 'desc' ? 'desc' : 'asc';
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';

$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;

if ($pagina < 1) {
    $pagina = 1;
}

$registos_por_pagina = 5;

$colunas_permitidas = [
    'codigo' => 'e.codigo_inventario',
    'equipamento' => 'e.designacao',
    'tipo' => 'm.tipo_manutencao',
    'ultima' => 'm.data_manutencao',
    'proxima' => 'm.proxima_manutencao',
    'fornecedor' => 'f.nome_empresa',
    'responsavel' => 'm.responsavel',
    'custo' => 'm.custo'
];

$coluna_sql = isset($colunas_permitidas[$ordenar])
    ? $colunas_permitidas[$ordenar]
    : 'e.codigo_inventario';

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
                e.id AS equipamento_id,
                e.codigo_inventario,
                e.designacao,
                m.tipo_manutencao,
                m.data_manutencao,
                m.proxima_manutencao,
                m.responsavel,
                m.custo,
                f.nome_empresa
            FROM equipamentos e
            LEFT JOIN manutencoes m
                ON m.equipamento_id = e.id
            LEFT JOIN fornecedores f
                ON m.fornecedor_id = f.id
            ORDER BY $coluna_sql $direcao";

    $stmt = $ligacao->prepare($sql);
    $stmt->execute();

    $resultados = $stmt->fetchAll(PDO::FETCH_OBJ);

} catch (PDOException $err) {

    $erro = 'Aconteceu um erro ao carregar as próximas manutenções.';
}

$ligacao = null;

function calcular_estado_manutencao($registo)
{
    if (empty($registo->tipo_manutencao)) {
        return ['estado' => 'Sem registo', 'classe' => 'secondary'];
    }

    if (empty($registo->proxima_manutencao)) {
        return ['estado' => 'Sem data', 'classe' => 'secondary'];
    }

    $hoje = date('Y-m-d');

    if ($registo->proxima_manutencao < $hoje) {
        return ['estado' => 'Em atraso', 'classe' => 'danger'];
    }

    if ($registo->proxima_manutencao <= date('Y-m-d', strtotime('+30 days'))) {
        return ['estado' => 'Próxima', 'classe' => 'warning'];
    }

    return ['estado' => 'Agendada', 'classe' => 'success'];
}

function link_ordenacao_manutencao($campo, $ordenar, $direcao, $filtro_estado)
{
    $nova_direcao = 'asc';

    if ($ordenar == $campo && $direcao == 'asc') {
        $nova_direcao = 'desc';
    }

    return '?ordenar=' . $campo .
        '&direcao=' . $nova_direcao .
        '&estado=' . urlencode($filtro_estado);
}

function icone_ordenacao_manutencao($campo, $ordenar, $direcao)
{
    if ($ordenar != $campo) {
        return '<i class="fa-solid fa-sort ms-1"></i>';
    }

    if ($direcao == 'asc') {
        return '<i class="fa-solid fa-sort-up ms-1"></i>';
    }

    return '<i class="fa-solid fa-sort-down ms-1"></i>';
}

foreach ($resultados as $registo) {
    $dados_estado = calcular_estado_manutencao($registo);

    if (!empty($filtro_estado) && $dados_estado['estado'] != $filtro_estado) {
        continue;
    }

    $resultados_filtrados[] = $registo;
}

$total_registos = count($resultados_filtrados);
$total_paginas = ceil($total_registos / $registos_por_pagina);
$offset = ($pagina - 1) * $registos_por_pagina;
$resultados_pagina = array_slice($resultados_filtrados, $offset, $registos_por_pagina);

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
                    <i class="fa-solid fa-calendar-check me-2"></i>
                    Próximas Manutenções
                </h2>
            </div>

            <p class="text-muted">
                Consulta, ordenação e filtragem das manutenções associadas aos equipamentos.
            </p>

            <form method="get" class="row mb-3">

                <input type="hidden" name="ordenar" value="<?= htmlspecialchars($ordenar) ?>">
                <input type="hidden" name="direcao" value="<?= htmlspecialchars($direcao) ?>">

                <div class="col-md-4">
                    <select name="estado" class="form-control">
                        <option value="">Todos os estados</option>
                        <option value="Agendada" <?= $filtro_estado == 'Agendada' ? 'selected' : '' ?>>Agendada</option>
                        <option value="Próxima" <?= $filtro_estado == 'Próxima' ? 'selected' : '' ?>>Próxima</option>
                        <option value="Em atraso" <?= $filtro_estado == 'Em atraso' ? 'selected' : '' ?>>Em atraso</option>
                        <option value="Sem registo" <?= $filtro_estado == 'Sem registo' ? 'selected' : '' ?>>Sem registo</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary w-100">
                        Filtrar
                    </button>
                </div>

                <div class="col-md-2">
                    <a href="proxima-manutencao.php" class="btn btn-outline-secondary w-100">
                        Limpar
                    </a>
                </div>

            </form>

            <?php if (!empty($erro)) : ?>

                <div class="alert alert-danger">
                    <?= htmlspecialchars($erro) ?>
                </div>

            <?php elseif ($total_registos == 0) : ?>

                <div class="alert alert-info">
                    Não existem registos para apresentar.
                </div>

            <?php else : ?>

                <p class="text-muted">
                    Total: <?= $total_registos ?> registo(s)
                </p>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">

                        <thead class="table-dark">
                            <tr>
                                <th>
                                    <a href="<?= link_ordenacao_manutencao('codigo', $ordenar, $direcao, $filtro_estado) ?>"
                                       class="text-white text-decoration-none">
                                        Código <?= icone_ordenacao_manutencao('codigo', $ordenar, $direcao) ?>
                                    </a>
                                </th>

                                <th>
                                    <a href="<?= link_ordenacao_manutencao('equipamento', $ordenar, $direcao, $filtro_estado) ?>"
                                       class="text-white text-decoration-none">
                                        Equipamento <?= icone_ordenacao_manutencao('equipamento', $ordenar, $direcao) ?>
                                    </a>
                                </th>

                                <th>
                                    <a href="<?= link_ordenacao_manutencao('tipo', $ordenar, $direcao, $filtro_estado) ?>"
                                       class="text-white text-decoration-none">
                                        Tipo <?= icone_ordenacao_manutencao('tipo', $ordenar, $direcao) ?>
                                    </a>
                                </th>

                                <th>
                                    <a href="<?= link_ordenacao_manutencao('ultima', $ordenar, $direcao, $filtro_estado) ?>"
                                       class="text-white text-decoration-none">
                                        Última <?= icone_ordenacao_manutencao('ultima', $ordenar, $direcao) ?>
                                    </a>
                                </th>

                                <th>
                                    <a href="<?= link_ordenacao_manutencao('proxima', $ordenar, $direcao, $filtro_estado) ?>"
                                       class="text-white text-decoration-none">
                                        Próxima <?= icone_ordenacao_manutencao('proxima', $ordenar, $direcao) ?>
                                    </a>
                                </th>

                                <th>
                                    <a href="<?= link_ordenacao_manutencao('fornecedor', $ordenar, $direcao, $filtro_estado) ?>"
                                       class="text-white text-decoration-none">
                                        Fornecedor <?= icone_ordenacao_manutencao('fornecedor', $ordenar, $direcao) ?>
                                    </a>
                                </th>

                                <th>
                                    <a href="<?= link_ordenacao_manutencao('responsavel', $ordenar, $direcao, $filtro_estado) ?>"
                                       class="text-white text-decoration-none">
                                        Responsável <?= icone_ordenacao_manutencao('responsavel', $ordenar, $direcao) ?>
                                    </a>
                                </th>

                                <th>
                                    <a href="<?= link_ordenacao_manutencao('custo', $ordenar, $direcao, $filtro_estado) ?>"
                                       class="text-white text-decoration-none">
                                        Custo <?= icone_ordenacao_manutencao('custo', $ordenar, $direcao) ?>
                                    </a>
                                </th>

                                <th>Estado</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($resultados_pagina as $manutencao) : ?>

                                <?php
                                $dados_estado = calcular_estado_manutencao($manutencao);
                                $estado = $dados_estado['estado'];
                                $classe = $dados_estado['classe'];
                                ?>

                                <tr>
                                    <td><?= htmlspecialchars($manutencao->codigo_inventario) ?></td>

                                    <td><?= htmlspecialchars($manutencao->designacao) ?></td>

                                    <td><?= htmlspecialchars($manutencao->tipo_manutencao ?? '-') ?></td>

                                    <td>
                                        <?= !empty($manutencao->data_manutencao)
                                            ? date('d/m/Y', strtotime($manutencao->data_manutencao))
                                            : '-' ?>
                                    </td>

                                    <td>
                                        <?= !empty($manutencao->proxima_manutencao)
                                            ? date('d/m/Y', strtotime($manutencao->proxima_manutencao))
                                            : '-' ?>
                                    </td>

                                    <td>
                                        <?= !empty($manutencao->nome_empresa)
                                            ? htmlspecialchars($manutencao->nome_empresa)
                                            : '-' ?>
                                    </td>

                                    <td><?= htmlspecialchars($manutencao->responsavel ?? '-') ?></td>

                                    <td>
                                        <?= !empty($manutencao->custo)
                                            ? number_format($manutencao->custo, 2, ',', '.') . ' €'
                                            : '-' ?>
                                    </td>

                                    <td>
                                        <span class="badge bg-<?= $classe ?>">
                                            <?= $estado ?>
                                        </span>
                                    </td>
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
                                           href="?pagina=<?= $i ?>&ordenar=<?= urlencode($ordenar) ?>&direcao=<?= urlencode($direcao) ?>&estado=<?= urlencode($filtro_estado) ?>">
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