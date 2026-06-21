<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$erros = [];
$equipamentos = [];
$fornecedores = [];

$equipamento_id = isset($_GET['equipamento_id']) ? intval($_GET['equipamento_id']) : '';
$fornecedor_id = '';
$tipo_manutencao = '';
$data_manutencao = date('Y-m-d');
$proxima_manutencao = '';
$responsavel = '';
$descricao = '';
$custo = '';
$observacoes = '';

try {
    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST . ";port=" . MYSQL_PORT . ";dbname=" . MYSQL_DATABASE . ";charset=utf8",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );
    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $equipamentos = $ligacao->query(
        "SELECT id, codigo_inventario, designacao FROM equipamentos ORDER BY codigo_inventario"
    )->fetchAll(PDO::FETCH_OBJ);

    $fornecedores = $ligacao->query(
        "SELECT id, nome_empresa FROM fornecedores ORDER BY nome_empresa"
    )->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $err) {
    $erros[] = 'Não foi possível carregar os dados do formulário.';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $equipamento_id = isset($_POST['equipamento_id']) ? intval($_POST['equipamento_id']) : '';
    $fornecedor_id = isset($_POST['fornecedor_id']) ? intval($_POST['fornecedor_id']) : '';
    $tipo_manutencao = isset($_POST['tipo_manutencao']) ? trim($_POST['tipo_manutencao']) : '';
    $data_manutencao = isset($_POST['data_manutencao']) ? trim($_POST['data_manutencao']) : '';
    $proxima_manutencao = isset($_POST['proxima_manutencao']) ? trim($_POST['proxima_manutencao']) : '';
    $responsavel = isset($_POST['responsavel']) ? trim($_POST['responsavel']) : '';
    $descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';
    $custo = isset($_POST['custo']) ? trim($_POST['custo']) : '';
    $observacoes = isset($_POST['observacoes']) ? trim($_POST['observacoes']) : '';

    if (empty($equipamento_id)) {
        $erros[] = 'O equipamento é obrigatório.';
    }

    if (empty($tipo_manutencao)) {
        $erros[] = 'O tipo de manutenção é obrigatório.';
    }

    if (empty($data_manutencao)) {
        $erros[] = 'A data da manutenção é obrigatória.';
    }

    if (!empty($proxima_manutencao) && !empty($data_manutencao) && $proxima_manutencao < $data_manutencao) {
        $erros[] = 'A próxima manutenção não pode ser anterior à manutenção registada.';
    }

    if (!empty($custo)) {
        $custo = str_replace(',', '.', $custo);
        if (!is_numeric($custo)) {
            $erros[] = 'O custo deve ser um valor numérico.';
        }
    }

    if (empty($erros)) {
        try {
            if (!isset($ligacao)) {
                $ligacao = new PDO(
                    "mysql:host=" . MYSQL_HOST . ";port=" . MYSQL_PORT . ";dbname=" . MYSQL_DATABASE . ";charset=utf8",
                    MYSQL_USERNAME,
                    MYSQL_PASSWORD
                );
                $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }

            $stmt = $ligacao->prepare(
                "INSERT INTO manutencoes
                 (equipamento_id, fornecedor_id, tipo_manutencao, data_manutencao, proxima_manutencao, responsavel, descricao, custo, observacoes)
                 VALUES
                 (:equipamento_id, :fornecedor_id, :tipo_manutencao, :data_manutencao, :proxima_manutencao, :responsavel, :descricao, :custo, :observacoes)"
            );

            $stmt->execute([
                ':equipamento_id' => $equipamento_id,
                ':fornecedor_id' => !empty($fornecedor_id) ? $fornecedor_id : null,
                ':tipo_manutencao' => $tipo_manutencao,
                ':data_manutencao' => $data_manutencao,
                ':proxima_manutencao' => !empty($proxima_manutencao) ? $proxima_manutencao : null,
                ':responsavel' => $responsavel,
                ':descricao' => $descricao,
                ':custo' => $custo !== '' ? $custo : null,
                ':observacoes' => $observacoes
            ]);

            registar_evento('Manutenções', 'Criação', 'Equipamento', $equipamento_id, 'Manutenção registada: ' . $tipo_manutencao);

            header('Location: detalhes.php?id=' . $equipamento_id . '#manutencoes');
            exit;
        } catch (PDOException $err) {
            $erros[] = 'Não foi possível registar a manutenção.';
        }
    }
}

$cancelar_url = !empty($equipamento_id)
    ? 'detalhes.php?id=' . $equipamento_id . '#manutencoes'
    : 'lista.php';

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>
<div class="container-fluid">
    <div class="row">
        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 form-page">
            <div class="mb-3">
                <h1 class="page-title"><i class="fa-solid fa-wrench me-2"></i>Agendar Manutenção</h1>
                <p class="page-subtitle mb-0">Registo de intervenção e próxima manutenção do equipamento.</p>
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
                            <label class="form-label">Tipo de Manutenção</label>
                            <select name="tipo_manutencao" class="form-control">
                                <option value="">Escolha o tipo de manutenção</option>
                                <?php foreach (['Preventiva', 'Corretiva', 'Calibração', 'Inspeção'] as $tipo) : ?>
                                    <option value="<?= $tipo ?>" <?= $tipo_manutencao == $tipo ? 'selected' : '' ?>><?= $tipo ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-lg-4 col-md-6">
                            <label class="form-label">Fornecedor</label>
                            <select name="fornecedor_id" class="form-control">
                                <option value="">Escolha o fornecedor</option>
                                <?php foreach ($fornecedores as $fornecedor) : ?>
                                    <option value="<?= $fornecedor->id ?>" <?= $fornecedor_id == $fornecedor->id ? 'selected' : '' ?>><?= htmlspecialchars($fornecedor->nome_empresa) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-lg-4 col-md-6">
                            <label class="form-label">Data da Manutenção</label>
                            <input type="date" name="data_manutencao" class="form-control" value="<?= htmlspecialchars($data_manutencao) ?>">
                        </div>

                        <div class="col-lg-4 col-md-6">
                            <label class="form-label">Próxima Manutenção</label>
                            <input type="date" name="proxima_manutencao" class="form-control" value="<?= htmlspecialchars($proxima_manutencao) ?>">
                        </div>

                        <div class="col-lg-4 col-md-6">
                            <label class="form-label">Responsável</label>
                            <input type="text" name="responsavel" class="form-control" value="<?= htmlspecialchars($responsavel) ?>">
                        </div>

                        <div class="col-lg-4 col-md-6">
                            <label class="form-label">Custo</label>
                            <input type="text" name="custo" class="form-control" placeholder="Ex.: 150,00" value="<?= htmlspecialchars($custo) ?>">
                        </div>

                        <div class="col-lg-4 col-md-6">
                            <label class="form-label">Descrição</label>
                            <input type="text" name="descricao" class="form-control" value="<?= htmlspecialchars($descricao) ?>">
                        </div>

                        <div class="col-lg-4 col-md-6">
                            <label class="form-label">Observações</label>
                            <input type="text" name="observacoes" class="form-control" value="<?= htmlspecialchars($observacoes) ?>">
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <a href="<?= htmlspecialchars($cancelar_url) ?>" class="btn btn-soft"><i class="fa-solid fa-xmark me-1"></i>Cancelar</a>
                        <button type="submit" class="btn btn-main"><i class="fa-regular fa-floppy-disk me-1"></i>Guardar manutenção</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>


