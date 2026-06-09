<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$erros = [];
$sucesso = '';
$localizacao = null;

$edificios_opcoes = ['Edifício A', 'Edifício B', 'Edifício C', 'Edifício D'];
$pisos_opcoes = ['Piso -1', 'Piso 0', 'Piso 1', 'Piso 2', 'Piso 3', 'Piso 4'];
$servicos_opcoes = [
    'Urgência',
    'Unidade de Cuidados Intensivos',
    'Bloco Operatório',
    'Medicina Interna',
    'Consulta Externa',
    'Imagiologia',
    'Laboratório',
    'Cardiologia',
    'Pediatria',
    'Esterilização',
    'Reabilitação'
];

if ($id <= 0) {
    $erros[] = 'Localização inválida.';
} else {
    try {
        $ligacao = new PDO(
            "mysql:host=" . MYSQL_HOST . ";dbname=" . MYSQL_DATABASE . ";charset=utf8",
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

                $stmt = $ligacao->prepare(
                    "UPDATE localizacoes SET
                        edificio = :edificio,
                        piso = :piso,
                        servico = :servico,
                        sala = :sala,
                        observacoes = :observacoes
                     WHERE id = :id"
                );

                $stmt->execute([
                    ':edificio' => $edificio,
                    ':piso' => $piso,
                    ':servico' => $servico,
                    ':sala' => $sala,
                    ':observacoes' => $observacoes,
                    ':id' => $id
                ]);

                $sucesso = 'Dados da localização atualizados com sucesso.';
            }
        }

        $stmt = $ligacao->prepare(
            "SELECT *
             FROM localizacoes
             WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
        $localizacao = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$localizacao) {
            $erros[] = 'Localização não encontrada.';
        }
    } catch (PDOException $err) {
        $erros[] = 'Não foi possível atualizar a localização.';
    }

    $ligacao = null;
}

function h($valor)
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

function selecionado($valor_atual, $valor_opcao)
{
    return $valor_atual == $valor_opcao ? 'selected' : '';
}

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<style>
    .editar-page {
        background: #f5f7fa;
        min-height: 100vh;
        padding: 24px;
    }

    .page-title {
        font-weight: 700;
        color: #1E3A5F;
        font-size: 1.8rem;
        margin-bottom: 0;
    }

    .page-subtitle {
        color: #64748b;
        font-size: 0.95rem;
        margin-bottom: 0;
    }

    .location-strip,
    .content-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 5px 14px rgba(15, 23, 42, 0.05);
    }

    .location-strip {
        padding: 12px 14px;
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .location-name {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 800;
        margin-bottom: 2px;
    }

    .location-path {
        color: #52677d;
        font-size: 0.84rem;
    }

    .location-count {
        background: #e8f1fb;
        color: #1E3A5F;
        border-radius: 999px;
        padding: 5px 10px;
        font-size: 0.76rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .content-card {
        padding: 16px;
    }

    .form-section-title {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #1E3A5F;
        font-weight: 800;
        font-size: 0.98rem;
        margin-bottom: 12px;
        padding-bottom: 9px;
        border-bottom: 1px solid #e8eef5;
    }

    .form-section-title::before {
        content: "";
        width: 4px;
        height: 18px;
        border-radius: 999px;
        background: #2F5D8A;
    }

    .form-hint {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
        color: #52677d;
        font-size: 0.84rem;
        padding: 9px 11px;
        margin-bottom: 14px;
    }

    .form-label {
        font-weight: 800;
        color: #172033;
        font-size: 0.84rem;
        margin-bottom: 5px;
    }

    .form-control,
    .form-select {
        border-radius: 8px;
        border: 1px solid #dbe3ec;
        font-size: 0.88rem;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #2F5D8A;
        box-shadow: 0 0 0 0.15rem rgba(47, 93, 138, 0.18);
    }

    textarea.form-control {
        resize: vertical;
        min-height: 38px;
    }

    .btn-cancelar-custom,
    .btn-guardar-custom {
        border-radius: 8px;
        font-weight: 700;
        padding: 8px 14px;
    }

    .btn-cancelar-custom {
        background: #fff;
        border: 1px solid #dbe3ec;
        color: #1E3A5F;
    }

    .btn-cancelar-custom:hover {
        background: #f6faff;
        color: #1E3A5F;
    }

    .btn-guardar-custom {
        background: #2F5D8A;
        border: 1px solid #2F5D8A;
        color: #ffffff;
    }

    .btn-guardar-custom:hover {
        background: #1E3A5F;
        color: #ffffff;
    }

    .mensagem-erro,
    .mensagem-sucesso {
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 14px;
    }

    .mensagem-erro {
        background: #fdeaea;
        color: #bb2d3b;
        border: 1px solid #f8d3d3;
    }

    .mensagem-sucesso {
        background: #e8f5ee;
        color: #198754;
        border: 1px solid #cfead9;
    }
</style>

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 editar-page">

            <div class="mb-3">
                <h2 class="page-title mb-1">
                    <i class="fa-regular fa-pen-to-square me-2"></i>
                    Editar dados da localização
                </h2>
                <p class="page-subtitle">Atualização dos dados físicos e funcionais da localização hospitalar.</p>
            </div>

            <?php if (!empty($erros)) : ?>
                <div class="mensagem-erro">
                    <?php foreach ($erros as $erro) : ?>
                        <div><?= h($erro) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($sucesso)) : ?>
                <div class="mensagem-sucesso"><?= h($sucesso) ?></div>
            <?php endif; ?>

            <?php if ($localizacao) : ?>
                <div class="location-strip">
                    <div>
                        <div class="location-name"><?= h($localizacao->servico) ?></div>
                        <div class="location-path"><?= h($localizacao->edificio . ' · ' . $localizacao->piso . ' · ' . $localizacao->sala) ?></div>
                    </div>
                    <span class="location-count">Localização atual</span>
                </div>

                <div class="content-card">
                    <form action="editar.php?id=<?= $localizacao->id ?>" method="post" novalidate>
                        <div class="form-hint">
                            Os valores atuais já estão preenchidos. Altere apenas os campos necessários.
                        </div>

                        <div class="row g-3">
                            <div class="col-lg-8">
                                <h5 class="form-section-title">Dados do espaço</h5>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Edifício</label>
                                        <select name="edificio" class="form-select">
                                            <option value="">Escolha o edifício</option>
                                            <?php foreach ($edificios_opcoes as $opcao) : ?>
                                                <option value="<?= h($opcao) ?>" <?= selecionado($localizacao->edificio, $opcao) ?>><?= h($opcao) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Piso</label>
                                        <select name="piso" class="form-select">
                                            <option value="">Escolha o piso</option>
                                            <?php foreach ($pisos_opcoes as $opcao) : ?>
                                                <option value="<?= h($opcao) ?>" <?= selecionado($localizacao->piso, $opcao) ?>><?= h($opcao) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-8">
                                        <label class="form-label">Serviço / Departamento</label>
                                        <select name="servico" class="form-select">
                                            <option value="">Escolha o serviço/departamento</option>
                                            <?php foreach ($servicos_opcoes as $opcao) : ?>
                                                <option value="<?= h($opcao) ?>" <?= selecionado($localizacao->servico, $opcao) ?>><?= h($opcao) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Sala</label>
                                        <input type="text" name="sala" class="form-control" value="<?= h($localizacao->sala) ?>" placeholder="Indique a sala">
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <h5 class="form-section-title">Observações</h5>
                                <label class="form-label">Notas internas</label>
                                <textarea name="observacoes" rows="5" class="form-control" placeholder="Opcional"><?= h($localizacao->observacoes) ?></textarea>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <a href="detalhes.php?id=<?= $localizacao->id ?>" class="btn btn-cancelar-custom">
                                <i class="fa-solid fa-xmark me-1"></i>
                                Cancelar
                            </a>

                            <button type="submit" class="btn btn-guardar-custom">
                                <i class="fa-regular fa-floppy-disk me-1"></i>
                                Guardar dados da localização
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>
