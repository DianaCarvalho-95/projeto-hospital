<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$localizacao = null;
$equipamentos = [];
$total_equipamentos = 0;
$resumo_estados = [
    'ativos' => 0,
    'manutencao' => 0,
    'calibracao' => 0,
    'inativos' => 0
];
$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$registos_por_pagina = 6;
$total_paginas = 1;
$erro = '';

if ($pagina < 1) {
    $pagina = 1;
}

if ($id <= 0) {
    $erro = 'Localização inválida.';
} else {
    try {
        $ligacao = new PDO(
            "mysql:host=" . MYSQL_HOST . ";port=" . MYSQL_PORT . ";dbname=" . MYSQL_DATABASE . ";charset=utf8",
            MYSQL_USERNAME,
            MYSQL_PASSWORD
        );

        $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $ligacao->prepare(
            "SELECT *
             FROM localizacoes
             WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
        $localizacao = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$localizacao) {
            $erro = 'Localização não encontrada.';
        } else {
            $stmt_total = $ligacao->prepare(
                "SELECT COUNT(*)
                 FROM equipamentos
                 WHERE localizacao_id = :id"
            );
            $stmt_total->execute([':id' => $id]);
            $total_equipamentos = (int) $stmt_total->fetchColumn();

            $stmt_estados = $ligacao->prepare(
                "SELECT
                    SUM(CASE WHEN estado = 'Ativo' THEN 1 ELSE 0 END) AS ativos,
                    SUM(CASE WHEN estado LIKE '%manutenção%' OR estado LIKE '%manutencao%' THEN 1 ELSE 0 END) AS manutencao,
                    SUM(CASE WHEN estado LIKE '%calibração%' OR estado LIKE '%calibracao%' THEN 1 ELSE 0 END) AS calibracao,
                    SUM(CASE WHEN estado = 'Inativo' THEN 1 ELSE 0 END) AS inativos
                 FROM equipamentos
                 WHERE localizacao_id = :id"
            );
            $stmt_estados->execute([':id' => $id]);
            $estados_db = $stmt_estados->fetch(PDO::FETCH_ASSOC);
            if ($estados_db) {
                $resumo_estados = [
                    'ativos' => (int) $estados_db['ativos'],
                    'manutencao' => (int) $estados_db['manutencao'],
                    'calibracao' => (int) $estados_db['calibracao'],
                    'inativos' => (int) $estados_db['inativos']
                ];
            }

            $total_paginas = max(1, (int) ceil($total_equipamentos / $registos_por_pagina));

            if ($pagina > $total_paginas) {
                $pagina = $total_paginas;
            }

            $offset = ($pagina - 1) * $registos_por_pagina;

            $stmt = $ligacao->prepare(
                "SELECT id, codigo_inventario, designacao, categoria, marca, modelo, estado, criticidade
                 FROM equipamentos
                 WHERE localizacao_id = :id
                 ORDER BY codigo_inventario
                 LIMIT :limite OFFSET :offset"
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':limite', $registos_por_pagina, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $equipamentos = $stmt->fetchAll(PDO::FETCH_OBJ);
        }
    } catch (PDOException $err) {
        $erro = 'Aconteceu um erro ao consultar a localização.';
    }

    $ligacao = null;
}

function h($valor)
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

function link_paginacao_localizacao($pagina)
{
    return 'detalhes.php?id=' . intval($_GET['id'] ?? 0) . '&pagina=' . intval($pagina);
}

function classe_estado_localizacao($estado)
{
    $estado_normalizado = mb_strtolower($estado ?? '', 'UTF-8');

    if (strpos($estado_normalizado, 'ativo') !== false && strpos($estado_normalizado, 'inativo') === false) {
        return 'estado-ativo';
    }

    if (strpos($estado_normalizado, 'manuten') !== false) {
        return 'estado-manutencao';
    }

    if (strpos($estado_normalizado, 'calibra') !== false) {
        return 'estado-calibracao';
    }

    if (strpos($estado_normalizado, 'inativo') !== false) {
        return 'estado-inativo';
    }

    return 'estado-neutro';
}

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>
<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 detalhes-page">

            <div class="d-flex justify-content-between align-items-start mb-3 gap-3">
                <div>
                    <h2 class="page-title mb-1">
                        <i class="fa-solid fa-location-dot me-2"></i>
                        Detalhes da Localização
                    </h2>
                    <p class="page-subtitle">Consulta da localização e dos equipamentos associados.</p>
                </div>

                <div class="d-flex gap-2">
                    <a href="lista.php" class="btn-voltar-custom">
                        <i class="fa-solid fa-arrow-left"></i>
                        Voltar à lista
                    </a>

                    <?php if (empty($erro)) : ?>
                        <a href="editar.php?id=<?= $localizacao->id ?>" class="btn-editar-custom">
                            <i class="fa-regular fa-pen-to-square"></i>
                            Editar dados da localização
                        </a>

                        <?php if ($total_equipamentos == 0) : ?>
                            <a href="apagar.php?id=<?= $localizacao->id ?>" class="btn-eliminar-custom">
                                <i class="fa-solid fa-trash-can"></i>
                                Eliminar localização
                            </a>
                        <?php else : ?>
                            <span class="btn-eliminar-disabled" title="Não é possível eliminar uma localização com equipamentos associados.">
                                <i class="fa-solid fa-lock"></i>
                                Eliminar localização
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($erro)) : ?>
                <div class="alert alert-danger"><?= h($erro) ?></div>
            <?php else : ?>
                <section class="summary-card">
                    <div class="location-summary">
                        <div class="summary-main">
                            <div class="summary-title"><?= h($localizacao->servico) ?></div>
                            <div class="summary-subtitle"><?= h($localizacao->edificio . ' - ' . $localizacao->piso . ' - ' . $localizacao->sala) ?></div>
                        </div>

                        <div class="summary-item">
                            <div class="info-label">Edifício</div>
                            <div class="info-value"><?= h($localizacao->edificio) ?></div>
                        </div>

                        <div class="summary-item">
                            <div class="info-label">Piso</div>
                            <div class="info-value"><?= h($localizacao->piso) ?></div>
                        </div>

                        <div class="summary-item">
                            <div class="info-label">Sala</div>
                            <div class="info-value"><?= h($localizacao->sala) ?></div>
                        </div>

                        <div class="summary-item">
                            <div class="info-label">Equipamentos</div>
                            <div class="info-value"><?= $total_equipamentos ?></div>
                        </div>
                    </div>
                </section>

                <div class="state-grid">
                    <div class="state-card">
                        <div>
                            <div class="info-label">Ativos</div>
                            <div class="info-value"><?= $resumo_estados['ativos'] ?></div>
                        </div>
                        <span class="estado-pill estado-ativo">OK</span>
                    </div>

                    <div class="state-card">
                        <div>
                            <div class="info-label">Em manutenção</div>
                            <div class="info-value"><?= $resumo_estados['manutencao'] ?></div>
                        </div>
                        <span class="estado-pill estado-manutencao">Atenção</span>
                    </div>

                    <div class="state-card">
                        <div>
                            <div class="info-label">Em calibração</div>
                            <div class="info-value"><?= $resumo_estados['calibracao'] ?></div>
                        </div>
                        <span class="estado-pill estado-calibracao">Verificar</span>
                    </div>

                    <div class="state-card">
                        <div>
                            <div class="info-label">Inativos</div>
                            <div class="info-value"><?= $resumo_estados['inativos'] ?></div>
                        </div>
                        <span class="estado-pill estado-inativo">Parado</span>
                    </div>
                </div>

                <section class="content-card">
                    <h5 class="section-title">Equipamentos nesta localização</h5>

                    <?php if ($total_equipamentos == 0) : ?>
                        <div class="alert alert-info mb-0">Não existem equipamentos associados a esta localização.</div>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="table-primary-custom">
                                    <tr>
                                        <th>Código</th>
                                        <th>Equipamento</th>
                                        <th>Categoria</th>
                                        <th>Marca / Modelo</th>
                                        <th>Criticidade</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($equipamentos as $equipamento) : ?>
                                        <tr>
                                            <td><?= h($equipamento->codigo_inventario) ?></td>
                                            <td>
                                                <div class="equipment-main"><?= h($equipamento->designacao) ?></div>
                                                <div class="equipment-sub"><?= h($equipamento->marca . ' | ' . $equipamento->modelo) ?></div>
                                            </td>
                                            <td><?= h($equipamento->categoria) ?></td>
                                            <td><?= h($equipamento->marca . ' / ' . $equipamento->modelo) ?></td>
                                            <td><?= h($equipamento->criticidade) ?></td>
                                            <td>
                                                <span class="estado-pill <?= h(classe_estado_localizacao($equipamento->estado)) ?>">
                                                    <?= h($equipamento->estado) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($total_paginas > 1) : ?>
                            <nav class="pagination-wrap" aria-label="Paginação dos equipamentos da localização">
                                <ul class="pagination pagination-sm mb-0">
                                    <?php for ($i = 1; $i <= $total_paginas; $i++) : ?>
                                        <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                                            <a class="page-link" href="<?= h(link_paginacao_localizacao($i)) ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>


