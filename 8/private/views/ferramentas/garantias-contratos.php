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

            <h2>Garantias e Contratos</h2>
            <p>Consulta de informação sobre garantias e contratos associados aos equipamentos.</p>

            <div class="card p-4 mt-4">
                <h4><i class="fa-solid fa-file-signature me-2"></i>Consulta de Garantias e Contratos</h4>
                <hr>

                <p><strong>Equipamento:</strong> Monitor Multiparamétrico</p>
                <p><strong>Fornecedor:</strong> MedEquip Portugal</p>
                <p><strong>Tipo:</strong> Contrato de manutenção</p>
                <p><strong>Estado:</strong> Ativo</p>

                <div class="alert alert-info text-center">
                    Funcionalidade preparada para futura integração com base de dados.
                </div>
            </div>

        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>