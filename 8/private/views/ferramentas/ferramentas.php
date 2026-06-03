<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 p-4">

            <section>
                <h2>Ferramentas</h2>
                <p>Escolhe uma ferramenta de apoio à gestão técnica para continuar.</p>
            </section>

            <div class="row justify-content-center mt-5">

                <div class="col-md-3 mb-4">
                    <a href="proxima-manutencao.php" class="text-decoration-none text-dark">
                        <div class="card text-center p-4">
                            <i class="fa-solid fa-calendar-check fa-2x mb-3"></i>
                            <p>Próxima manutenção</p>
                        </div>
                    </a>
                </div>

                <div class="col-md-3 mb-4">
                    <a href="avaliacao-tecnica.php" class="text-decoration-none text-dark">
                        <div class="card text-center p-4">
                            <i class="fa-solid fa-stethoscope fa-2x mb-3"></i>
                            <p>Avaliação técnica</p>
                        </div>
                    </a>
                </div>

                <div class="col-md-3 mb-4">
                    <a href="estimativa-custo.php" class="text-decoration-none text-dark">
                        <div class="card text-center p-4">
                            <i class="fa-solid fa-euro-sign fa-2x mb-3"></i>
                            <p>Estimativa de custo</p>
                        </div>
                    </a>
                </div>

                <div class="col-md-3 mb-4">
                    <a href="garantias-contratos.php" class="text-decoration-none text-dark">
                        <div class="card text-center p-4">
                            <i class="fa-solid fa-file-signature fa-2x mb-3"></i>
                            <p>Garantias e contratos</p>
                        </div>
                    </a>
                </div>

                <div class="col-md-3 mb-4">
                    <a href="relatorios.php" class="text-decoration-none text-dark">
                        <div class="card text-center p-4">
                            <i class="fa-solid fa-chart-column fa-2x mb-3"></i>
                            <p>Relatórios</p>
                        </div>
                    </a>
                </div>

                <div class="col-md-3 mb-4">
                    <a href="historico.php" class="text-decoration-none text-dark">
                        <div class="card text-center p-4">
                            <i class="fa-solid fa-clock-rotate-left fa-2x mb-3"></i>
                            <p>Histórico</p>
                        </div>
                    </a>
                </div>

            </div>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>