<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password_atual = trim($_POST['password_atual'] ?? '');
    $nova_password = trim($_POST['nova_password'] ?? '');
    $confirmar_password = trim($_POST['confirmar_password'] ?? '');

    if ($password_atual === '' || $nova_password === '' || $confirmar_password === '') {
        $erro = 'Preencha todos os campos.';
    } elseif (strlen($nova_password) < 6 || strlen($nova_password) > 20) {
        $erro = 'A nova password deve ter entre 6 e 20 caracteres.';
    } elseif ($nova_password !== $confirmar_password) {
        $erro = 'A confirmação não corresponde à nova password.';
    } else {
        try {
            $ligacao = new PDO(
                "mysql:host=" . MYSQL_HOST . ";port=" . MYSQL_PORT . ";dbname=" . MYSQL_DATABASE . ";charset=utf8mb4",
                MYSQL_USERNAME,
                MYSQL_PASSWORD
            );
            $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $ligacao->prepare(
                'SELECT id, passwrd FROM agents WHERE name = :utilizador LIMIT 1'
            );
            $stmt->execute([
                ':utilizador' => $_SESSION['utilizador']
            ]);
            $utilizador = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$utilizador || !password_verify($password_atual, $utilizador->passwrd)) {
                $erro = 'A password atual não está correta.';
            } else {
                $hash_password = password_hash($nova_password, PASSWORD_DEFAULT);
                $stmt = $ligacao->prepare('UPDATE agents SET passwrd = :nova_password WHERE id = :id');
                $stmt->execute([
                    ':nova_password' => $hash_password,
                    ':id' => $utilizador->id
                ]);
                $sucesso = 'Password atualizada com sucesso.';
            }
        } catch (PDOException $err) {
            $erro = 'Não foi possível atualizar a password.';
        }
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

        <main class="col-md-9 col-lg-10 password-page">
            <h2 class="page-title">
                <i class="fa-solid fa-key me-2"></i>
                Alterar password
            </h2>
            <p class="page-subtitle">Atualização da password de acesso à área privada.</p>

            <div class="password-card">
                <?php if ($erro !== '') : ?>
                    <div class="alert alert-danger py-2"><?= h($erro) ?></div>
                <?php endif; ?>

                <?php if ($sucesso !== '') : ?>
                    <div class="alert alert-success py-2"><?= h($sucesso) ?></div>
                <?php endif; ?>

                <form method="post" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Password atual</label>
                        <input type="password" name="password_atual" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nova password</label>
                        <input type="password" name="nova_password" class="form-control" minlength="6" maxlength="20" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Confirmar password</label>
                        <input type="password" name="confirmar_password" class="form-control" minlength="6" maxlength="20" required>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <a href="<?php echo BASE_URL; ?>/private/views/dashboard/dashboard.php" class="btn btn-light-back">
                            <i class="fa-solid fa-xmark me-1"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-main">
                            <i class="fa-solid fa-floppy-disk me-1"></i>Guardar password
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>


