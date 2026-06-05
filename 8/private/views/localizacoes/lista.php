<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$erro = '';
$resultados = [];

try {

    /*Ligação à base de dados.*/
    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST .
            ";dbname=" . MYSQL_DATABASE .
            ";charset=utf8",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );

    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    /*Lista todas as localizações e conta quantos equipamentos
    estão atualmente associados a cada localização.*/
    $resultados = $ligacao
        ->query(
            "SELECT
                l.*,
                COUNT(e.id) AS total_equipamentos
             FROM localizacoes l
             LEFT JOIN equipamentos e
                ON e.localizacao_id = l.id
             GROUP BY l.id
             ORDER BY l.edificio, l.piso, l.servico, l.sala"
        )
        ->fetchAll(PDO::FETCH_OBJ);

} catch (PDOException $err) {

    $erro = 'Aconteceu um erro ao carregar as localizações.';
    $resultados = [];
}

$ligacao = null;

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<style>
    /*Fundo da página*/
    .localizacoes-page {
        background: #f5f7fa;
        min-height: 100vh;
        padding: 24px;
    }

    /*Título principal da página*/
    .page-title {
        font-weight: 600;
        color: #1E3A5F;
        font-size: 1.8rem;
        margin-bottom: 0;
    }

    /*Texto auxiliar abaixo do título*/
    .page-subtitle {
        color: #64748b;
        font-size: 0.95rem;
        margin-bottom: 0;
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

    /*Cartão branco que envolve a tabela.*/
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

    /*Texto das células.*/
    .table td {
        font-size: 0.9rem;
        vertical-align: middle;
    }

    /*Botões das ações*/
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

        <main class="col-md-9 col-lg-10 localizacoes-page">

            <div class="d-flex justify-content-between align-items-start mb-3">

                <div>

                    <!-- Título principal -->
                    <h2 class="page-title mb-1">
                        <i class="fas fa-location-dot me-2"></i>
                        Listagem de Localizações
                    </h2>

                    <!-- Subtítulo explicativo -->
                    <p class="page-subtitle">
                        Consulta das localizações hospitalares e dos equipamentos associados a cada espaço.
                    </p>

                </div>

                <!-- Botão para criar uma nova localização -->
                <a href="novo.php" class="btn btn-sm novo-btn">
                    <i class="fa-solid fa-plus me-1"></i>
                    Nova localização
                </a>

            </div>

            <?php if (!empty($erro)) : ?>

                <div class="mensagem-erro-custom text-center">
                    <?= htmlspecialchars($erro) ?>
                </div>

            <?php else : ?>

                <?php if (count($resultados) == 0) : ?>

                    <div class="alert alert-info">
                        Não existem localizações registadas.
                    </div>

                <?php else : ?>

                    <div class="content-card">

                        <div class="table-responsive">

                            <table class="table table-bordered table-hover align-middle mb-0">

                                <thead class="table-primary-custom">
                                    <tr>
                                        <th>Edifício</th>
                                        <th>Piso</th>
                                        <th>Serviço / Departamento</th>
                                        <th>Sala</th>
                                        <th>N.º Equipamentos</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    <?php foreach ($resultados as $localizacao) : ?>

                                        <tr>
                                            <td><?= htmlspecialchars($localizacao->edificio) ?></td>
                                            <td><?= htmlspecialchars($localizacao->piso) ?></td>
                                            <td><?= htmlspecialchars($localizacao->servico) ?></td>
                                            <td><?= htmlspecialchars($localizacao->sala) ?></td>
                                            <td><?= $localizacao->total_equipamentos ?></td>

                                            <td>

                                                <a href="detalhes.php?id=<?= $localizacao->id ?>"
                                                   class="action-btn action-consultar">

                                                    <i class="fa-solid fa-eye me-1"></i>
                                                    Consultar

                                                </a>

                                                <a href="editar.php?id=<?= $localizacao->id ?>"
                                                   class="action-btn action-editar">

                                                    <i class="fa-regular fa-pen-to-square me-1"></i>
                                                    Editar

                                                </a>

                                                <a href="apagar.php?id=<?= $localizacao->id ?>"
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

                <?php endif; ?>

            <?php endif; ?>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>