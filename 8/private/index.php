<?php

require_once __DIR__ . '/../config/config.php';

session_start();

// --------------------------------------------------------------------
// SEGURANÇA
// --------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

// --------------------------------------------------------------------
// RECOLHA DOS DADOS
// --------------------------------------------------------------------
$username = isset($_POST['text_username'])
    ? trim($_POST['text_username'])
    : '';

$password = isset($_POST['text_password'])
    ? trim($_POST['text_password'])
    : '';

// --------------------------------------------------------------------
// VALIDAÇÕES
// --------------------------------------------------------------------
$validation_errors = [];

if (empty($username)) {
    $validation_errors[] = 'O utilizador é obrigatório.';
}

if (empty($password)) {
    $validation_errors[] = 'A password é obrigatória.';
}

if (!empty($username) && !filter_var($username, FILTER_VALIDATE_EMAIL)) {
    $validation_errors[] = 'Introduza um email válido.';
}

if (!empty($password) && (strlen($password) < 6 || strlen($password) > 12)) {
    $validation_errors[] = 'A password deve ter entre 6 e 12 caracteres.';
}

// --------------------------------------------------------------------
// EXISTEM ERROS?
// --------------------------------------------------------------------
if (!empty($validation_errors)) {
    $_SESSION['validation_errors'] = $validation_errors;
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

// --------------------------------------------------------------------
// CRIAR SESSÃO DO UTILIZADOR
// --------------------------------------------------------------------
$_SESSION['utilizador'] = $username;

?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/nav.php'; ?>

<div class="container-fluid">
    <div class="row">

        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 p-4">

            <section>
                <h2><?php echo APP_NAME; ?></h2>

                <p>Escolhe uma opção no menu lateral para continuar.</p>

                <div class="alert alert-success mt-3">
                    Login efetuado com sucesso para:
                    <strong><?php echo htmlspecialchars($username); ?></strong>
                </div>
            </section>

        </main>

    </div>
</div>

<?php include 'includes/footer.php'; ?>