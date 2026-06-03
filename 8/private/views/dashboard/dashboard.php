<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$total_equipamentos = 0;
$total_ativos = 0;
$total_manutencao = 0;
$total_inativos = 0;
$total_abatidos = 0;

$erro = '';

try {

    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST .
        ";dbname=" . MYSQL_DATABASE .
        ";charset=utf8",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );

    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $total_equipamentos = $ligacao
        ->query("SELECT COUNT(*) FROM equipamentos")
        ->fetchColumn();

    $total_ativos = $ligacao
        ->query("SELECT COUNT(*) FROM equipamentos WHERE estado = 'Ativo'")
        ->fetchColumn();

    $total_manutencao = $ligacao
        ->query("SELECT COUNT(*) FROM equipamentos WHERE estado = 'Em manutenção'")
        ->fetchColumn();

    $total_inativos = $ligacao
        ->query("SELECT COUNT(*) FROM equipamentos WHERE estado = 'Inativo'")
        ->fetchColumn();

    $total_abatidos = $ligacao
        ->query("SELECT COUNT(*) FROM equipamentos WHERE estado = 'Abatido'")
        ->fetchColumn();

} catch (PDOException $err) {

    $erro = 'Aconteceu um erro ao carregar os indicadores do dashboard.';

}

$ligacao = null;

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 p-4">

            <h2>
                <i class="fas fa-chart-line me-2"></i>Dashboard
            </h2>

            <p>Resumo geral do sistema de gestão e inventário hospitalar.</p>

            <?php if (!empty($erro)) : ?>
                <div class="mensagem-erro">
                    <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <div class="row mt-4">

                <div class="col-md-3 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5>Total de Equipamentos</h5>
                            <p class="display-6"><?= $total_equipamentos ?></p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5>Equipamentos Ativos</h5>
                            <p class="display-6"><?= $total_ativos ?></p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5>Em Manutenção</h5>
                            <p class="display-6"><?= $total_manutencao ?></p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5>Equipamentos Inativos</h5>
                            <p class="display-6"><?= $total_inativos ?></p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5>Equipamentos Abatidos</h5>
                            <p class="display-6"><?= $total_abatidos ?></p>
                        </div>
                    </div>
                </div>

            </div>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>