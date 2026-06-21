<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$erro = '';
$prazos = [];
$pagina = isset($_GET['pagina']) ? max(1, (int) $_GET['pagina']) : 1;
$registos_por_pagina = 10;
$offset = ($pagina - 1) * $registos_por_pagina;
$total_prazos = 0;
$total_paginas = 1;

try {
    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST . ";port=" . MYSQL_PORT . ";dbname=" . MYSQL_DATABASE . ";charset=utf8mb4",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );
    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql_base = "
        SELECT
            'Manutenção' AS tipo,
            e.id AS equipamento_id,
            e.codigo_inventario,
            e.designacao,
            m.tipo_manutencao AS assunto,
            m.responsavel,
            m.proxima_manutencao AS data_limite,
            DATEDIFF(CURDATE(), m.proxima_manutencao) AS dias_atraso
        FROM manutencoes m
        INNER JOIN equipamentos e ON e.id = m.equipamento_id
        WHERE m.proxima_manutencao IS NOT NULL
        AND m.proxima_manutencao < CURDATE()

        UNION ALL

        SELECT
            'Garantia' AS tipo,
            e.id AS equipamento_id,
            e.codigo_inventario,
            e.designacao,
            COALESCE(gc.tipo_contrato, 'Garantia / contrato') AS assunto,
            gc.entidade_responsavel AS responsavel,
            gc.data_fim AS data_limite,
            DATEDIFF(CURDATE(), gc.data_fim) AS dias_atraso
        FROM garantias_contratos gc
        INNER JOIN equipamentos e ON e.id = gc.equipamento_id
        WHERE gc.data_fim IS NOT NULL
        AND gc.data_fim < CURDATE()
    ";

    $total_prazos = (int) $ligacao->query("SELECT COUNT(*) FROM (" . $sql_base . ") prazos_total")->fetchColumn();
    $total_paginas = max(1, (int) ceil($total_prazos / $registos_por_pagina));

    if ($pagina > $total_paginas) {
        $pagina = $total_paginas;
        $offset = ($pagina - 1) * $registos_por_pagina;
    }

    $stmt = $ligacao->prepare(
        "SELECT * FROM (" . $sql_base . ") prazos
         ORDER BY data_limite ASC, codigo_inventario ASC
         LIMIT :limite OFFSET :offset"
    );
    $stmt->bindValue(':limite', $registos_por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $prazos = $stmt->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $err) {
    $erro = 'Aconteceu um erro ao carregar os prazos em atraso.';
}

$ligacao = null;

function h($valor)
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

function data_curta($data)
{
    if (empty($data)) {
        return 'Sem data';
    }

    return date('d/m/Y', strtotime($data));
}

function classe_tipo_prazo($tipo)
{
    return $tipo === 'Garantia' ? 'prazo-garantia' : 'prazo-manutencao';
}

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>
<div class="container-fluid">
    <div class="row">
        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 prazos-page">
            <header class="page-head">
                <div>
                    <h2 class="page-title">
                        <i class="fas fa-clock-rotate-left me-2"></i>
                        Prazos em atraso
                    </h2>
                    <p class="page-subtitle">Manutenções e garantias cuja data prevista já foi ultrapassada.</p>
                </div>
                <a href="dashboard.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Voltar à dashboard
                </a>
            </header>

            <?php if (!empty($erro)) : ?>
                <div class="alert alert-danger"><?= h($erro) ?></div>
            <?php endif; ?>

            <section class="summary-card">
                <div class="summary-label">Situações pendentes</div>
                <div class="summary-line">
                    <span class="summary-value"><?= (int) $total_prazos ?></span>
                    <span class="summary-help">prazo(s) em atraso para analisar e regularizar.</span>
                </div>
            </section>

            <section class="content-card">
                <?php if (count($prazos) === 0) : ?>
                    <div class="empty-state">
                        <strong>Não existem prazos em atraso.</strong><br>
                        Todas as manutenções e garantias estão dentro das datas previstas.
                    </div>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead class="table-primary-custom">
                                <tr>
                                    <th>Tipo</th>
                                    <th>Equipamento</th>
                                    <th>Assunto</th>
                                    <th>Responsável / Entidade</th>
                                    <th>Data limite</th>
                                    <th>Atraso</th>
                                    <th>Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($prazos as $prazo) : ?>
                                    <tr>
                                        <td>
                                            <span class="type-chip <?= h(classe_tipo_prazo($prazo->tipo)) ?>">
                                                <?= h($prazo->tipo) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="code-chip"><?= h($prazo->codigo_inventario) ?></span>
                                            <a class="equipment-link ms-2" href="../equipamentos/detalhes.php?id=<?= (int) $prazo->equipamento_id ?>">
                                                <?= h($prazo->designacao) ?>
                                            </a>
                                        </td>
                                        <td><?= h($prazo->assunto) ?></td>
                                        <td><?= h($prazo->responsavel ?: 'Sem indicação') ?></td>
                                        <td><?= h(data_curta($prazo->data_limite)) ?></td>
                                        <td>
                                            <span class="delay-chip">
                                                <?= (int) $prazo->dias_atraso ?> dia(s)
                                            </span>
                                        </td>
                                        <td>
                                            <a class="action-btn" href="../equipamentos/detalhes.php?id=<?= (int) $prazo->equipamento_id ?>">
                                                <i class="fas fa-eye me-1"></i> Ver ficha
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($total_paginas > 1) : ?>
                        <nav class="pagination-wrap" aria-label="Paginação dos prazos em atraso">
                            <ul class="pagination mb-0">
                                <?php for ($i = 1; $i <= $total_paginas; $i++) : ?>
                                    <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
                                        <a class="page-link" href="?pagina=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>







