<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$erros = [];
$sucesso = '';

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
    $observacoes = isset($_POST['observacoes']) ? trim($_POST['observacoes']) : '';

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

        try {

            $ligacao = new PDO(
                "mysql:host=" . MYSQL_HOST .
                ";dbname=" . MYSQL_DATABASE .
                ";charset=utf8",
                MYSQL_USERNAME,
                MYSQL_PASSWORD
            );

            $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sql = "INSERT INTO equipamentos
                    (codigo_inventario, designacao, categoria, marca, modelo,
                     numero_serie, fabricante, data_aquisicao, ano_fabrico,
                     custo_aquisicao, tipo_entrada, estado, criticidade, observacoes)
                    VALUES
                    (:codigo, :designacao, :categoria, :marca, :modelo,
                     :numero_serie, :fabricante, :data_aquisicao, :ano_fabrico,
                     :custo_aquisicao, :tipo_entrada, :estado, :criticidade, :observacoes)";

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
                ':observacoes' => $observacoes
            ]);

            $sucesso = 'Equipamento inserido com sucesso.';

            $codigo = '';
            $designacao = '';
            $categoria = '';
            $marca = '';
            $modelo = '';
            $numero_serie = '';
            $fabricante = '';
            $data_aquisicao = '';
            $ano_fabrico = '';
            $custo_aquisicao = '';
            $tipo_entrada = '';
            $estado = '';
            $criticidade = '';
            $observacoes = '';

        } catch (PDOException $err) {

            $erros[] = 'Não foi possível inserir o equipamento.';
        }

        $ligacao = null;
    }
}
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 p-4">

            <h2>
                <i class="fa-solid fa-cogs me-2"></i>
                Inserir novo equipamento
            </h2>

            <hr>

            <?php if (!empty($erros)) : ?>
                <div class="alert alert-danger">
                    <?php foreach ($erros as $erro) : ?>
                        <div><?= htmlspecialchars($erro) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($sucesso)) : ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($sucesso) ?>
                </div>
            <?php endif; ?>

            <form action="novo.php" method="post">

                <div class="mb-3">
                    <label class="form-label">Código Interno de Inventário</label>
                    <input type="text" name="codigo" class="form-control" value="<?= htmlspecialchars($codigo ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Designação do Equipamento</label>
                    <input type="text" name="designacao" class="form-control" value="<?= htmlspecialchars($designacao ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Categoria</label>
                    <select name="categoria" class="form-control">
                        <option value="">Escolha uma opção</option>
                        <option value="Monitorização">Monitorização</option>
                        <option value="Suporte de vida">Suporte de vida</option>
                        <option value="Terapia">Terapia</option>
                        <option value="Diagnóstico">Diagnóstico</option>
                        <option value="Laboratório">Laboratório</option>
                        <option value="Esterilização">Esterilização</option>
                        <option value="Reabilitação">Reabilitação</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Marca</label>
                    <input type="text" name="marca" class="form-control" value="<?= htmlspecialchars($marca ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Modelo</label>
                    <input type="text" name="modelo" class="form-control" value="<?= htmlspecialchars($modelo ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Número de Série</label>
                    <input type="text" name="numero_serie" class="form-control" value="<?= htmlspecialchars($numero_serie ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Fabricante</label>
                    <input type="text" name="fabricante" class="form-control" value="<?= htmlspecialchars($fabricante ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Data de Aquisição</label>
                    <input type="date" name="data_aquisicao" class="form-control" value="<?= htmlspecialchars($data_aquisicao ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Ano de Fabrico</label>
                    <input type="number" name="ano_fabrico" class="form-control" value="<?= htmlspecialchars($ano_fabrico ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Custo de Aquisição</label>
                    <input type="text" name="custo_aquisicao" class="form-control" value="<?= htmlspecialchars($custo_aquisicao ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Tipo de Entrada</label>
                    <select name="tipo_entrada" class="form-control">
                        <option value="">Escolha uma opção</option>
                        <option value="Compra">Compra</option>
                        <option value="Doação">Doação</option>
                        <option value="Aluguer">Aluguer</option>
                        <option value="Empréstimo">Empréstimo</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Estado Atual</label>
                    <select name="estado" class="form-control">
                        <option value="">Escolha uma opção</option>
                        <option value="Ativo">Ativo</option>
                        <option value="Inativo">Inativo</option>
                        <option value="Em manutenção">Em manutenção</option>
                        <option value="Em calibração">Em calibração</option>
                        <option value="Abatido">Abatido</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Criticidade</label>
                    <select name="criticidade" class="form-control">
                        <option value="">Escolha uma opção</option>
                        <option value="Baixa">Baixa</option>
                        <option value="Média">Média</option>
                        <option value="Alta">Alta</option>
                        <option value="Suporte de vida">Suporte de vida</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Observações</label>
                    <textarea name="observacoes" rows="4" class="form-control"><?= htmlspecialchars($observacoes ?? '') ?></textarea>
                </div>

                <div class="mb-3">
                    <a href="lista.php" class="btn btn-secondary">
                        <i class="fa-solid fa-xmark me-1"></i>
                        Cancelar
                    </a>

                    <button type="submit" class="btn btn-success">
                        <i class="fa-regular fa-floppy-disk me-1"></i>
                        Guardar
                    </button>
                </div>

            </form>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>