<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

/* Variáveis para mensagens */
$erros = [];

/* Lista de equipamentos carregada da base de dados */
$equipamentos = [];

/* Lista fixa de serviços disponíveis */
$servicos = [
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

/* Variáveis dos campos do formulário */
$equipamento_id = '';
$servico_origem = '';
$servico_destino = '';
$data_emprestimo = date('Y-m-d');
$data_prevista_devolucao = '';
$responsavel = '';
$observacoes = '';

try {

    /* Ligação à base de dados para carregar os equipamentos */
    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST .
        ";dbname=" . MYSQL_DATABASE .
        ";charset=utf8",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );

    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    /* Carrega os equipamentos disponíveis para seleção */
    $stmt = $ligacao->query(
        "SELECT id, codigo_inventario, designacao
         FROM equipamentos
         ORDER BY codigo_inventario"
    );

    $equipamentos = $stmt->fetchAll(PDO::FETCH_OBJ);

} catch (PDOException $err) {

    $erros[] = 'Não foi possível carregar os equipamentos.';
}

$ligacao = null;

/* Tratamento do formulário após submissão */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    /* Recolha dos dados enviados pelo formulário */
    $equipamento_id = isset($_POST['equipamento_id']) ? intval($_POST['equipamento_id']) : '';
    $servico_origem = isset($_POST['servico_origem']) ? trim($_POST['servico_origem']) : '';
    $servico_destino = isset($_POST['servico_destino']) ? trim($_POST['servico_destino']) : '';
    $data_emprestimo = isset($_POST['data_emprestimo']) ? trim($_POST['data_emprestimo']) : '';
    $data_prevista_devolucao = isset($_POST['data_prevista_devolucao']) ? trim($_POST['data_prevista_devolucao']) : '';
    $responsavel = isset($_POST['responsavel']) ? trim($_POST['responsavel']) : '';
    $observacoes = isset($_POST['observacoes']) ? trim($_POST['observacoes']) : '';

    /* Validações obrigatórias */
    if (empty($equipamento_id)) {
        $erros[] = 'O equipamento é obrigatório.';
    }

    if (empty($servico_origem)) {
        $erros[] = 'O serviço de origem é obrigatório.';
    }

    if (empty($servico_destino)) {
        $erros[] = 'O serviço de destino é obrigatório.';
    }

    if (empty($data_emprestimo)) {
        $erros[] = 'A data do empréstimo é obrigatória.';
    }

    if (empty($data_prevista_devolucao)) {
        $erros[] = 'A data prevista de devolução é obrigatória.';
    }

    if (empty($responsavel)) {
        $erros[] = 'O responsável é obrigatório.';
    }

    /* Impede que o serviço de origem e destino sejam iguais */
    if (
        !empty($servico_origem) &&
        !empty($servico_destino) &&
        $servico_origem == $servico_destino
    ) {
        $erros[] = 'O serviço de origem e o serviço de destino não podem ser iguais.';
    }

    /* Validação da data prevista de devolução */
    if (
        !empty($data_emprestimo) &&
        !empty($data_prevista_devolucao) &&
        $data_prevista_devolucao < $data_emprestimo
    ) {
        $erros[] = 'A data prevista de devolução não pode ser anterior à data do empréstimo.';
    }

    /* Se não existirem erros, regista o empréstimo */
    if (empty($erros)) {

        try {

            /* Nova ligação à base de dados para inserir o empréstimo */
            $ligacao = new PDO(
                "mysql:host=" . MYSQL_HOST .
                ";dbname=" . MYSQL_DATABASE .
                ";charset=utf8",
                MYSQL_USERNAME,
                MYSQL_PASSWORD
            );

            $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            /* Insere o empréstimo */
            $sql = "INSERT INTO emprestimos
                    (equipamento_id, servico_origem, servico_destino,
                     data_emprestimo, data_prevista_devolucao,
                     data_devolucao, responsavel, estado, observacoes)
                    VALUES
                    (:equipamento_id, :servico_origem, :servico_destino,
                     :data_emprestimo, :data_prevista_devolucao,
                     NULL, :responsavel, 'Ativo', :observacoes)";

            $stmt = $ligacao->prepare($sql);

            $stmt->execute([
                ':equipamento_id' => $equipamento_id,
                ':servico_origem' => $servico_origem,
                ':servico_destino' => $servico_destino,
                ':data_emprestimo' => $data_emprestimo,
                ':data_prevista_devolucao' => $data_prevista_devolucao,
                ':responsavel' => $responsavel,
                ':observacoes' => $observacoes
            ]);

            /* Após guardar, volta à listagem dos empréstimos */
            header('Location: emprestimos.php');
            exit;

        } catch (PDOException $err) {

            $erros[] = 'Não foi possível registar o empréstimo.';
        }

        $ligacao = null;
    }
}

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<style>
    /* Fundo da página */
    .emprestimo-page {
        background: #f5f7fa;
        min-height: 100vh;
        padding: 24px;
    }

    /* Título principal */
    .page-title {
        color: #1E3A5F;
        font-size: 1.8rem;
        font-weight: 600;
        margin-bottom: 0;
    }

    /* Subtítulo */
    .page-subtitle {
        color: #64748b;
        font-size: 0.95rem;
    }

    /* Cartão principal do formulário */
    .content-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        border: 1px solid #e5e7eb;
    }

    /* Labels dos campos */
    .form-label {
        font-weight: 600;
        color: #334155;
        font-size: 0.88rem;
    }

    /* Campos do formulário */
    .form-control {
        border-radius: 10px;
        border: 1px solid #dbe3ec;
        font-size: 0.9rem;
    }

    .form-control:focus {
        border-color: #2F5D8A;
        box-shadow: 0 0 0 0.15rem rgba(47, 93, 138, 0.18);
    }

    /* Botão cancelar */
    .btn-cancelar-custom {
        background: #eef2f7;
        border: 1px solid #dbe3ec;
        color: #475569;
        border-radius: 8px;
        font-weight: 600;
        padding: 8px 16px;
        text-decoration: none;
    }

    .btn-cancelar-custom:hover {
        background: #e2e8f0;
        color: #334155;
    }

    /* Botão guardar */
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

    /* Mensagem de erro */
    .mensagem-erro {
        background: #fdeaea;
        color: #bb2d3b;
        border: 1px solid #f8d3d3;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 16px;
    }
</style>

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 emprestimo-page">

            <div class="mb-3">

                <h2 class="page-title mb-1">
                    <i class="fa-solid fa-handshake me-2"></i>
                    Novo Empréstimo
                </h2>

                <p class="page-subtitle">
                    Registo de empréstimos temporários entre serviços hospitalares.
                </p>

            </div>

            <?php if (!empty($erros)) : ?>

                <div class="mensagem-erro">
                    <strong>Foram encontrados erros:</strong>

                    <ul class="mb-0 mt-2">
                        <?php foreach ($erros as $erro) : ?>
                            <li><?= htmlspecialchars($erro) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

            <?php endif; ?>

            <div class="content-card">

                <form action="novo-emprestimo.php" method="post" novalidate>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Equipamento</label>

                            <select name="equipamento_id" class="form-control">
                                <option value="">Escolha um equipamento</option>

                                <?php foreach ($equipamentos as $equipamento) : ?>

                                    <option value="<?= $equipamento->id ?>"
                                        <?= $equipamento_id == $equipamento->id ? 'selected' : '' ?>>

                                        <?= htmlspecialchars(
                                            $equipamento->codigo_inventario .
                                            ' - ' .
                                            $equipamento->designacao
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>
                        </div>

                         <!-- Responsável -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Responsável</label>

                            <input type="text"
                                   name="responsavel"
                                   class="form-control"
                                   value="<?= htmlspecialchars($responsavel) ?>">
                        </div>

                        <!-- Serviço de Origem -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Serviço de Origem</label>

                            <select name="servico_origem" class="form-control">
                                <option value="">Escolha um serviço</option>

                                <?php foreach ($servicos as $servico) : ?>

                                    <option value="<?= htmlspecialchars($servico) ?>"
                                        <?= $servico_origem == $servico ? 'selected' : '' ?>>

                                        <?= htmlspecialchars($servico) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>
                        </div>

                        <!-- Serviço de Destino -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Serviço de Destino</label>

                            <select name="servico_destino" class="form-control">
                                <option value="">Escolha um serviço</option>

                                <?php foreach ($servicos as $servico) : ?>

                                    <option value="<?= htmlspecialchars($servico) ?>"
                                        <?= $servico_destino == $servico ? 'selected' : '' ?>>

                                        <?= htmlspecialchars($servico) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>
                        </div>

                        <!-- Data do Empréstimo -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Data do Empréstimo</label>

                            <input type="date"
                                   name="data_emprestimo"
                                   class="form-control"
                                   value="<?= htmlspecialchars($data_emprestimo) ?>">
                        </div>

                        <!-- Data Prevista de Devolução -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Data Prevista de Devolução</label>

                            <input type="date"
                                   name="data_prevista_devolucao"
                                   class="form-control"
                                   value="<?= htmlspecialchars($data_prevista_devolucao) ?>">
                        </div>

                        <!-- Observações -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Observações</label>

                            <textarea name="observacoes"
                                      rows="2"
                                      class="form-control"><?= htmlspecialchars($observacoes) ?></textarea>
                        </div>

                    </div>

                    <div class="d-flex gap-2 mt-2">

                        <a href="emprestimos.php" class="btn-cancelar-custom">
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