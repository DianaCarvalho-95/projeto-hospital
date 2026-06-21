<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$erros = [];
$sucesso = '';
$nome = '';
$observacoes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
    $observacoes = isset($_POST['observacoes']) ? trim($_POST['observacoes']) : '';

    if ($nome === '') {
        $erros[] = 'O nome do edifício é obrigatório.';
    }

    if (empty($erros)) {
        try {
            $ligacao = new PDO(
                "mysql:host=" . MYSQL_HOST . ";dbname=" . MYSQL_DATABASE . ";charset=utf8mb4",
                MYSQL_USERNAME,
                MYSQL_PASSWORD
            );
            $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $ligacao->prepare(
                "INSERT INTO edificios (nome, observacoes)
                 VALUES (:nome, :observacoes)"
            );
            $stmt->execute([
                ':nome' => $nome,
                ':observacoes' => $observacoes
            ]);

            $sucesso = 'Edifício criado com sucesso. Já pode ser usado numa nova localização.';
            $nome = '';
            $observacoes = '';
        } catch (PDOException $err) {
            if ((int) $err->getCode() === 23000) {
                $erros[] = 'Já existe um edifício com esse nome.';
            } else {
                $erros[] = 'Não foi possível criar o edifício.';
            }
        }

        $ligacao = null;
    }
}

function h($valor)
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>
<div class="container-fluid">
    <div class="row">
        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 edificio-page">
            <div class="mb-3">
                <h2 class="page-title"><i class="fa-solid fa-building me-2"></i>Novo edifício</h2>
                <p class="page-subtitle">Registo de um novo edifício hospitalar para usar nas localizações.</p>
            </div>

            <?php if (!empty($erros)) : ?>
                <div class="mensagem-erro"><strong>Foram encontrados os seguintes erros:</strong><ul class="mb-0 mt-2"><?php foreach ($erros as $erro) : ?><li><?= h($erro) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>

            <?php if (!empty($sucesso)) : ?>
                <div class="mensagem-sucesso"><?= h($sucesso) ?></div>
            <?php endif; ?>

            <div class="content-card">
                <div class="info-box mb-3">
                    Use esta opção quando o hospital criar um novo edifício. Depois de guardado, o edifício passa a aparecer na criação e edição de localizações.
                </div>

                <form method="post" action="novo-edificio.php" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nome do edifício</label>
                            <input type="text" name="nome" class="form-control" value="<?= h($nome) ?>" placeholder="Ex.: Edifício D">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Observações</label>
                            <input type="text" name="observacoes" class="form-control" value="<?= h($observacoes) ?>" placeholder="Opcional">
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="lista.php" class="btn btn-cancelar-custom"><i class="fa-solid fa-xmark me-1"></i>Cancelar</a>
                        <button type="submit" class="btn btn-guardar-custom"><i class="fa-regular fa-floppy-disk me-1"></i>Guardar edifício</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

