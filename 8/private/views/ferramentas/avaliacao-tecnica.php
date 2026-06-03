<?php

require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 p-4">

            <h2>Avaliação Técnica</h2>
            <p>Avalia rapidamente o estado técnico do equipamento.</p>

            <div class="card p-4 mt-4">
                <h4><i class="fa-solid fa-stethoscope me-2"></i>Avaliação Técnica do Equipamento</h4>
                <hr>

                <form oninput="avaliarEstadoTecnico()">
                    <input type="checkbox" id="precisaCalibracao">
                    <label for="precisaCalibracao">Precisa de calibração</label><br>

                    <input type="checkbox" id="emManutencao">
                    <label for="emManutencao">Está em manutenção</label><br>

                    <input type="checkbox" id="avariaReportada">
                    <label for="avariaReportada">Tem avaria reportada</label>

                    <div class="mt-3">
                        <label class="form-label">Recomendação:</label>
                        <div id="mensagemTecnica" class="alert alert-info p-2 text-center"></div>
                    </div>
                </form>
            </div>

        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>