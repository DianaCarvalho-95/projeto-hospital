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
                "mysql:host=" . MYSQL_HOST . ";dbname=" . MYSQL_DATABASE . ";charset=utf8mb4",
                MYSQL_USERNAME,
                MYSQL_PASSWORD
            );
            $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $ligacao->prepare(
                'SELECT id FROM agents WHERE name = :utilizador AND passwrd = :password_atual LIMIT 1'
            );
            $stmt->execute([
                ':utilizador' => $_SESSION['utilizador'],
                ':password_atual' => $password_atual
            ]);
            $utilizador = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$utilizador) {
                $erro = 'A password atual não está correta.';
            } else {
                $stmt = $ligacao->prepare('UPDATE agents SET passwrd = :nova_password WHERE id = :id');
                $stmt->execute([
                    ':nova_password' => $nova_password,
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

<style>
    .password-page {
        background: #f5f7fa;
        min-height: 100vh;
        padding: 22px 24px;
    }

    .page-title {
        color: #1E3A5F;
        font-size: 1.65rem;
        font-weight: 850;
        margin: 0;
    }

    .page-subtitle {
        color: #526985;
        font-size: 0.9rem;
        margin: 3px 0 14px;
    }

    .password-card {
        background: #ffffff;
        border: 1px solid #dbe5ef;
        border-radius: 14px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
        max-width: 760px;
        padding: 22px;
    }

    .form-label {
        color: #1E3A5F;
        font-size: 0.78rem;
        font-weight: 850;
    }

    .form-control {
        border-radius: 9px;
        min-height: 38px;
    }

    .btn-main {
        background: #2F5D8A;
        border-color: #2F5D8A;
        border-radius: 9px;
        color: #ffffff;
        font-weight: 850;
        min-height: 38px;
    }

    .btn-main:hover {
        background: #1E3A5F;
        color: #ffffff;
    }

    .btn-light-back {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 9px;
        color: #1E3A5F;
        font-weight: 800;
        min-height: 38px;
    }
</style>

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
