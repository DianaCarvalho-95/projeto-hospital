<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 p-4">

            <h2>Relatórios</h2>
            <p>Área destinada à geração de relatórios do sistema.</p>

            <div class="card p-4 mt-4">
                <h4><i class="fa-solid fa-chart-column me-2"></i>Relatórios</h4>
                <hr>

                <p>- Equipamentos por localização</p>
                <p>- Equipamentos em manutenção</p>
                <p>- Contratos a expirar</p>
                <p>- Documentação por equipamento</p>

                <div class="alert alert-info text-center">
                    Funcionalidade preparada para futura integração com base de dados.
                </div>
            </div>

        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>