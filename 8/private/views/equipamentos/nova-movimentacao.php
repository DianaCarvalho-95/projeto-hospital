<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$erros = [];
$equipamentos = [];
$localizacoes = [];

$equipamento_id = isset($_GET['equipamento_id']) ? intval($_GET['equipamento_id']) : '';
$local_origem = '';
$local_destino = '';
$data_movimentacao = date('Y-m-d');
$responsavel = '';
$motivo = '';
$observacoes = '';


function texto_localizacao($localizacao)
{
    return trim(
        ($localizacao->edificio ?? '') . ' - ' .
        ($localizacao->piso ?? '') . ' - ' .
        ($localizacao->servico ?? '') . ' - ' .
        ($localizacao->sala ?? '')
    );
}

try {
    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST . ";dbname=" . MYSQL_DATABASE . ";charset=utf8",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );
    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $equipamentos = $ligacao->query(
        "SELECT e.id, e.codigo_inventario, e.designacao, l.edificio, l.piso, l.servico, l.sala
         FROM equipamentos e
         LEFT JOIN localizacoes l ON e.localizacao_id = l.id
         ORDER BY e.codigo_inventario"
    )->fetchAll(PDO::FETCH_OBJ);

    $localizacoes = $ligacao->query(
        "SELECT id, edificio, piso, servico, sala
         FROM localizacoes
         ORDER BY edificio, piso, servico, sala"
    )->fetchAll(PDO::FETCH_OBJ);

} catch (PDOException $err) {
    $erros[] = 'Não foi possível carregar os dados do formulário.';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $equipamento_id = isset($_POST['equipamento_id']) ? intval($_POST['equipamento_id']) : '';
    $local_origem = isset($_POST['local_origem']) ? trim($_POST['local_origem']) : '';
    $local_destino = isset($_POST['local_destino']) ? trim($_POST['local_destino']) : '';
    $data_movimentacao = isset($_POST['data_movimentacao']) ? trim($_POST['data_movimentacao']) : '';
    $responsavel = isset($_POST['responsavel']) ? trim($_POST['responsavel']) : '';
    $motivo = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';
    $observacoes = isset($_POST['observacoes']) ? trim($_POST['observacoes']) : '';

    if (empty($equipamento_id)) {
        $erros[] = 'O equipamento é obrigatório.';
    }

    if (empty($local_origem)) {
        $erros[] = 'A localização de origem é obrigatória.';
    }

    if (empty($local_destino)) {
        $erros[] = 'A localização de destino é obrigatória.';
    }

    if (empty($data_movimentacao)) {
        $erros[] = 'A data da movimentação é obrigatória.';
    }

    if (empty($responsavel)) {
        $erros[] = 'O responsável é obrigatório.';
    }

    if (empty($erros)) {
        try {
            if (!isset($ligacao)) {
                $ligacao = new PDO(
                    "mysql:host=" . MYSQL_HOST . ";dbname=" . MYSQL_DATABASE . ";charset=utf8",
                    MYSQL_USERNAME,
                    MYSQL_PASSWORD
                );
                $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }

            $stmt = $ligacao->prepare(
                "INSERT INTO movimentacoes
                 (equipamento_id, local_origem, local_destino, data_movimentacao, responsavel, motivo, observacoes)
                 VALUES
                 (:equipamento_id, :local_origem, :local_destino, :data_movimentacao, :responsavel, :motivo, :observacoes)"
            );

            $stmt->execute([
                ':equipamento_id' => $equipamento_id,
                ':local_origem' => $local_origem,
                ':local_destino' => $local_destino,
                ':data_movimentacao' => $data_movimentacao,
                ':responsavel' => $responsavel,
                ':motivo' => $motivo,
                ':observacoes' => $observacoes
            ]);

            header('Location: detalhes.php?id=' . $equipamento_id . '#movimentacoes');
            exit;
        } catch (PDOException $err) {
            $erros[] = 'Não foi possível registar a movimentação.';
        }
    }
}

$cancelar_url = !empty($equipamento_id)
    ? 'detalhes.php?id=' . $equipamento_id . '#movimentacoes'
    : 'lista.php';

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<style>
    .form-page { background: #f5f7fa; min-height: 100vh; padding: 24px; }
    .page-title { color: #1E3A5F; font-size: 1.8rem; font-weight: 700; margin-bottom: 0; }
    .page-subtitle { color: #64748b; font-size: 0.95rem; }
    .content-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 20px; box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06); }
    .form-label { font-weight: 700; color: #334155; font-size: 0.88rem; }
    .form-control { border-radius: 10px; border: 1px solid #dbe3ec; font-size: 0.9rem; }
    .btn-main { background: #0d6efd; border: 1px solid #0d6efd; color: #fff; font-weight: 700; }
    .btn-main:hover { background: #0b5ed7; color: #fff; }
    .btn-soft { background: #eef2f7; border: 1px solid #dbe3ec; color: #334155; font-weight: 700; }
</style>

<div class="container-fluid">
    <div class="row">
        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 form-page">
            <div class="mb-3">
                <h1 class="page-title"><i class="fa-solid fa-right-left me-2"></i>Registar Movimentação</h1>
                <p class="page-subtitle mb-0">Registo de transferência ou alteração de localização do equipamento.</p>
            </div>

            <?php if (!empty($erros)) : ?>
                <div class="alert alert-danger">
                    <?php foreach ($erros as $erro) : ?>
                        <div><?= htmlspecialchars($erro) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="content-card">
                <form method="post">
                    <div class="row g-3">
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label">Equipamento</label>
                            <select name="equipamento_id" class="form-control">
                                <option value="">Escolha um equipamento</option>
                                <?php foreach ($equipamentos as $equipamento) : ?>
                                    <option value="<?= $equipamento->id ?>" <?= $equipamento_id == $equipamento->id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($equipamento->codigo_inventario . ' - ' . $equipamento->designacao) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-lg-4 col-md-6">
                            <label class="form-label">Origem</label>
                            <select name="local_origem" class="form-control">
                                <option value="">Escolha a origem</option>
                                <?php foreach ($localizacoes as $localizacao) : ?>
                                    <?php $texto_localizacao = texto_localizacao($localizacao); ?>
                                    <option value="<?= htmlspecialchars($texto_localizacao) ?>" <?= $local_origem == $texto_localizacao ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($texto_localizacao) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-lg-4 col-md-6">
                            <label class="form-label">Destino</label>
                            <select name="local_destino" class="form-control">
                                <option value="">Escolha o destino</option>
                                <?php foreach ($localizacoes as $localizacao) : ?>
                                    <?php $texto_localizacao = texto_localizacao($localizacao); ?>
                                    <option value="<?= htmlspecialchars($texto_localizacao) ?>" <?= $local_destino == $texto_localizacao ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($texto_localizacao) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-lg-4 col-md-6">
                            <label class="form-label">Data da Movimentação</label>
                            <input type="date" name="data_movimentacao" class="form-control" value="<?= htmlspecialchars($data_movimentacao) ?>">
                        </div>

                        <div class="col-lg-4 col-md-6">
                            <label class="form-label">Responsável</label>
                            <input type="text" name="responsavel" class="form-control" value="<?= htmlspecialchars($responsavel) ?>">
                        </div>

                        <div class="col-lg-4 col-md-6">
                            <label class="form-label">Motivo</label>
                            <input type="text" name="motivo" class="form-control" value="<?= htmlspecialchars($motivo) ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Observações</label>
                            <input type="text" name="observacoes" class="form-control" value="<?= htmlspecialchars($observacoes) ?>">
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <a href="<?= htmlspecialchars($cancelar_url) ?>" class="btn btn-soft"><i class="fa-solid fa-xmark me-1"></i>Cancelar</a>
                        <button type="submit" class="btn btn-main"><i class="fa-regular fa-floppy-disk me-1"></i>Guardar movimentação</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
