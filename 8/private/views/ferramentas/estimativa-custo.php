<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 p-4">

            <h2>Estimativa de Custo</h2>
            <p>Calcula um custo estimado de intervenção técnica.</p>

            <div class="card p-4 mt-4">
                <h4><i class="fa-solid fa-euro-sign me-2"></i>Estimativa de Custo</h4>
                <hr>

                <form oninput="calcularEstimativaCusto()">
                    <div class="mb-3">
                        <label for="tipoIntervencao" class="form-label">Tipo de Intervenção:</label>
                        <select id="tipoIntervencao" class="form-control">
                            <option value="">Escolha uma opção</option>
                            <option value="preventiva">Manutenção preventiva</option>
                            <option value="corretiva">Manutenção corretiva</option>
                            <option value="calibracao">Calibração</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="nivelUrgencia" class="form-label">Nível de Urgência:</label>
                        <select id="nivelUrgencia" class="form-control">
                            <option value="">Escolha uma opção</option>
                            <option value="normal">Normal</option>
                            <option value="urgente">Urgente</option>
                            <option value="critica">Crítica</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Custo estimado:</label>
                        <div id="resultadoCusto" class="alert alert-info p-2 text-center"></div>
                    </div>
                </form>
            </div>

        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>