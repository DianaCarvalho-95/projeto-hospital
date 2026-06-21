<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

function guardar_pdf_upload($campo, &$erros)
{
    if (!isset($_FILES[$campo]) || $_FILES[$campo]['error'] === UPLOAD_ERR_NO_FILE) {
        $erros[] = 'O ficheiro PDF é obrigatório.';
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
    $nome_final = 'doc_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '_' . $nome_base . '.pdf';
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
$tipo_registo = $_GET['tipo'] ?? 'ficha';
$tipo_registo = in_array($tipo_registo, ['ficha', 'manual'], true) ? $tipo_registo : 'ficha';

$tipo_documento = 'Ficha Técnica';
$nome_documento = '';
$data_documento = date('Y-m-d');
$data_validade = '';
$fornecedor_id = '';

$titulo = '';
$tipo_manual = 'Manual de Utilizador';
$idioma = 'Português';
$observacoes = '';

try {
    $ligacao = new PDO(
        'mysql:host=' . MYSQL_HOST . ';port=' . MYSQL_PORT . ';dbname=' . MYSQL_DATABASE . ';charset=utf8mb4',
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );
    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $equipamentos = $ligacao->query('SELECT id, codigo_inventario, designacao FROM equipamentos ORDER BY codigo_inventario')->fetchAll(PDO::FETCH_OBJ);
    $fornecedores = $ligacao->query('SELECT id, nome_empresa FROM fornecedores ORDER BY nome_empresa')->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $err) {
    $erros[] = 'Não foi possível carregar os dados do formulário.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipamento_id = isset($_POST['equipamento_id']) ? intval($_POST['equipamento_id']) : '';
    $tipo_registo = $_POST['tipo_registo'] ?? 'ficha';
    $tipo_registo = in_array($tipo_registo, ['ficha', 'manual'], true) ? $tipo_registo : 'ficha';

    $tipo_documento = trim($_POST['tipo_documento'] ?? 'Ficha Técnica');
    $nome_documento = trim($_POST['nome_documento'] ?? '');
    $data_documento = trim($_POST['data_documento'] ?? '');
    $data_validade = trim($_POST['data_validade'] ?? '');
    $fornecedor_id = isset($_POST['fornecedor_id']) ? intval($_POST['fornecedor_id']) : '';

    $titulo = trim($_POST['titulo'] ?? '');
    $tipo_manual = trim($_POST['tipo_manual'] ?? 'Manual de Utilizador');
    $idioma = trim($_POST['idioma'] ?? 'Português');
    $observacoes = trim($_POST['observacoes'] ?? '');

    if (empty($equipamento_id)) {
        $erros[] = 'O equipamento é obrigatório.';
    }

    if ($tipo_registo === 'ficha') {
        if ($nome_documento === '') {
            $erros[] = 'O nome da ficha técnica é obrigatório.';
        }
        if ($tipo_documento === '') {
            $erros[] = 'O tipo de documento é obrigatório.';
        }
    } else {
        if ($titulo === '') {
            $erros[] = 'O título do manual é obrigatório.';
        }
        if ($tipo_manual === '') {
            $erros[] = 'O tipo de manual é obrigatório.';
        }
    }

    $ficheiro = guardar_pdf_upload('ficheiro', $erros);

    if (empty($erros)) {
        try {
            if ($tipo_registo === 'ficha') {
                $stmt = $ligacao->prepare(
                    'INSERT INTO documentacao (tipo_documento, nome_documento, data_documento, data_validade, caminho_ficheiro, equipamento_id, fornecedor_id)
                     VALUES (:tipo_documento, :nome_documento, :data_documento, :data_validade, :ficheiro, :equipamento_id, :fornecedor_id)'
                );
                $stmt->execute([
                    ':tipo_documento' => $tipo_documento,
                    ':nome_documento' => $nome_documento,
                    ':data_documento' => $data_documento !== '' ? $data_documento : null,
                    ':data_validade' => $data_validade !== '' ? $data_validade : null,
                    ':ficheiro' => $ficheiro,
                    ':equipamento_id' => $equipamento_id,
                    ':fornecedor_id' => !empty($fornecedor_id) ? $fornecedor_id : null
                ]);
            } else {
                $stmt = $ligacao->prepare(
                    'INSERT INTO manuais_equipamentos (id_equipamento, titulo, tipo_manual, idioma, ficheiro, data_upload, observacoes)
                     VALUES (:equipamento_id, :titulo, :tipo_manual, :idioma, :ficheiro, NOW(), :observacoes)'
                );
                $stmt->execute([
                    ':equipamento_id' => $equipamento_id,
                    ':titulo' => $titulo,
                    ':tipo_manual' => $tipo_manual,
                    ':idioma' => $idioma !== '' ? $idioma : 'Português',
                    ':ficheiro' => $ficheiro,
                    ':observacoes' => $observacoes
                ]);
            }

            registar_evento('Documentação', 'Criação', 'Equipamento', $equipamento_id, 'Documento associado ao equipamento.');

            header('Location: detalhes.php?id=' . $equipamento_id . '#documentacao');
            exit;
        } catch (PDOException $err) {
            $erros[] = 'Não foi possível guardar o documento.';
        }
    }
}

$cancelar_url = !empty($equipamento_id) ? 'detalhes.php?id=' . $equipamento_id . '#documentacao' : 'lista.php';
$titulo_pagina = $tipo_registo === 'manual' ? 'Adicionar manual' : 'Adicionar ficha técnica';
$subtitulo_pagina = $tipo_registo === 'manual' ? 'Registo de um novo manual associado ao equipamento.' : 'Registo de uma nova ficha técnica associada ao equipamento.';

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>
<div class="container-fluid">
    <div class="row">
        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 form-page">
            <div class="mb-3">
                <h1 class="page-title"><i class="fa-regular fa-file-lines me-2"></i><?= htmlspecialchars($titulo_pagina) ?></h1>
                <p class="page-subtitle mb-0"><?= htmlspecialchars($subtitulo_pagina) ?></p>
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
                    <input type="hidden" name="tipo_registo" value="<?= htmlspecialchars($tipo_registo) ?>">

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

                        <?php if ($tipo_registo === 'ficha') : ?>
                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">Tipo de documento</label>
                                <select name="tipo_documento" class="form-select">
                                    <?php foreach (['Ficha Técnica', 'Certificado', 'Relatório técnico', 'Outro documento'] as $tipo) : ?>
                                        <option value="<?= $tipo ?>" <?= $tipo_documento === $tipo ? 'selected' : '' ?>><?= $tipo ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">Nome do documento</label>
                                <input type="text" name="nome_documento" class="form-control" value="<?= htmlspecialchars($nome_documento) ?>" placeholder="Ex.: Ficha Técnica - EQ001">
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">Data do documento</label>
                                <input type="date" name="data_documento" class="form-control" value="<?= htmlspecialchars($data_documento) ?>">
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">Validade</label>
                                <input type="date" name="data_validade" class="form-control" value="<?= htmlspecialchars($data_validade) ?>">
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">Fornecedor</label>
                                <select name="fornecedor_id" class="form-select">
                                    <option value="">Escolha o fornecedor</option>
                                    <?php foreach ($fornecedores as $fornecedor) : ?>
                                        <option value="<?= $fornecedor->id ?>" <?= $fornecedor_id == $fornecedor->id ? 'selected' : '' ?>><?= htmlspecialchars($fornecedor->nome_empresa) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php else : ?>
                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">Título do manual</label>
                                <input type="text" name="titulo" class="form-control" value="<?= htmlspecialchars($titulo) ?>" placeholder="Ex.: Manual de Utilizador - EQ001">
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">Tipo de manual</label>
                                <select name="tipo_manual" class="form-select">
                                    <?php foreach (['Manual de Utilizador', 'Manual Técnico', 'Manual de Serviço', 'Guia rápido'] as $tipo) : ?>
                                        <option value="<?= $tipo ?>" <?= $tipo_manual === $tipo ? 'selected' : '' ?>><?= $tipo ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">Idioma</label>
                                <select name="idioma" class="form-select">
                                    <?php foreach (['Português', 'Inglês', 'Espanhol'] as $opcao) : ?>
                                        <option value="<?= $opcao ?>" <?= $idioma === $opcao ? 'selected' : '' ?>><?= $opcao ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-lg-8 col-md-6">
                                <label class="form-label">Observações</label>
                                <input type="text" name="observacoes" class="form-control" value="<?= htmlspecialchars($observacoes) ?>" placeholder="Opcional">
                            </div>
                        <?php endif; ?>

                        <div class="col-lg-4 col-md-6">
                            <label class="form-label">Ficheiro PDF</label>
                            <input type="file" name="ficheiro" class="form-control" accept="application/pdf,.pdf">
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <a href="<?= htmlspecialchars($cancelar_url) ?>" class="btn btn-soft"><i class="fa-solid fa-xmark me-1"></i>Cancelar</a>
                        <button type="submit" class="btn btn-main"><i class="fa-regular fa-floppy-disk me-1"></i>Guardar documento</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>


