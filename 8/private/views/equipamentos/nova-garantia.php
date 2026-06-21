<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

function guardar_pdf_opcional($campo, &$erros)
{
    if (!isset($_FILES[$campo]) || $_FILES[$campo]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES[$campo]['error'] !== UPLOAD_ERR_OK) {
        $erros[] = 'Não foi possível carregar o ficheiro.';
        return null;
    }

    $extensao = strtolower(pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION));
    if ($extensao !== 'pdf') {
        $erros[] = 'Só são permitidos ficheiros PDF.';
        return null;
    }

    if ($_FILES[$campo]['size'] > 10 * 1024 * 1024) {
        $erros[] = 'O ficheiro não pode ter mais de 10 MB.';
        return null;
    }

    $pasta = realpath(__DIR__ . '/../../uploads/documentos');
    if ($pasta === false) {
        $erros[] = 'A pasta de documentos não está disponível.';
        return null;
    }

    $nome_base = preg_replace('/[^a-zA-Z0-9_.-]/', '_', pathinfo($_FILES[$campo]['name'], PATHINFO_FILENAME));
    $nome_final = 'garantia_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '_' . $nome_base . '.pdf';
    $destino = $pasta . DIRECTORY_SEPARATOR . $nome_final;

    if (!move_uploaded_file($_FILES[$campo]['tmp_name'], $destino)) {
        $erros[] = 'Não foi possível guardar o ficheiro.';
        return null;
    }

    return $nome_final;
}

$erros = [];
$equipamentos = [];
$fornecedores = [];

$equipamento_id = isset($_GET['equipamento_id']) ? intval($_GET['equipamento_id']) : '';
$tipo_contrato = 'Garantia de fabricante';
$entidade_responsavel = '';
$data_inicio = date('Y-m-d');
$data_fim = '';
$periodicidade = 'Anual';
$existe_contrato = 1;
$observacoes = '';

try {
    $ligacao = new PDO(
        'mysql:host=' . MYSQL_HOST . ';port=' . MYSQL_PORT . ';dbname=' . MYSQL_DATABASE . ';charset=utf8mb4',
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );
    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $equipamentos = $ligacao->query('SELECT id, codigo_inventario, designacao FROM equipamentos ORDER BY codigo_inventario')->fetchAll(PDO::FETCH_OBJ);
    $fornecedores = $ligacao->query('SELECT nome_empresa FROM fornecedores ORDER BY nome_empresa')->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $err) {
    $erros[] = 'Não foi possível carregar os dados do formulário.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipamento_id = isset($_POST['equipamento_id']) ? intval($_POST['equipamento_id']) : '';
    $tipo_contrato = trim($_POST['tipo_contrato'] ?? '');
    $entidade_responsavel = trim($_POST['entidade_responsavel'] ?? '');
    $data_inicio = trim($_POST['data_inicio'] ?? '');
    $data_fim = trim($_POST['data_fim'] ?? '');
    $periodicidade = trim($_POST['periodicidade'] ?? '');
    $existe_contrato = isset($_POST['existe_contrato']) ? 1 : 0;
    $observacoes = trim($_POST['observacoes'] ?? '');

    if (empty($equipamento_id)) {
        $erros[] = 'O equipamento é obrigatório.';
    }
    if ($tipo_contrato === '') {
        $erros[] = 'O tipo de garantia/contrato é obrigatório.';
    }
    if ($entidade_responsavel === '') {
        $erros[] = 'A entidade responsável é obrigatória.';
    }
    if ($data_fim !== '' && $data_inicio !== '' && $data_fim < $data_inicio) {
        $erros[] = 'A data de fim não pode ser anterior à data de início.';
    }

    $ficheiro = guardar_pdf_opcional('ficheiro', $erros);

    if (empty($erros)) {
        try {
            $stmt = $ligacao->prepare(
                'INSERT INTO garantias_contratos (equipamento_id, data_inicio, data_fim, existe_contrato, tipo_contrato, entidade_responsavel, periodicidade, observacoes, caminho_ficheiro)
                 VALUES (:equipamento_id, :data_inicio, :data_fim, :existe_contrato, :tipo_contrato, :entidade_responsavel, :periodicidade, :observacoes, :ficheiro)'
            );
            $stmt->execute([
                ':equipamento_id' => $equipamento_id,
                ':data_inicio' => $data_inicio !== '' ? $data_inicio : null,
                ':data_fim' => $data_fim !== '' ? $data_fim : null,
                ':existe_contrato' => $existe_contrato,
                ':tipo_contrato' => $tipo_contrato,
                ':entidade_responsavel' => $entidade_responsavel,
                ':periodicidade' => $periodicidade,
                ':observacoes' => $observacoes,
                ':ficheiro' => $ficheiro
            ]);

            registar_evento('Garantias', 'Criação', 'Equipamento', $equipamento_id, 'Garantia/contrato registado.');

            header('Location: detalhes.php?id=' . $equipamento_id . '#garantias');
            exit;
        } catch (PDOException $err) {
            $erros[] = 'Não foi possível guardar a garantia/contrato.';
        }
    }
}

$cancelar_url = !empty($equipamento_id) ? 'detalhes.php?id=' . $equipamento_id . '#garantias' : 'lista.php';

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>
<div class="container-fluid">
    <div class="row">
        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 form-page">
            <div class="mb-3">
                <h1 class="page-title"><i class="fa-solid fa-shield-halved me-2"></i>Adicionar garantia/contrato</h1>
                <p class="page-subtitle mb-0">Registo de garantia, contrato de assistência ou contrato de manutenção associado ao equipamento.</p>
            </div>

            <?php if (!empty($erros)) : ?>
                <div class="alert alert-danger">
                    <?php foreach ($erros as $erro) : ?>
                        <div><?= htmlspecialchars($erro) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="content-card">
                <form method="post" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label">Equipamento</label>
                            <select name="equipamento_id" class="form-select">
                                <option value="">Escolha um equipamento</option>
                                <?php foreach ($equipamentos as $equipamento) : ?>
                                    <option value="<?= $equipamento->id ?>" <?= $equipamento_id == $equipamento->id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($equipamento->codigo_inventario . ' - ' . $equipamento->designacao) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-lg-4 col-md-6">
                            <label class="form-label">Tipo</label>
                            <select name="tipo_contrato" class="form-select">
                                <?php foreach (['Garantia de fabricante', 'Contrato de assistência técnica', 'Contrato de manutenção preventiva', 'Extensão de garantia'] as $tipo) : ?>
                                    <option value="<?= $tipo ?>" <?= $tipo_contrato === $tipo ? 'selected' : '' ?>><?= $tipo ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-lg-4 col-md-6">
                            <label class="form-label">Entidade responsável</label>
                            <select name="entidade_responsavel" class="form-select">
                                <option value="">Escolha a entidade</option>
                                <?php foreach ($fornecedores as $fornecedor) : ?>
                                    <option value="<?= htmlspecialchars($fornecedor->nome_empresa) ?>" <?= $entidade_responsavel === $fornecedor->nome_empresa ? 'selected' : '' ?>><?= htmlspecialchars($fornecedor->nome_empresa) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">Data de início</label>
                            <input type="date" name="data_inicio" class="form-control" value="<?= htmlspecialchars($data_inicio) ?>">
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">Data de fim</label>
                            <input type="date" name="data_fim" class="form-control" value="<?= htmlspecialchars($data_fim) ?>">
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">Periodicidade</label>
                            <select name="periodicidade" class="form-select">
                                <?php foreach (['Mensal', 'Trimestral', 'Semestral', 'Anual', 'Conforme necessidade'] as $opcao) : ?>
                                    <option value="<?= $opcao ?>" <?= $periodicidade === $opcao ? 'selected' : '' ?>><?= $opcao ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">Ficheiro PDF</label>
                            <input type="file" name="ficheiro" class="form-control" accept="application/pdf,.pdf">
                        </div>

                        <div class="col-lg-9 col-md-8">
                            <label class="form-label">Observações</label>
                            <input type="text" name="observacoes" class="form-control" value="<?= htmlspecialchars($observacoes) ?>" placeholder="Opcional">
                        </div>

                        <div class="col-lg-3 col-md-4 d-flex align-items-end">
                            <label class="form-check mb-2">
                                <input type="checkbox" name="existe_contrato" class="form-check-input" <?= $existe_contrato ? 'checked' : '' ?>>
                                <span class="form-check-label fw-bold">Contrato associado</span>
                            </label>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <a href="<?= htmlspecialchars($cancelar_url) ?>" class="btn btn-soft"><i class="fa-solid fa-xmark me-1"></i>Cancelar</a>
                        <button type="submit" class="btn btn-main"><i class="fa-regular fa-floppy-disk me-1"></i>Guardar garantia/contrato</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>


