<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$erros = [];
$sucesso = '';
$localizacao = null;

if ($id <= 0) {

    $erros[] = 'Localização inválida.';

} else {

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

        /*Se o formulário for submetido, os dados são recolhidos,
          validados e posteriormente atualizados na base de dados*/
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

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

            /*Atualização da localização caso não existam erros*/
            if (empty($erros)) {

                /*A sala é guardada em maiúsculas para manter uma normalização dos dados*/
                $sala = strtoupper($sala);

                $sql = "UPDATE localizacoes SET
                            edificio = :edificio,
                            piso = :piso,
                            servico = :servico,
                            sala = :sala,
                            observacoes = :observacoes
                        WHERE id = :id";

                $stmt = $ligacao->prepare($sql);

                $stmt->execute([
                    ':edificio' => $edificio,
                    ':piso' => $piso,
                    ':servico' => $servico,
                    ':sala' => $sala,
                    ':observacoes' => $observacoes,
                    ':id' => $id
                ]);

                $sucesso = 'Localização atualizada com sucesso.';
            }
        }

        /*Carrega a localização atual*/
        $stmt = $ligacao->prepare(
            "SELECT *
             FROM localizacoes
             WHERE id = :id"
        );

        $stmt->execute([
            ':id' => $id
        ]);

        $localizacao = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$localizacao) {
            $erros[] = 'Localização não encontrada.';
        }

    } catch (PDOException $err) {

        $erros[] = 'Não foi possível atualizar a localização.';
    }

    $ligacao = null;
}

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<style>
    /*Fundo da página*/
    .editar-page {
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

    /*Subtítulo da página*/
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

    /*Mensagem de erro*/
    .mensagem-erro {
        background: #fdeaea;
        color: #bb2d3b;
        border: 1px solid #f8d3d3;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 16px;
    }

    /*Mensagem de sucesso*/
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

        <main class="col-md-9 col-lg-10 editar-page">

            <div class="mb-3">

                <!-- Título principal -->
                <h2 class="page-title mb-1">
                    <i class="fa-regular fa-pen-to-square me-2"></i>
                    Editar Localização
                </h2>

                <!-- Subtítulo explicativo -->
                <p class="page-subtitle">
                    Atualização dos dados físicos e funcionais de uma localização hospitalar.
                </p>

            </div>

            <?php if (!empty($erros)) : ?>

                <div class="mensagem-erro">
                    <?php foreach ($erros as $erro) : ?>
                        <div><?= htmlspecialchars($erro) ?></div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>

            <?php if (!empty($sucesso)) : ?>

                <div class="mensagem-sucesso">
                    <?= htmlspecialchars($sucesso) ?>
                </div>

            <?php endif; ?>

            <?php if ($localizacao) : ?>

                <div class="content-card">

                    <form action="editar.php?id=<?= $localizacao->id ?>" method="post" novalidate>

                        <div class="row">

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Edifício</label>

                                <select name="edificio" class="form-control">
                                    <option value="">Escolha um edifício</option>
                                    <option value="Edifício A" <?= $localizacao->edificio == 'Edifício A' ? 'selected' : '' ?>>Edifício A</option>
                                    <option value="Edifício B" <?= $localizacao->edificio == 'Edifício B' ? 'selected' : '' ?>>Edifício B</option>
                                    <option value="Edifício C" <?= $localizacao->edificio == 'Edifício C' ? 'selected' : '' ?>>Edifício C</option>
                                    <option value="Edifício D" <?= $localizacao->edificio == 'Edifício D' ? 'selected' : '' ?>>Edifício D</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Piso</label>

                                <select name="piso" class="form-control">
                                    <option value="">Escolha um piso</option>
                                    <option value="-1" <?= $localizacao->piso == '-1' ? 'selected' : '' ?>>-1</option>
                                    <option value="0" <?= $localizacao->piso == '0' ? 'selected' : '' ?>>0</option>
                                    <option value="1" <?= $localizacao->piso == '1' ? 'selected' : '' ?>>1</option>
                                    <option value="2" <?= $localizacao->piso == '2' ? 'selected' : '' ?>>2</option>
                                    <option value="3" <?= $localizacao->piso == '3' ? 'selected' : '' ?>>3</option>
                                    <option value="4" <?= $localizacao->piso == '4' ? 'selected' : '' ?>>4</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Serviço / Departamento</label>

                                <select name="servico" class="form-control">
                                    <option value="">Escolha um serviço</option>
                                    <option value="Urgência" <?= $localizacao->servico == 'Urgência' ? 'selected' : '' ?>>Urgência</option>
                                    <option value="Unidade de Cuidados Intensivos" <?= $localizacao->servico == 'Unidade de Cuidados Intensivos' ? 'selected' : '' ?>>Unidade de Cuidados Intensivos</option>
                                    <option value="Bloco Operatório" <?= $localizacao->servico == 'Bloco Operatório' ? 'selected' : '' ?>>Bloco Operatório</option>
                                    <option value="Medicina Interna" <?= $localizacao->servico == 'Medicina Interna' ? 'selected' : '' ?>>Medicina Interna</option>
                                    <option value="Consulta Externa" <?= $localizacao->servico == 'Consulta Externa' ? 'selected' : '' ?>>Consulta Externa</option>
                                    <option value="Imagiologia" <?= $localizacao->servico == 'Imagiologia' ? 'selected' : '' ?>>Imagiologia</option>
                                    <option value="Laboratório" <?= $localizacao->servico == 'Laboratório' ? 'selected' : '' ?>>Laboratório</option>
                                    <option value="Cardiologia" <?= $localizacao->servico == 'Cardiologia' ? 'selected' : '' ?>>Cardiologia</option>
                                    <option value="Pediatria" <?= $localizacao->servico == 'Pediatria' ? 'selected' : '' ?>>Pediatria</option>
                                    <option value="Esterilização" <?= $localizacao->servico == 'Esterilização' ? 'selected' : '' ?>>Esterilização</option>
                                    <option value="Reabilitação" <?= $localizacao->servico == 'Reabilitação' ? 'selected' : '' ?>>Reabilitação</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Sala</label>

                                <input type="text"
                                       name="sala"
                                       class="form-control"
                                       value="<?= htmlspecialchars($localizacao->sala) ?>"
                                       placeholder="Ex.: U01, BO01, LAB02">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Observações</label>

                                <textarea name="observacoes"
                                          rows="2"
                                          class="form-control"><?= htmlspecialchars($localizacao->observacoes) ?></textarea>
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
                                Guardar alterações
                            </button>

                        </div>

                    </form>

                </div>

            <?php endif; ?>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>