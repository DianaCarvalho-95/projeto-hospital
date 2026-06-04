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

        $ligacao = new PDO(
            "mysql:host=" . MYSQL_HOST .
                ";dbname=" . MYSQL_DATABASE .
                ";charset=utf8",
            MYSQL_USERNAME,
            MYSQL_PASSWORD
        );

        $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $edificio = isset($_POST['edificio']) ? trim($_POST['edificio']) : '';
            $piso = isset($_POST['piso']) ? trim($_POST['piso']) : '';
            $servico = isset($_POST['servico']) ? trim($_POST['servico']) : '';
            $sala = isset($_POST['sala']) ? trim($_POST['sala']) : '';
            $observacoes = isset($_POST['observacoes']) ? trim($_POST['observacoes']) : '';

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

            if (empty($erros)) {

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

        $stmt = $ligacao->prepare(
            "SELECT * FROM localizacoes
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

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 p-4">

            <h2>
                <i class="fa-regular fa-pen-to-square me-2"></i>
                Editar Localização
            </h2>

            <hr>

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

                <form action="editar.php?id=<?= $localizacao->id ?>" method="post" novalidate>

                    <div class="mb-3">
                        <label class="form-label">Edifício</label>
                        <select name="edificio" class="form-control">
                            <option value="">Escolha um edifício</option>
                            <option value="Edifício A" <?= $localizacao->edificio == 'Edifício A' ? 'selected' : '' ?>>Edifício A</option>
                            <option value="Edifício B" <?= $localizacao->edificio == 'Edifício B' ? 'selected' : '' ?>>Edifício B</option>
                            <option value="Edifício C" <?= $localizacao->edificio == 'Edifício C' ? 'selected' : '' ?>>Edifício C</option>
                            <option value="Edifício D" <?= $localizacao->edificio == 'Edifício D' ? 'selected' : '' ?>>Edifício D</option>
                        </select>
                    </div>

                    <div class="mb-3">
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

                    <div class="mb-3">
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

                    <div class="mb-3">
                        <label class="form-label">Sala</label>
                        <input type="text" name="sala" class="form-control"
                               value="<?= htmlspecialchars($localizacao->sala) ?>"
                               placeholder="Ex.: U01, BO01, LAB02">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" rows="4" class="form-control"><?= htmlspecialchars($localizacao->observacoes) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <a href="lista.php" class="btn btn-secondary">
                            <i class="fa-solid fa-xmark me-1"></i>
                            Cancelar
                        </a>

                        <button type="submit" class="btn btn-success">
                            <i class="fa-regular fa-floppy-disk me-1"></i>
                            Guardar alterações
                        </button>
                    </div>

                </form>

            <?php endif; ?>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>