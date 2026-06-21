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
                ";port=" . MYSQL_PORT . ";dbname=" . MYSQL_DATABASE .
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

            if (empty($erros)) {
                $imagem_upload = guardar_upload_imagem_equipamento('imagem_equipamento', $erros);
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
                            observacoes = :observacoes,
                            imagem_upload = COALESCE(:imagem_upload, imagem_upload)
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
                    ':imagem_upload' => $imagem_upload,
                    ':id' => $id
                ]);

                registar_evento('Equipamentos', 'Edição', 'Equipamento', $id, $codigo . ' - ' . $designacao);

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

                    <form action="editar.php?id=<?= $equipamento->id ?>" method="post" enctype="multipart/form-data">

                        <div class="row g-3">

                            <!-- Código Interno de Inventário -->
                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">Código Interno de Inventário</label>
                                <input type="text"
                                    name="codigo"
                                    class="form-control"
                                    value="<?= htmlspecialchars($equipamento->codigo_inventario) ?>">
                            </div>

                            <!-- Designação do Equipamento -->
                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">Designação do Equipamento</label>
                                <input type="text"
                                    name="designacao"
                                    class="form-control"
                                    value="<?= htmlspecialchars($equipamento->designacao) ?>">
                            </div>

                            <!-- Categoria -->
                            <div class="col-lg-4 col-md-6">
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
                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">Marca</label>
                                <input type="text"
                                    name="marca"
                                    class="form-control"
                                    value="<?= htmlspecialchars($equipamento->marca) ?>">
                            </div>

                            <!-- Modelo -->
                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">Modelo</label>
                                <input type="text"
                                    name="modelo"
                                    class="form-control"
                                    value="<?= htmlspecialchars($equipamento->modelo) ?>">
                            </div>

                            <!-- Número de Série -->
                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">Número de Série</label>
                                <input type="text"
                                    name="numero_serie"
                                    class="form-control"
                                    value="<?= htmlspecialchars($equipamento->numero_serie) ?>">
                            </div>

                            <!-- Fabricante -->
                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">Fabricante</label>
                                <input type="text"
                                    name="fabricante"
                                    class="form-control"
                                    value="<?= htmlspecialchars($equipamento->fabricante) ?>">
                            </div>

                            <!-- Data de Aquisição -->
                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">Data de Aquisição</label>
                                <input type="date"
                                    name="data_aquisicao"
                                    class="form-control"
                                    value="<?= htmlspecialchars($equipamento->data_aquisicao) ?>">
                            </div>

                            <!-- Ano de Fabrico -->
                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">Ano de Fabrico</label>
                                <input type="number"
                                    name="ano_fabrico"
                                    class="form-control"
                                    value="<?= htmlspecialchars($equipamento->ano_fabrico) ?>">
                            </div>

                            <!-- Custo de Aquisição -->
                            <div class="col-lg-4 col-md-6">
                                <label class="form-label">Custo de Aquisição</label>
                                <input type="text"
                                    name="custo_aquisicao"
                                    class="form-control"
                                    value="<?= htmlspecialchars($equipamento->custo_aquisicao) ?>">
                            </div>

                            <!-- Tipo de Entrada -->
                            <div class="col-lg-4 col-md-6">
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
                            <div class="col-lg-4 col-md-6">
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
                            <div class="col-lg-4 col-md-6">
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
                            <div class="col-lg-4 col-md-6">
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
                            <div class="col-lg-4 col-md-6">

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
                                                        <div class="col-lg-4 col-md-6">
                                <label class="form-label">Imagem do equipamento</label>
                                <input type="file"
                                    name="imagem_equipamento"
                                    class="form-control"
                                    accept="image/jpeg,image/png,image/webp,image/gif">
                                <small class="form-text text-muted">Opcional. Ao escolher uma nova imagem, substitui a atual.</small>
                            </div>
<div class="col-lg-4 col-md-6">

                                <label class="form-label">Observações</label>

                                <textarea
                                    name="observacoes"
                                    rows="1"
                                    class="form-control"><?= htmlspecialchars($equipamento->observacoes) ?></textarea>

                            </div>

                        </div>

                        <!-- Botões do formulário -->
                        <div class="d-flex gap-2 mt-3">

                            <a href="detalhes.php?id=<?= $equipamento->id ?>" class="btn btn-cancelar-custom">
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






