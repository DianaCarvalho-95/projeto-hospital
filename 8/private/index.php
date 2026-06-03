<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: /PROJETO-HOSPITAL/8/public/login.php');
    return;
}

$username = isset($_POST['text_username'])
    ? trim($_POST['text_username'])
    : '';

$password = isset($_POST['text_password'])
    ? trim($_POST['text_password'])
    : '';

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

if (!empty($validation_errors)) {
    $_SESSION['validation_errors'] = $validation_errors;
    header('Location: /PROJETO-HOSPITAL/8/public/login.php');
    return;
}
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