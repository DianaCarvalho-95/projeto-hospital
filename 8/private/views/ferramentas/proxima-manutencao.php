<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 p-4">

            <h2>Próxima Manutenção</h2>
            <p>Calcula automaticamente a próxima data de manutenção.</p>

            <div class="card p-4 mt-4">
                <h4>
                    <i class="fa-solid fa-calendar-check me-2"></i>
                    Cálculo da Próxima Manutenção
                </h4>

                <hr>

                <form>
                    <div class="mb-3">
                        <label for="dataUltima" class="form-label">Data da última manutenção:</label>
                        <input type="date" id="dataUltima" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label for="meses" class="form-label">Periodicidade (meses):</label>
                        <input type="number" id="meses" min="1" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Próxima manutenção:</label>
                        <div id="resultado" class="alert alert-info p-2 text-center"></div>
                    </div>
                </form>
            </div>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>