<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$erro = '';
$documentos = [];
$pagina = isset($_GET['pagina']) ? max(1, (int) $_GET['pagina']) : 1;
$registos_por_pagina = 10;
$offset = ($pagina - 1) * $registos_por_pagina;
$total_documentos = 0;
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
            e.id AS equipamento_id,
            e.codigo_inventario,
            e.designacao,
            e.categoria,
            e.marca,
            e.modelo,
            e.estado,
            CASE WHEN d.id IS NULL THEN 1 ELSE 0 END AS falta_ficha,
            CASE WHEN m.id_manual IS NULL THEN 1 ELSE 0 END AS falta_manual
        FROM equipamentos e
        LEFT JOIN documentacao d ON d.equipamento_id = e.id
        LEFT JOIN manuais_equipamentos m ON m.id_equipamento = e.id
        WHERE d.id IS NULL OR m.id_manual IS NULL
        GROUP BY e.id, e.codigo_inventario, e.designacao, e.categoria, e.marca, e.modelo, e.estado, d.id, m.id_manual
    ";

    $total_documentos = (int) $ligacao->query("SELECT COUNT(*) FROM (" . $sql_base . ") docs_total")->fetchColumn();
    $total_paginas = max(1, (int) ceil($total_documentos / $registos_por_pagina));

    if ($pagina > $total_paginas) {
        $pagina = $total_paginas;
        $offset = ($pagina - 1) * $registos_por_pagina;
    }

    $stmt = $ligacao->prepare(
        "SELECT * FROM (" . $sql_base . ") docs
         ORDER BY codigo_inventario ASC
         LIMIT :limite OFFSET :offset"
    );
    $stmt->bindValue(':limite', $registos_por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $documentos = $stmt->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $err) {
    $erro = 'Aconteceu um erro ao carregar a documentação em falta.';
}

$ligacao = null;

function h($valor) { return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8'); }
function estado_documentacao($item)
{
    $faltas = [];
    if ((int) $item->falta_ficha === 1) { $faltas[] = 'Ficha técnica'; }
    if ((int) $item->falta_manual === 1) { $faltas[] = 'Manual'; }
    return implode(' e ', $faltas);
}

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>
<div class="container-fluid">
    <div class="row">
        <?php include '../../includes/sidebar.php'; ?>
        <main class="col-md-9 col-lg-10 alert-list-page">
            <header class="page-head">
                <div>
                    <h2 class="page-title"><i class="fas fa-folder-open me-2"></i>Documentação em falta</h2>
                    <p class="page-subtitle">Equipamentos sem ficha técnica ou manual associado.</p>
                </div>
                <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Voltar à dashboard</a>
            </header>

            <?php if (!empty($erro)) : ?><div class="alert alert-danger"><?= h($erro) ?></div><?php endif; ?>

            <section class="summary-card">
                <div class="summary-label">Controlo documental</div>
                <div class="summary-line">
                    <span class="summary-value"><?= (int) $total_documentos ?></span>
                    <span class="summary-help">equipamento(s) com documentação incompleta.</span>
                </div>
            </section>

            <section class="content-card">
                <?php if (count($documentos) === 0) : ?>
                    <div class="empty-state"><strong>Não existe documentação em falta.</strong><br>Todos os equipamentos têm ficha técnica e manual associados.</div>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead class="table-primary-custom">
                                <tr>
                                    <th>Equipamento</th>
                                    <th>Categoria</th>
                                    <th>Marca / Modelo</th>
                                    <th>Estado</th>
                                    <th>Em falta</th>
                                    <th>Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documentos as $item) : ?>
                                    <tr>
                                        <td><span class="code-chip"><?= h($item->codigo_inventario) ?></span> <a class="equipment-link ms-2" href="../equipamentos/detalhes.php?id=<?= (int) $item->equipamento_id ?>#documentacao"><?= h($item->designacao) ?></a></td>
                                        <td><?= h($item->categoria) ?></td>
                                        <td><strong><?= h($item->marca) ?></strong><span class="muted-small"><?= h($item->modelo) ?></span></td>
                                        <td><span class="state-chip"><?= h($item->estado) ?></span></td>
                                        <td><span class="missing-chip"><?= h(estado_documentacao($item)) ?></span></td>
                                        <td><a class="action-btn" href="../equipamentos/detalhes.php?id=<?= (int) $item->equipamento_id ?>#documentacao"><i class="fas fa-eye me-1"></i> Ver ficha</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($total_paginas > 1) : ?>
                        <nav class="pagination-wrap" aria-label="Paginação da documentação"><ul class="pagination mb-0">
                            <?php for ($i = 1; $i <= $total_paginas; $i++) : ?><li class="page-item <?= $i === $pagina ? 'active' : '' ?>"><a class="page-link" href="?pagina=<?= $i ?>"><?= $i ?></a></li><?php endfor; ?>
                        </ul></nav>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

