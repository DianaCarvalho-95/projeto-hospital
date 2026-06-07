<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$erro = '';
$resultados = [];

$ordenar = isset($_GET['ordenar']) ? $_GET['ordenar'] : 'id';
$direcao = isset($_GET['direcao']) && $_GET['direcao'] == 'asc' ? 'asc' : 'desc';

$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;

if ($pagina < 1) {
    $pagina = 1;
}

$registos_por_pagina = 14;
$offset = ($pagina - 1) * $registos_por_pagina;

/*Colunas permitidas para ordenação*/
$colunas_permitidas = [
    'id' => 'd.id',
    'nome' => 'd.nome_documento',
    'tipo' => 'd.tipo_documento',
    'equipamento' => 'e.designacao',
    'fornecedor' => 'f.nome_empresa',
    'data' => 'd.data_documento',
    'validade' => 'd.data_validade'
];

$coluna_sql = isset($colunas_permitidas[$ordenar])
    ? $colunas_permitidas[$ordenar]
    : 'd.id';

try {

    /*Ligação à base de dados*/
    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST .
            ";dbname=" . MYSQL_DATABASE .
            ";charset=utf8",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );

    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    /*Conta o total de documentos para calcular a paginação*/
    $stmt_total = $ligacao->query("SELECT COUNT(*) FROM documentacao");

    $total_registos = $stmt_total->fetchColumn();
    $total_paginas = ceil($total_registos / $registos_por_pagina);

    /*Consulta principal da documentação*/
    $sql = "SELECT 
                d.*,
                e.codigo_inventario,
                e.designacao,
                f.nome_empresa
            FROM documentacao d
            INNER JOIN equipamentos e ON d.equipamento_id = e.id
            LEFT JOIN fornecedores f ON d.fornecedor_id = f.id
            ORDER BY $coluna_sql $direcao
            LIMIT :limite OFFSET :offset";

    $stmt = $ligacao->prepare($sql);

    $stmt->bindValue(':limite', $registos_por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();

    $resultados = $stmt->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $err) {

    $erro = 'Aconteceu um erro ao carregar a documentação.';
    $resultados = [];
    $total_registos = 0;
    $total_paginas = 0;
}

$ligacao = null;

/*Cria o link de ordenação - Ao clicar novamente na mesma coluna, alterna entre ascendente e descendente*/
function link_ordenacao_documentacao($campo, $ordenar, $direcao)
{
    $nova_direcao = 'asc';

    if ($ordenar == $campo && $direcao == 'asc') {
        $nova_direcao = 'desc';
    }

    return '?ordenar=' . $campo .
        '&direcao=' . $nova_direcao;
}

/*Mostra o ícone correto da ordenação*/
function icone_ordenacao_documentacao($campo, $ordenar, $direcao)
{
    if ($ordenar != $campo) {
        return '<i class="fa-solid fa-sort ms-1"></i>';
    }

    if ($direcao == 'asc') {
        return '<i class="fa-solid fa-sort-up ms-1"></i>';
    }

    return '<i class="fa-solid fa-sort-down ms-1"></i>';
}

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<style>
    /*Fundo da página*/
    .documentacao-page {
        background: #f5f7fa;
        min-height: 100vh;
        padding: 24px;
    }

    /*Título principal*/
    .page-title {
        font-weight: 600;
        color: #1E3A5F;
        font-size: 1.8rem;
        margin-bottom: 0;
    }

    /*Subtítulo*/
    .page-subtitle {
        color: #64748b;
        font-size: 0.95rem;
        margin-bottom: 0;
    }

    .exportar-btn {
        background: #e8f5ee;
        border: 1px solid #cfead9;
        color: #198754;
        border-radius: 10px;
        font-weight: 600;
    }

    .exportar-btn:hover {
        background: #d9f0e3;
        border-color: #badfc9;
        color: #146c43;
    }

    /*Botão principal da página*/
    .novo-btn {
        background: #2F5D8A;
        border-color: #2F5D8A;
        color: #fff;
        border-radius: 10px;
        font-weight: 600;
    }

    .novo-btn:hover {
        background: #1E3A5F;
        border-color: #1E3A5F;
        color: #fff;
    }

    /*Cartão branco que envolve a tabela*/
    .content-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 18px;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        border: 1px solid #e5e7eb;
    }

    /*Cabeçalho da tabela*/
    .table-primary-custom th {
        background: #2F5D8A !important;
        color: #ffffff !important;
        border-color: #2F5D8A !important;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .table-primary-custom a {
        color: #ffffff;
        text-decoration: none;
    }

    .table td {
        font-size: 0.9rem;
        vertical-align: middle;
    }

    /*Botões de ação*/
    .action-btn {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 0.72rem;
        font-weight: 600;
        text-decoration: none;
        margin-right: 4px;
        transition: 0.2s;
        white-space: nowrap;
    }

    .action-consultar {
        background: #e8f5ee;
        color: #198754;
    }

    .action-editar {
        background: #fff6dd;
        color: #c79200;
    }

    .action-eliminar {
        background: #fdeaea;
        color: #dc3545;
    }

    .action-btn:hover {
        opacity: 0.85;
    }

    /*Paginação centrada*/
    .pagination-wrapper {
        display: flex;
        justify-content: center;
        margin-top: 22px;
        margin-bottom: 6px;
    }

    .pagination .page-link {
        color: #2F5D8A;
        border-radius: 8px;
        margin: 0 2px;
    }

    .pagination .page-item.active .page-link {
        background-color: #2F5D8A;
        border-color: #2F5D8A;
        color: #fff;
    }

    /*Mensagem de erro*/
    .mensagem-erro-custom {
        background: #fdeaea;
        color: #bb2d3b;
        border: 1px solid #f8d3d3;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 16px;
    }
</style>

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 documentacao-page">

            <div class="d-flex justify-content-between align-items-start mb-3">

                <div>

                    <!-- Título principal -->
                    <h2 class="page-title mb-1">
                        <i class="fas fa-file-medical me-2"></i>
                        Listagem de Documentação
                    </h2>

                    <!-- Subtítulo explicativo -->
                    <p class="page-subtitle">
                        Gestão da documentação técnica, contratos, certificados e manuais associados aos equipamentos médicos.
                    </p>

                </div>

                <!-- Botões para criar novo documento/exportar excel -->
                <div class="d-flex gap-2">

                    <a href="exportar-documentacao.php"
                        class="btn btn-sm exportar-btn">
                        <i class="fa-solid fa-file-excel me-1"></i>
                        Exportar Excel
                    </a>

                    <a href="novo.php"
                        class="btn btn-sm novo-btn">
                        <i class="fa-solid fa-plus me-1"></i>
                        Novo documento
                    </a>

                </div>

            </div>

            <?php if (!empty($erro)) : ?>

                <div class="mensagem-erro-custom text-center">
                    <?= htmlspecialchars($erro) ?>
                </div>

            <?php else : ?>

                <?php if (count($resultados) == 0) : ?>

                    <div class="alert alert-info">
                        Não existem documentos registados.
                    </div>

                <?php else : ?>

                    <div class="content-card">

                        <div class="table-responsive">

                            <table class="table table-bordered table-hover align-middle mb-0">

                                <thead class="table-primary-custom">
                                    <tr>
                                        <th>
                                            <a href="<?= link_ordenacao_documentacao('nome', $ordenar, $direcao) ?>">
                                                Nome <?= icone_ordenacao_documentacao('nome', $ordenar, $direcao) ?>
                                            </a>
                                        </th>

                                        <th>
                                            <a href="<?= link_ordenacao_documentacao('tipo', $ordenar, $direcao) ?>">
                                                Tipo <?= icone_ordenacao_documentacao('tipo', $ordenar, $direcao) ?>
                                            </a>
                                        </th>

                                        <th>
                                            <a href="<?= link_ordenacao_documentacao('equipamento', $ordenar, $direcao) ?>">
                                                Equipamento <?= icone_ordenacao_documentacao('equipamento', $ordenar, $direcao) ?>
                                            </a>
                                        </th>

                                        <th>
                                            <a href="<?= link_ordenacao_documentacao('fornecedor', $ordenar, $direcao) ?>">
                                                Fornecedor <?= icone_ordenacao_documentacao('fornecedor', $ordenar, $direcao) ?>
                                            </a>
                                        </th>

                                        <th>
                                            <a href="<?= link_ordenacao_documentacao('data', $ordenar, $direcao) ?>">
                                                Data <?= icone_ordenacao_documentacao('data', $ordenar, $direcao) ?>
                                            </a>
                                        </th>

                                        <th>
                                            <a href="<?= link_ordenacao_documentacao('validade', $ordenar, $direcao) ?>">
                                                Validade <?= icone_ordenacao_documentacao('validade', $ordenar, $direcao) ?>
                                            </a>
                                        </th>

                                        <th>Ações</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    <?php foreach ($resultados as $documento) : ?>

                                        <tr>
                                            <td><?= htmlspecialchars($documento->nome_documento) ?></td>

                                            <td><?= htmlspecialchars($documento->tipo_documento) ?></td>

                                            <td>
                                                <?= htmlspecialchars($documento->codigo_inventario) ?>
                                                -
                                                <?= htmlspecialchars($documento->designacao) ?>
                                            </td>

                                            <td>
                                                <?php if (!empty($documento->nome_empresa)) : ?>
                                                    <?= htmlspecialchars($documento->nome_empresa) ?>
                                                <?php else : ?>
                                                    Sem fornecedor
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <?= !empty($documento->data_documento)
                                                    ? date('d/m/Y', strtotime($documento->data_documento))
                                                    : '-' ?>
                                            </td>

                                            <td>
                                                <?= !empty($documento->data_validade)
                                                    ? date('d/m/Y', strtotime($documento->data_validade))
                                                    : '-' ?>
                                            </td>

                                            <td>
                                                <a href="detalhes.php?id=<?= $documento->id ?>"
                                                    class="action-btn action-consultar">
                                                    <i class="fa-solid fa-eye me-1"></i>
                                                    Consultar
                                                </a>

                                                <a href="editar.php?id=<?= $documento->id ?>"
                                                    class="action-btn action-editar">
                                                    <i class="fa-regular fa-pen-to-square me-1"></i>
                                                    Editar
                                                </a>

                                                <a href="apagar.php?id=<?= $documento->id ?>"
                                                    class="action-btn action-eliminar">
                                                    <i class="fa-solid fa-trash-can me-1"></i>
                                                    Eliminar
                                                </a>
                                            </td>
                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

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

                <?php endif; ?>

            <?php endif; ?>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>