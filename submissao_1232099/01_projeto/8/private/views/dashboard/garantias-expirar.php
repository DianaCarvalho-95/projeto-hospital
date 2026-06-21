<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$erro = '';
$garantias = [];
$pagina = isset($_GET['pagina']) ? max(1, (int) $_GET['pagina']) : 1;
$registos_por_pagina = 10;
$offset = ($pagina - 1) * $registos_por_pagina;
$total_garantias = 0;
$total_paginas = 1;

try {
    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST . ";dbname=" . MYSQL_DATABASE . ";charset=utf8mb4",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );
    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql_base = "
        SELECT
            gc.id,
            e.id AS equipamento_id,
            e.codigo_inventario,
            e.designacao,
            COALESCE(gc.tipo_contrato, 'Garantia / contrato') AS tipo_contrato,
            gc.entidade_responsavel,
            gc.data_inicio,
            gc.data_fim,
            DATEDIFF(gc.data_fim, CURDATE()) AS dias_restantes
        FROM garantias_contratos gc
        INNER JOIN equipamentos e ON e.id = gc.equipamento_id
        WHERE gc.data_fim IS NOT NULL
        AND gc.data_fim BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ";

    $total_garantias = (int) $ligacao->query("SELECT COUNT(*) FROM (" . $sql_base . ") garantias_total")->fetchColumn();
    $total_paginas = max(1, (int) ceil($total_garantias / $registos_por_pagina));

    if ($pagina > $total_paginas) {
        $pagina = $total_paginas;
        $offset = ($pagina - 1) * $registos_por_pagina;
    }

    $stmt = $ligacao->prepare(
        "SELECT * FROM (" . $sql_base . ") garantias
         ORDER BY data_fim ASC, codigo_inventario ASC
         LIMIT :limite OFFSET :offset"
    );
    $stmt->bindValue(':limite', $registos_por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $garantias = $stmt->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $err) {
    $erro = 'Aconteceu um erro ao carregar as garantias a expirar.';
}

$ligacao = null;

function h($valor) { return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8'); }
function data_curta($data) { return empty($data) ? 'Sem data' : date('d/m/Y', strtotime($data)); }

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>
<div class="container-fluid">
    <div class="row">
        <?php include '../../includes/sidebar.php'; ?>
        <main class="col-md-9 col-lg-10 alert-list-page">
            <header class="page-head">
                <div>
                    <h2 class="page-title"><i class="fas fa-file-contract me-2"></i>Garantias a expirar</h2>
                    <p class="page-subtitle">Contratos e garantias que terminam nos próximos 30 dias.</p>
                </div>
                <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Voltar à dashboard</a>
            </header>

            <?php if (!empty($erro)) : ?><div class="alert alert-danger"><?= h($erro) ?></div><?php endif; ?>

            <section class="summary-card">
                <div class="summary-label">Acompanhamento documental</div>
                <div class="summary-line">
                    <span class="summary-value"><?= (int) $total_garantias ?></span>
                    <span class="summary-help">garantia(s) ou contrato(s) a terminar nos próximos 30 dias.</span>
                </div>
            </section>

            <section class="content-card">
                <?php if (count($garantias) === 0) : ?>
                    <div class="empty-state"><strong>Não existem garantias a expirar.</strong><br>Não há contratos ou garantias a terminar nos próximos 30 dias.</div>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead class="table-primary-custom">
                                <tr>
                                    <th>Equipamento</th>
                                    <th>Tipo</th>
                                    <th>Entidade</th>
                                    <th>Início</th>
                                    <th>Fim</th>
                                    <th>Restam</th>
                                    <th>Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($garantias as $garantia) : ?>
                                    <tr>
                                        <td><span class="code-chip"><?= h($garantia->codigo_inventario) ?></span> <a class="equipment-link ms-2" href="../equipamentos/detalhes.php?id=<?= (int) $garantia->equipamento_id ?>#garantias"><?= h($garantia->designacao) ?></a></td>
                                        <td><?= h($garantia->tipo_contrato) ?></td>
                                        <td><?= h($garantia->entidade_responsavel ?: 'Sem indicação') ?></td>
                                        <td><?= h(data_curta($garantia->data_inicio)) ?></td>
                                        <td><?= h(data_curta($garantia->data_fim)) ?></td>
                                        <td><span class="status-chip"><?= (int) $garantia->dias_restantes ?> dia(s)</span></td>
                                        <td><a class="action-btn" href="../equipamentos/detalhes.php?id=<?= (int) $garantia->equipamento_id ?>#garantias"><i class="fas fa-eye me-1"></i> Ver ficha</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($total_paginas > 1) : ?>
                        <nav class="pagination-wrap" aria-label="Paginação das garantias"><ul class="pagination mb-0">
                            <?php for ($i = 1; $i <= $total_paginas; $i++) : ?><li class="page-item <?= $i === $pagina ? 'active' : '' ?>"><a class="page-link" href="?pagina=<?= $i ?>"><?= $i ?></a></li><?php endfor; ?>
                        </ul></nav>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

