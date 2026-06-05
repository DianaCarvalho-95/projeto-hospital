<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$erros = [];
$sucesso = '';
$equipamento = null;
$localizacoes = [];
$fornecedores = [];

if ($id <= 0) {
    $erros[] = 'Equipamento inválido.';
} else {

    try {

        /*
            Ligação à base de dados.
        */
        $ligacao = new PDO(
            "mysql:host=" . MYSQL_HOST .
                ";dbname=" . MYSQL_DATABASE .
                ";charset=utf8",
            MYSQL_USERNAME,
            MYSQL_PASSWORD
        );

        $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        /*
            Carrega as localizações disponíveis para a lista de seleção.
        */
        $stmt_localizacoes = $ligacao->query(
            "SELECT * FROM localizacoes
             ORDER BY edificio, piso, servico, sala"
        );

        $localizacoes = $stmt_localizacoes->fetchAll(PDO::FETCH_OBJ);

        /*
            Carrega os fornecedores disponíveis para a lista de seleção.
        */
        $stmt_fornecedores = $ligacao->query(
            "SELECT * FROM fornecedores
             ORDER BY nome_empresa"
        );

        $fornecedores = $stmt_fornecedores->fetchAll(PDO::FETCH_OBJ);

        /*
            Se o formulário foi submetido, recolhe e valida os dados.
        */
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $codigo = isset($_POST['codigo']) ? trim($_POST['codigo']) : '';
            $designacao = isset($_POST['designacao']) ? trim($_POST['designacao']) : '';
            $categoria = isset($_POST['categoria']) ? trim($_POST['categoria']) : '';
            $marca = isset($_POST['marca']) ? trim($_POST['marca']) : '';
            $modelo = isset($_POST['modelo']) ? trim($_POST['modelo']) : '';
            $numero_serie = isset($_POST['numero_serie']) ? trim($_POST['numero_serie']) : '';
            $fabricante = isset($_POST['fabricante']) ? trim($_POST['fabricante']) : '';
            $data_aquisicao = isset($_POST['data_aquisicao']) ? trim($_POST['data_aquisicao']) : '';
            $ano_fabrico = isset($_POST['ano_fabrico']) ? trim($_POST['ano_fabrico']) : '';
            $custo_aquisicao = isset($_POST['custo_aquisicao']) ? trim($_POST['custo_aquisicao']) : '';
            $tipo_entrada = isset($_POST['tipo_entrada']) ? trim($_POST['tipo_entrada']) : '';
            $estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';
            $criticidade = isset($_POST['criticidade']) ? trim($_POST['criticidade']) : '';
            $localizacao_id = isset($_POST['localizacao_id']) ? intval($_POST['localizacao_id']) : null;
            $fornecedor_id = isset($_POST['fornecedor_id']) ? intval($_POST['fornecedor_id']) : null;
            $observacoes = isset($_POST['observacoes']) ? trim($_POST['observacoes']) : '';

            /*
                Validações obrigatórias.
            */
            if (empty($codigo)) {
                $erros[] = 'O código interno é obrigatório.';
            }

            if (empty($designacao)) {
                $erros[] = 'A designação é obrigatória.';
            }

            if (empty($categoria)) {
                $erros[] = 'A categoria é obrigatória.';
            }

            if (empty($estado)) {
                $erros[] = 'O estado é obrigatório.';
            }

            if (empty($criticidade)) {
                $erros[] = 'A criticidade é obrigatória.';
            }

            /*
                Atualiza o equipamento se não existirem erros.
            */
            if (empty($erros)) {

                $sql = "UPDATE equipamentos SET
                            codigo_inventario = :codigo,
                            designacao = :designacao,
                            categoria = :categoria,
                            marca = :marca,
                            modelo = :modelo,
                            numero_serie = :numero_serie,
                            fabricante = :fabricante,
                            data_aquisicao = :data_aquisicao,
                            ano_fabrico = :ano_fabrico,
                            custo_aquisicao = :custo_aquisicao,
                            tipo_entrada = :tipo_entrada,
                            estado = :estado,
                            criticidade = :criticidade,
                            localizacao_id = :localizacao_id,
                            fornecedor_id = :fornecedor_id,
                            observacoes = :observacoes
                        WHERE id = :id";

                $stmt = $ligacao->prepare($sql);

                $stmt->execute([
                    ':codigo' => $codigo,
                    ':designacao' => $designacao,
                    ':categoria' => $categoria,
                    ':marca' => $marca,
                    ':modelo' => $modelo,
                    ':numero_serie' => $numero_serie,
                    ':fabricante' => $fabricante,
                    ':data_aquisicao' => !empty($data_aquisicao) ? $data_aquisicao : null,
                    ':ano_fabrico' => !empty($ano_fabrico) ? $ano_fabrico : null,
                    ':custo_aquisicao' => !empty($custo_aquisicao) ? $custo_aquisicao : null,
                    ':tipo_entrada' => $tipo_entrada,
                    ':estado' => $estado,
                    ':criticidade' => $criticidade,
                    ':localizacao_id' => !empty($localizacao_id) ? $localizacao_id : null,
                    ':fornecedor_id' => !empty($fornecedor_id) ? $fornecedor_id : null,
                    ':observacoes' => $observacoes,
                    ':id' => $id
                ]);

                $sucesso = 'Equipamento atualizado com sucesso.';
            }
        }

        /*
            Carrega os dados atuais do equipamento.
        */
        $stmt = $ligacao->prepare("SELECT * FROM equipamentos WHERE id = :id");

        $stmt->execute([
            ':id' => $id
        ]);

        $equipamento = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$equipamento) {
            $erros[] = 'Equipamento não encontrado.';
        }
    } catch (PDOException $err) {

        $erros[] = 'Não foi possível atualizar o equipamento.';
    }

    $ligacao = null;
}

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<style>
    /*
        Fundo da página.
        Mantém o mesmo estilo da Dashboard e da listagem.
    */
    .editar-page {
        background: #f5f7fa;
        min-height: 100vh;
        padding: 24px;
    }

    /*
        Título principal da página.
    */
    .page-title {
        font-weight: 600;
        color: #1E3A5F;
        font-size: 1.8rem;
        margin-bottom: 0;
    }

    /*
        Texto auxiliar abaixo do título.
    */
    .page-subtitle {
        color: #64748b;
        font-size: 0.95rem;
        margin-bottom: 0;
    }

    /*
        Cartão branco que contém o formulário.
    */
    .content-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        border: 1px solid #e5e7eb;
    }

    /*
        Labels dos campos.
    */
    .form-label {
        font-weight: 600;
        color: #334155;
        font-size: 0.88rem;
    }

    /*
        Campos do formulário.
    */
    .form-control {
        border-radius: 10px;
        border: 1px solid #dbe3ec;
        font-size: 0.9rem;
    }

    .form-control:focus {
        border-color: #2F5D8A;
        box-shadow: 0 0 0 0.15rem rgba(47, 93, 138, 0.18);
    }

    /*
        Botão Cancelar.
    */
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

    /*
        Botão Guardar.
        Usa o azul institucional do site.
    */
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

    /*
        Mensagens de erro e sucesso com aspeto mais moderno.
    */
    .mensagem-erro {
        background: #fdeaea;
        color: #bb2d3b;
        border: 1px solid #f8d3d3;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 16px;
    }

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
                    Editar Equipamento
                </h2>

                <!-- Subtítulo explicativo -->
                <p class="page-subtitle">
                    Atualização dos dados técnicos, administrativos e operacionais do equipamento.
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

            <?php if ($equipamento) : ?>

                <div class="content-card">

                    <form action="editar.php?id=<?= $equipamento->id ?>" method="post">

                        <div class="row">

                            <!-- Código Interno de Inventário -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Código Interno de Inventário</label>
                                <input type="text"
                                    name="codigo"
                                    class="form-control"
                                    value="<?= htmlspecialchars($equipamento->codigo_inventario) ?>">
                            </div>

                            <!-- Designação do Equipamento -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Designação do Equipamento</label>
                                <input type="text"
                                    name="designacao"
                                    class="form-control"
                                    value="<?= htmlspecialchars($equipamento->designacao) ?>">
                            </div>

                            <!-- Categoria -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Categoria</label>
                                <select name="categoria" class="form-control">
                                    <option value="">Escolha uma opção</option>
                                    <option value="Monitorização" <?= $equipamento->categoria == 'Monitorização' ? 'selected' : '' ?>>Monitorização</option>
                                    <option value="Suporte de vida" <?= $equipamento->categoria == 'Suporte de vida' ? 'selected' : '' ?>>Suporte de vida</option>
                                    <option value="Terapia" <?= $equipamento->categoria == 'Terapia' ? 'selected' : '' ?>>Terapia</option>
                                    <option value="Diagnóstico" <?= $equipamento->categoria == 'Diagnóstico' ? 'selected' : '' ?>>Diagnóstico</option>
                                    <option value="Laboratório" <?= $equipamento->categoria == 'Laboratório' ? 'selected' : '' ?>>Laboratório</option>
                                    <option value="Esterilização" <?= $equipamento->categoria == 'Esterilização' ? 'selected' : '' ?>>Esterilização</option>
                                    <option value="Reabilitação" <?= $equipamento->categoria == 'Reabilitação' ? 'selected' : '' ?>>Reabilitação</option>
                                </select>
                            </div>

                            <!-- Marca -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Marca</label>
                                <input type="text"
                                    name="marca"
                                    class="form-control"
                                    value="<?= htmlspecialchars($equipamento->marca) ?>">
                            </div>

                            <!-- Modelo -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Modelo</label>
                                <input type="text"
                                    name="modelo"
                                    class="form-control"
                                    value="<?= htmlspecialchars($equipamento->modelo) ?>">
                            </div>

                            <!-- Número de Série -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Número de Série</label>
                                <input type="text"
                                    name="numero_serie"
                                    class="form-control"
                                    value="<?= htmlspecialchars($equipamento->numero_serie) ?>">
                            </div>

                            <!-- Fabricante -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fabricante</label>
                                <input type="text"
                                    name="fabricante"
                                    class="form-control"
                                    value="<?= htmlspecialchars($equipamento->fabricante) ?>">
                            </div>

                            <!-- Data de Aquisição -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Data de Aquisição</label>
                                <input type="date"
                                    name="data_aquisicao"
                                    class="form-control"
                                    value="<?= htmlspecialchars($equipamento->data_aquisicao) ?>">
                            </div>

                            <!-- Ano de Fabrico -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ano de Fabrico</label>
                                <input type="number"
                                    name="ano_fabrico"
                                    class="form-control"
                                    value="<?= htmlspecialchars($equipamento->ano_fabrico) ?>">
                            </div>

                            <!-- Custo de Aquisição -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Custo de Aquisição</label>
                                <input type="text"
                                    name="custo_aquisicao"
                                    class="form-control"
                                    value="<?= htmlspecialchars($equipamento->custo_aquisicao) ?>">
                            </div>

                            <!-- Tipo de Entrada -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo de Entrada</label>
                                <select name="tipo_entrada" class="form-control">
                                    <option value="">Escolha uma opção</option>
                                    <option value="Compra" <?= $equipamento->tipo_entrada == 'Compra' ? 'selected' : '' ?>>Compra</option>
                                    <option value="Doação" <?= $equipamento->tipo_entrada == 'Doação' ? 'selected' : '' ?>>Doação</option>
                                    <option value="Aluguer" <?= $equipamento->tipo_entrada == 'Aluguer' ? 'selected' : '' ?>>Aluguer</option>
                                    <option value="Empréstimo" <?= $equipamento->tipo_entrada == 'Empréstimo' ? 'selected' : '' ?>>Empréstimo</option>
                                </select>
                            </div>

                            <!-- Estado Atual -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Estado Atual</label>
                                <select name="estado" class="form-control">
                                    <option value="">Escolha uma opção</option>
                                    <option value="Ativo" <?= $equipamento->estado == 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                                    <option value="Inativo" <?= $equipamento->estado == 'Inativo' ? 'selected' : '' ?>>Inativo</option>
                                    <option value="Em manutenção" <?= $equipamento->estado == 'Em manutenção' ? 'selected' : '' ?>>Em manutenção</option>
                                    <option value="Em calibração" <?= $equipamento->estado == 'Em calibração' ? 'selected' : '' ?>>Em calibração</option>
                                    <option value="Abatido" <?= $equipamento->estado == 'Abatido' ? 'selected' : '' ?>>Abatido</option>
                                </select>
                            </div>

                            <!-- Criticidade -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Criticidade</label>
                                <select name="criticidade" class="form-control">
                                    <option value="">Escolha uma opção</option>
                                    <option value="Baixa" <?= $equipamento->criticidade == 'Baixa' ? 'selected' : '' ?>>Baixa</option>
                                    <option value="Média" <?= $equipamento->criticidade == 'Média' ? 'selected' : '' ?>>Média</option>
                                    <option value="Alta" <?= $equipamento->criticidade == 'Alta' ? 'selected' : '' ?>>Alta</option>
                                    <option value="Suporte de vida" <?= $equipamento->criticidade == 'Suporte de vida' ? 'selected' : '' ?>>Suporte de vida</option>
                                </select>
                            </div>

                            <!-- Localização -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Localização</label>
                                <select name="localizacao_id" class="form-control">
                                    <option value="">Escolha uma localização</option>

                                    <?php foreach ($localizacoes as $localizacao) : ?>
                                        <option value="<?= $localizacao->id ?>" <?= $equipamento->localizacao_id == $localizacao->id ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(
                                                $localizacao->edificio .
                                                    ' - ' .
                                                    $localizacao->piso .
                                                    ' - ' .
                                                    $localizacao->servico .
                                                    ' - ' .
                                                    $localizacao->sala
                                            ) ?>
                                        </option>
                                    <?php endforeach; ?>

                                </select>
                            </div>

                            <!-- Fornecedor -->
                            <div class="col-md-6 mb-3">

                                <label class="form-label">Fornecedor</label>

                                <select name="fornecedor_id" class="form-control">

                                    <option value="">Escolha um fornecedor</option>

                                    <?php foreach ($fornecedores as $fornecedor) : ?>

                                        <option value="<?= $fornecedor->id ?>"
                                            <?= $equipamento->fornecedor_id == $fornecedor->id ? 'selected' : '' ?>>

                                            <?= htmlspecialchars($fornecedor->nome_empresa) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                            <!-- Observações -->
                            <div class="col-md-6 mb-3">

                                <label class="form-label">Observações</label>

                                <textarea
                                    name="observacoes"
                                    rows="1"
                                    class="form-control"><?= htmlspecialchars($equipamento->observacoes) ?></textarea>

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