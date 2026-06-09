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
            "mysql:host=" . MYSQL_HOST . ";dbname=" . MYSQL_DATABASE . ";charset=utf8",
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

<style>
    .detalhes-page {
        background: #f5f7fa;
        min-height: 100vh;
        padding: 24px;
    }

    .page-title {
        font-weight: 700;
        color: #1E3A5F;
        font-size: 1.8rem;
        margin-bottom: 0;
    }

    .page-subtitle {
        color: #64748b;
        font-size: 0.95rem;
        margin-bottom: 0;
    }

    .btn-voltar-custom,
    .btn-editar-custom,
    .btn-eliminar-custom,
    .btn-eliminar-disabled {
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.86rem;
        padding: 7px 12px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 7px;
    }

    .btn-voltar-custom {
        background: #fff;
        border: 1px solid #dbe4ef;
        color: #1E3A5F;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
    }

    .btn-voltar-custom:hover {
        background: #f6faff;
        border-color: #bdd5f0;
        color: #1E3A5F;
    }

    .btn-editar-custom {
        background: #2F5D8A;
        border: 1px solid #2F5D8A;
        color: #ffffff;
    }

    .btn-editar-custom:hover {
        background: #1E3A5F;
        border-color: #1E3A5F;
        color: #ffffff;
    }

    .btn-eliminar-custom {
        background: #fff5f5;
        border: 1px solid #f3c7cd;
        color: #9f1239;
    }

    .btn-eliminar-custom:hover {
        background: #ffe4e6;
        color: #9f1239;
    }

    .btn-eliminar-disabled {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #64748b;
        cursor: not-allowed;
    }

    .summary-card,
    .content-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 5px 14px rgba(15, 23, 42, 0.05);
    }

    .summary-card {
        padding: 14px 16px;
        margin-bottom: 14px;
    }

    .supplier-summary {
        display: grid;
        grid-template-columns: 1.15fr repeat(5, minmax(105px, 0.65fr));
        gap: 10px;
        align-items: stretch;
    }

    .summary-main,
    .summary-item {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 12px;
    }

    .summary-title {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 800;
        margin-bottom: 4px;
    }

    .summary-subtitle {
        color: #52677d;
        font-size: 0.82rem;
    }

    .info-label {
        color: #52677d;
        font-size: 0.7rem;
        font-weight: 800;
        margin-bottom: 4px;
        text-transform: uppercase;
    }

    .info-value {
        color: #0f172a;
        font-size: 0.88rem;
        font-weight: 700;
        line-height: 1.35;
    }

    .content-card {
        padding: 16px;
    }

    .section-title {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #1E3A5F;
        font-weight: 800;
        font-size: 0.98rem;
        margin-bottom: 12px;
        padding-bottom: 9px;
        border-bottom: 1px solid #e8eef5;
    }

    .section-title::before {
        content: "";
        width: 4px;
        height: 18px;
        border-radius: 999px;
        background: #2F5D8A;
    }

    .table {
        border-color: #d9e2ec;
    }

    .table-primary-custom th {
        background: #2F5D8A !important;
        color: #ffffff !important;
        border-color: #2F5D8A !important;
        font-weight: 700;
        font-size: 0.84rem;
        white-space: nowrap;
    }

    .table td {
        color: #0f172a;
        font-size: 0.86rem;
        vertical-align: middle;
    }

    .equipment-main {
        font-weight: 800;
        color: #0f172a;
    }

    .equipment-sub {
        color: #64748b;
        font-size: 0.76rem;
        margin-top: 2px;
    }

    .estado-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 4px 9px;
        border-radius: 999px;
        font-weight: 800;
        font-size: 0.74rem;
        white-space: nowrap;
    }

    .estado-ativo { background: #dcfce7; color: #166534; }
    .estado-manutencao { background: #fef3c7; color: #92400e; }
    .estado-calibracao { background: #dbeafe; color: #1d4ed8; }
    .estado-inativo { background: #fee2e2; color: #991b1b; }
    .estado-neutro { background: #e5e7eb; color: #374151; }

    .pagination-wrap {
        display: flex;
        justify-content: center;
        margin-top: 14px;
    }

    .pagination .page-link {
        color: #1E3A5F;
        border-color: #d8e1ec;
        font-size: 0.82rem;
        font-weight: 700;
    }

    .pagination .page-item.active .page-link {
        background: #2F5D8A;
        border-color: #2F5D8A;
        color: #fff;
    }

    .mensagem-erro {
        background: #fdeaea;
        color: #bb2d3b;
        border: 1px solid #f8d3d3;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 16px;
    }

    @media (max-width: 991px) {
        .supplier-summary {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .summary-main { grid-column: 1 / -1; }
    }

    @media (max-width: 640px) {
        .supplier-summary {
            grid-template-columns: 1fr;
        }
    }
</style>

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
