<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$erros = [];
$sucesso = '';

$edificio = '';
$piso = '';
$servico = '';
$sala = '';
$observacoes = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    /*Recolha dos dados enviados pelo formulário*/
    $edificio = isset($_POST['edificio']) ? trim($_POST['edificio']) : '';
    $piso = isset($_POST['piso']) ? trim($_POST['piso']) : '';
    $servico = isset($_POST['servico']) ? trim($_POST['servico']) : '';
    $sala = isset($_POST['sala']) ? trim($_POST['sala']) : '';
    $observacoes = isset($_POST['observacoes']) ? trim($_POST['observacoes']) : '';

    /*Validações obrigatórias*/
    if (empty($edificio)) {
        $erros[] = 'O edifício é obrigatório.';
    }

    if (empty($piso)) {
        $erros[] = 'O piso é obrigatório.';
    }

    if (empty($servico)) {
        $erros[] = 'O serviço/departamento é obrigatório.';
    }

    if (empty($sala)) {
        $erros[] = 'A sala é obrigatória.';
    }

    /*Se não existirem erros, a nova localização é inserida*/
    if (empty($erros)) {

        /*A sala é guardada em maiúsculas para manter coerência nos dados*/
        $sala = strtoupper($sala);

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

            /*Inserção da nova localização*/
            $sql = "INSERT INTO localizacoes
                    (edificio, piso, servico, sala, observacoes)
                    VALUES
                    (:edificio, :piso, :servico, :sala, :observacoes)";

            $stmt = $ligacao->prepare($sql);

            $stmt->execute([
                ':edificio' => $edificio,
                ':piso' => $piso,
                ':servico' => $servico,
                ':sala' => $sala,
                ':observacoes' => $observacoes
            ]);

            $sucesso = 'Localização inserida com sucesso.';

            /*Limpa os campos depois da inserção*/
            $edificio = '';
            $piso = '';
            $servico = '';
            $sala = '';
            $observacoes = '';

        } catch (PDOException $err) {

            $erros[] = 'Não foi possível inserir a localização.';
        }

        $ligacao = null;
    }
}

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<style>
    /*Fundo da página.*/
    .novo-page {
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

    /*Cartão branco que contém o formulário*/
    .content-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        border: 1px solid #e5e7eb;
    }

    /*Labels dos campos*/
    .form-label {
        font-weight: 600;
        color: #334155;
        font-size: 0.88rem;
    }

    /*Campos do formulário*/
    .form-control {
        border-radius: 10px;
        border: 1px solid #dbe3ec;
        font-size: 0.9rem;
    }

    .form-control:focus {
        border-color: #2F5D8A;
        box-shadow: 0 0 0 0.15rem rgba(47, 93, 138, 0.18);
    }

    /*Botão Cancelar*/
    .btn-cancelar-custom {
        background: #eef2f7;
        border: 1px solid #dbe3ec;
        color: #475569;
        border-radius: 8px;
        font-weight: 600;
        padding: 8px 16px;
    }

    .btn-cancelar-custom:hover {
        background: #e2e8f0;
        color: #334155;
    }

    /*Botão Guardar*/
    .btn-guardar-custom {
        background: #edf4ff;
        border: 1px solid #d6e7ff;
        color: #2F5D8A;
        border-radius: 8px;
        font-weight: 600;
        padding: 8px 16px;
    }

    .btn-guardar-custom:hover {
        background: #dcecff;
        color: #1E3A5F;
    }

    /*Mensagens de erro*/
    .mensagem-erro {
        background: #fdeaea;
        color: #bb2d3b;
        border: 1px solid #f8d3d3;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 16px;
    }

    /*Mensagens de sucesso*/
    .mensagem-sucesso {
        background: #e8f5ee;
        color: #198754;
        border: 1px solid #cfead9;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 16px;
    }
</style>

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 novo-page">

            <div class="mb-3">

                <!-- Título principal da página -->
                <h2 class="page-title mb-1">
                    <i class="fa-solid fa-location-dot me-2"></i>
                    Nova Localização
                </h2>

                <!-- Subtítulo explicativo -->
                <p class="page-subtitle">
                    Registo de novos espaços físicos associados aos equipamentos hospitalares.
                </p>

            </div>

            <?php if (!empty($erros)) : ?>

                <div class="mensagem-erro">
                    <strong>Foram encontrados os seguintes erros:</strong>

                    <ul class="mb-0 mt-2">
                        <?php foreach ($erros as $erro) : ?>
                            <li><?= htmlspecialchars($erro) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

            <?php endif; ?>

            <?php if (!empty($sucesso)) : ?>

                <div class="mensagem-sucesso">
                    <?= htmlspecialchars($sucesso) ?>
                </div>

            <?php endif; ?>

            <div class="content-card">

                <form action="novo.php" method="post" novalidate>

                    <div class="row">

                        <!-- Edifício -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Edifício</label>

                            <select name="edificio" class="form-control">
                                <option value="">Escolha um edifício</option>
                                <option value="Edifício A" <?= $edificio == 'Edifício A' ? 'selected' : '' ?>>Edifício A</option>
                                <option value="Edifício B" <?= $edificio == 'Edifício B' ? 'selected' : '' ?>>Edifício B</option>
                                <option value="Edifício C" <?= $edificio == 'Edifício C' ? 'selected' : '' ?>>Edifício C</option>
                                <option value="Edifício D" <?= $edificio == 'Edifício D' ? 'selected' : '' ?>>Edifício D</option>
                            </select>
                        </div>

                         <!-- Piso -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Piso</label>

                            <select name="piso" class="form-control">
                                <option value="">Escolha um piso</option>
                                <option value="-1" <?= $piso == '-1' ? 'selected' : '' ?>>-1</option>
                                <option value="0" <?= $piso == '0' ? 'selected' : '' ?>>0</option>
                                <option value="1" <?= $piso == '1' ? 'selected' : '' ?>>1</option>
                                <option value="2" <?= $piso == '2' ? 'selected' : '' ?>>2</option>
                                <option value="3" <?= $piso == '3' ? 'selected' : '' ?>>3</option>
                                <option value="4" <?= $piso == '4' ? 'selected' : '' ?>>4</option>
                            </select>
                        </div>

                         <!-- Serviço/Departamento -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Serviço / Departamento</label>

                            <select name="servico" class="form-control">
                                <option value="">Escolha um serviço</option>
                                <option value="Urgência" <?= $servico == 'Urgência' ? 'selected' : '' ?>>Urgência</option>
                                <option value="Unidade de Cuidados Intensivos" <?= $servico == 'Unidade de Cuidados Intensivos' ? 'selected' : '' ?>>Unidade de Cuidados Intensivos</option>
                                <option value="Bloco Operatório" <?= $servico == 'Bloco Operatório' ? 'selected' : '' ?>>Bloco Operatório</option>
                                <option value="Medicina Interna" <?= $servico == 'Medicina Interna' ? 'selected' : '' ?>>Medicina Interna</option>
                                <option value="Consulta Externa" <?= $servico == 'Consulta Externa' ? 'selected' : '' ?>>Consulta Externa</option>
                                <option value="Imagiologia" <?= $servico == 'Imagiologia' ? 'selected' : '' ?>>Imagiologia</option>
                                <option value="Laboratório" <?= $servico == 'Laboratório' ? 'selected' : '' ?>>Laboratório</option>
                                <option value="Cardiologia" <?= $servico == 'Cardiologia' ? 'selected' : '' ?>>Cardiologia</option>
                                <option value="Pediatria" <?= $servico == 'Pediatria' ? 'selected' : '' ?>>Pediatria</option>
                                <option value="Esterilização" <?= $servico == 'Esterilização' ? 'selected' : '' ?>>Esterilização</option>
                                <option value="Reabilitação" <?= $servico == 'Reabilitação' ? 'selected' : '' ?>>Reabilitação</option>
                            </select>
                        </div>

                         <!-- Sala -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sala</label>

                            <input type="text"
                                   name="sala"
                                   class="form-control"
                                   value="<?= htmlspecialchars($sala) ?>"
                                   placeholder="Ex.: U01, BO01, LAB02">
                        </div>

                         <!-- Observações -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Observações</label>

                            <textarea name="observacoes"
                                      rows="1"
                                      class="form-control"><?= htmlspecialchars($observacoes) ?></textarea>
                        </div>

                    </div>

                    <!-- Botões do formulário -->
                    <div class="d-flex gap-2 mt-2">

                        <a href="lista.php" class="btn btn-cancelar-custom">
                            <i class="fa-solid fa-xmark me-1"></i>
                            Cancelar
                        </a>

                        <button type="submit" class="btn btn-guardar-custom">
                            <i class="fa-regular fa-floppy-disk me-1"></i>
                            Guardar
                        </button>

                    </div>

                </form>

            </div>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>