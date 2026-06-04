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
    'tipo' => 'gc.tipo_contrato',
    'entidade' => 'gc.entidade_responsavel',
    'inicio' => 'gc.data_inicio',
    'fim' => 'gc.data_fim',
    'periodicidade' => 'gc.periodicidade'
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
                gc.tipo_contrato,
                gc.entidade_responsavel,
                gc.data_inicio,
                gc.data_fim,
                gc.periodicidade,
                gc.existe_contrato
            FROM equipamentos e
            LEFT JOIN garantias_contratos gc
                ON gc.equipamento_id = e.id
            ORDER BY $coluna_sql $direcao";

    $stmt = $ligacao->prepare($sql);
    $stmt->execute();

    $resultados = $stmt->fetchAll(PDO::FETCH_OBJ);

} catch (PDOException $err) {

    $erro = 'Aconteceu um erro ao carregar as garantias e contratos.';
}

$ligacao = null;

function calcular_estado($registo)
{
    if (empty($registo->tipo_contrato)) {
        return ['estado' => 'Sem registo', 'classe' => 'secondary'];
    }

    if (!empty($registo->data_fim)) {
        $hoje = date('Y-m-d');

        if ($registo->data_fim < $hoje) {
            return ['estado' => 'Expirado', 'classe' => 'danger'];
        }

        if ($registo->data_fim <= date('Y-m-d', strtotime('+30 days'))) {
            return ['estado' => 'A expirar', 'classe' => 'warning'];
        }

        return ['estado' => 'Ativo', 'classe' => 'success'];
    }

    return ['estado' => 'Sem data', 'classe' => 'secondary'];
}

function link_ordenacao($campo, $ordenar, $direcao, $filtro_estado)
{
    $nova_direcao = 'asc';

    if ($ordenar == $campo && $direcao == 'asc') {
        $nova_direcao = 'desc';
    }

    return '?ordenar=' . $campo .
        '&direcao=' . $nova_direcao .
        '&estado=' . urlencode($filtro_estado);
}

function icone_ordenacao($campo, $ordenar, $direcao)
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
    $dados_estado = calcular_estado($registo);

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

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 p-4">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="mb-0">
                    <i class="fa-solid fa-file-signature me-2"></i>
                    Garantias e Contratos
                </h2>
            </div>

            <p class="text-muted">
                Consulta, ordenação e filtragem de garantias e contratos associados aos equipamentos.
            </p>

            <form method="get" class="row mb-3">

                <input type="hidden" name="ordenar" value="<?= htmlspecialchars($ordenar) ?>">
                <input type="hidden" name="direcao" value="<?= htmlspecialchars($direcao) ?>">

                <div class="col-md-4">
                    <select name="estado" class="form-control">
                        <option value="">Todos os estados</option>
                        <option value="Ativo" <?= $filtro_estado == 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                        <option value="A expirar" <?= $filtro_estado == 'A expirar' ? 'selected' : '' ?>>A expirar</option>
                        <option value="Expirado" <?= $filtro_estado == 'Expirado' ? 'selected' : '' ?>>Expirado</option>
                        <option value="Sem registo" <?= $filtro_estado == 'Sem registo' ? 'selected' : '' ?>>Sem registo</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary w-100">
                        Filtrar
                    </button>
                </div>

                <div class="col-md-2">
                    <a href="garantias-contratos.php" class="btn btn-outline-secondary w-100">
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
                                    <a href="<?= link_ordenacao('codigo', $ordenar, $direcao, $filtro_estado) ?>"
                                       class="text-white text-decoration-none">
                                        Código <?= icone_ordenacao('codigo', $ordenar, $direcao) ?>
                                    </a>
                                </th>

                                <th>
                                    <a href="<?= link_ordenacao('equipamento', $ordenar, $direcao, $filtro_estado) ?>"
                                       class="text-white text-decoration-none">
                                        Equipamento <?= icone_ordenacao('equipamento', $ordenar, $direcao) ?>
                                    </a>
                                </th>

                                <th>
                                    <a href="<?= link_ordenacao('tipo', $ordenar, $direcao, $filtro_estado) ?>"
                                       class="text-white text-decoration-none">
                                        Tipo de Contrato <?= icone_ordenacao('tipo', $ordenar, $direcao) ?>
                                    </a>
                                </th>

                                <th>
                                    <a href="<?= link_ordenacao('entidade', $ordenar, $direcao, $filtro_estado) ?>"
                                       class="text-white text-decoration-none">
                                        Entidade <?= icone_ordenacao('entidade', $ordenar, $direcao) ?>
                                    </a>
                                </th>

                                <th>
                                    <a href="<?= link_ordenacao('inicio', $ordenar, $direcao, $filtro_estado) ?>"
                                       class="text-white text-decoration-none">
                                        Data Início <?= icone_ordenacao('inicio', $ordenar, $direcao) ?>
                                    </a>
                                </th>

                                <th>
                                    <a href="<?= link_ordenacao('fim', $ordenar, $direcao, $filtro_estado) ?>"
                                       class="text-white text-decoration-none">
                                        Data Fim <?= icone_ordenacao('fim', $ordenar, $direcao) ?>
                                    </a>
                                </th>

                                <th>
                                    <a href="<?= link_ordenacao('periodicidade', $ordenar, $direcao, $filtro_estado) ?>"
                                       class="text-white text-decoration-none">
                                        Periodicidade <?= icone_ordenacao('periodicidade', $ordenar, $direcao) ?>
                                    </a>
                                </th>

                                <th>Estado</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($resultados_pagina as $registo) : ?>

                                <?php
                                $dados_estado = calcular_estado($registo);
                                $estado = $dados_estado['estado'];
                                $classe = $dados_estado['classe'];
                                ?>

                                <tr>

                                    <td><?= htmlspecialchars($registo->codigo_inventario) ?></td>

                                    <td><?= htmlspecialchars($registo->designacao) ?></td>

                                    <td><?= htmlspecialchars($registo->tipo_contrato ?? '-') ?></td>

                                    <td><?= htmlspecialchars($registo->entidade_responsavel ?? '-') ?></td>

                                    <td>
                                        <?= !empty($registo->data_inicio)
                                            ? date('d/m/Y', strtotime($registo->data_inicio))
                                            : '-' ?>
                                    </td>

                                    <td>
                                        <?= !empty($registo->data_fim)
                                            ? date('d/m/Y', strtotime($registo->data_fim))
                                            : '-' ?>
                                    </td>

                                    <td><?= htmlspecialchars($registo->periodicidade ?? '-') ?></td>

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

                    <nav>
                        <ul class="pagination justify-content-center">

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

                <?php endif; ?>

            <?php endif; ?>

            <div class="mt-3 text-center">
                <a href="ferramentas.php" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i>
                    Voltar
                </a>
            </div>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>