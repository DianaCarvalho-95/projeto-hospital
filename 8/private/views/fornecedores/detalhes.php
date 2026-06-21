<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$fornecedor = null;
$equipamentos = [];
$total_equipamentos = 0;
$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$registos_por_pagina = 5;
$total_paginas = 1;
$erro = '';

if ($pagina < 1) {
    $pagina = 1;
}

if ($id <= 0) {
    $erro = 'Fornecedor inválido.';
} else {
    try {
        $ligacao = new PDO(
            "mysql:host=" . MYSQL_HOST . ";port=" . MYSQL_PORT . ";dbname=" . MYSQL_DATABASE . ";charset=utf8",
            MYSQL_USERNAME,
            MYSQL_PASSWORD
        );
        $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $ligacao->prepare("SELECT * FROM fornecedores WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $fornecedor = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$fornecedor) {
            $erro = 'Fornecedor não encontrado.';
        } else {
            $stmt_total = $ligacao->prepare("SELECT COUNT(*) FROM equipamentos WHERE fornecedor_id = :id");
            $stmt_total->execute([':id' => $id]);
            $total_equipamentos = (int) $stmt_total->fetchColumn();

            $total_paginas = max(1, (int) ceil($total_equipamentos / $registos_por_pagina));
            if ($pagina > $total_paginas) {
                $pagina = $total_paginas;
            }
            $offset = ($pagina - 1) * $registos_por_pagina;

            $stmt = $ligacao->prepare(
                "SELECT
                    e.id,
                    e.codigo_inventario,
                    e.designacao,
                    e.categoria,
                    e.marca,
                    e.modelo,
                    e.estado,
                    e.criticidade,
                    CONCAT_WS(' - ', l.edificio, l.piso, l.servico, l.sala) AS localizacao
                 FROM equipamentos e
                 LEFT JOIN localizacoes l ON l.id = e.localizacao_id
                 WHERE e.fornecedor_id = :id
                 ORDER BY e.codigo_inventario
                 LIMIT :limite OFFSET :offset"
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':limite', $registos_por_pagina, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $equipamentos = $stmt->fetchAll(PDO::FETCH_OBJ);
        }
    } catch (PDOException $err) {
        $erro = 'Aconteceu um erro ao consultar o fornecedor.';
    }

    $ligacao = null;
}

function h($valor)
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

function valor_fornecedor($valor)
{
    return !empty($valor) ? h($valor) : 'Não definido';
}

function link_paginacao_fornecedor($pagina)
{
    return 'detalhes.php?id=' . intval($_GET['id'] ?? 0) . '&pagina=' . intval($pagina);
}

function classe_estado_fornecedor($estado)
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
                        <i class="fa-solid fa-truck-medical me-2"></i>
                        Detalhes do Fornecedor
                    </h2>
                    <p class="page-subtitle">Consulta do fornecedor e dos equipamentos associados.</p>
                </div>

                <div class="d-flex gap-2">
                    <a href="lista.php" class="btn-voltar-custom">
                        <i class="fa-solid fa-arrow-left"></i>
                        Voltar à lista
                    </a>

                    <?php if (empty($erro)) : ?>
                        <a href="editar.php?id=<?= $fornecedor->id ?>" class="btn-editar-custom">
                            <i class="fa-regular fa-pen-to-square"></i>
                            Editar dados do fornecedor
                        </a>

                        <?php if ($total_equipamentos == 0) : ?>
                            <a href="apagar.php?id=<?= $fornecedor->id ?>" class="btn-eliminar-custom">
                                <i class="fa-solid fa-trash-can"></i>
                                Eliminar fornecedor
                            </a>
                        <?php else : ?>
                            <span class="btn-eliminar-disabled" title="Não é possível eliminar um fornecedor com equipamentos associados.">
                                <i class="fa-solid fa-lock"></i>
                                Eliminar fornecedor
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($erro)) : ?>
                <div class="mensagem-erro"><?= h($erro) ?></div>
            <?php else : ?>
                <section class="summary-card">
                    <div class="supplier-summary">
                        <div class="summary-main">
                            <div class="summary-title"><?= h($fornecedor->nome_empresa) ?></div>
                            <div class="summary-subtitle"><?= valor_fornecedor($fornecedor->morada) ?></div>
                        </div>
                        <div class="summary-item">
                            <div class="info-label">NIF</div>
                            <div class="info-value"><?= h($fornecedor->nif) ?></div>
                        </div>
                        <div class="summary-item">
                            <div class="info-label">Tipo</div>
                            <div class="info-value"><?= valor_fornecedor($fornecedor->tipo_fornecedor) ?></div>
                        </div>
                        <div class="summary-item">
                            <div class="info-label">Contacto principal</div>
                            <div class="info-value"><?= valor_fornecedor($fornecedor->pessoa_contacto ?: $fornecedor->email) ?></div>
                        </div>
                        <div class="summary-item">
                            <div class="info-label">Telefone</div>
                            <div class="info-value"><?= valor_fornecedor($fornecedor->telefone) ?></div>
                        </div>
                        <div class="summary-item">
                            <div class="info-label">Equipamentos</div>
                            <div class="info-value"><?= $total_equipamentos ?></div>
                        </div>
                    </div>
                </section>

                <section class="content-card mb-3">
                    <h5 class="section-title">Contactos e informação adicional</h5>
                    <div class="row g-2">
                        <div class="col-md-3"><div class="info-label">Telefone</div><div class="info-value"><?= valor_fornecedor($fornecedor->telefone) ?></div></div>
                        <div class="col-md-3"><div class="info-label">Pessoa de contacto</div><div class="info-value"><?= valor_fornecedor($fornecedor->pessoa_contacto) ?></div></div>
                        <div class="col-md-3"><div class="info-label">Telefone de contacto</div><div class="info-value"><?= valor_fornecedor($fornecedor->telefone_contacto) ?></div></div>
                        <div class="col-md-3"><div class="info-label">Website</div><div class="info-value"><?= valor_fornecedor($fornecedor->website) ?></div></div>
                    </div>
                </section>

                <section class="content-card">
                    <h5 class="section-title">Equipamentos associados ao fornecedor</h5>
                    <?php if ($total_equipamentos == 0) : ?>
                        <div class="alert alert-info mb-0">Não existem equipamentos associados a este fornecedor.</div>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="table-primary-custom">
                                    <tr>
                                        <th>Código</th>
                                        <th>Equipamento</th>
                                        <th>Categoria</th>
                                        <th>Marca / Modelo</th>
                                        <th>Localização</th>
                                        <th>Criticidade</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($equipamentos as $equipamento) : ?>
                                        <tr>
                                            <td><?= h($equipamento->codigo_inventario) ?></td>
                                            <td><div class="equipment-main"><?= h($equipamento->designacao) ?></div><div class="equipment-sub"><?= h($equipamento->marca . ' | ' . $equipamento->modelo) ?></div></td>
                                            <td><?= h($equipamento->categoria) ?></td>
                                            <td><?= h($equipamento->marca . ' / ' . $equipamento->modelo) ?></td>
                                            <td><?= valor_fornecedor($equipamento->localizacao) ?></td>
                                            <td><?= h($equipamento->criticidade) ?></td>
                                            <td><span class="estado-pill <?= h(classe_estado_fornecedor($equipamento->estado)) ?>"><?= h($equipamento->estado) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($total_paginas > 1) : ?>
                            <nav class="pagination-wrap" aria-label="Paginação dos equipamentos do fornecedor">
                                <ul class="pagination pagination-sm mb-0">
                                    <?php for ($i = 1; $i <= $total_paginas; $i++) : ?>
                                        <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                                            <a class="page-link" href="<?= h(link_paginacao_fornecedor($i)) ?>"><?= $i ?></a>
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


