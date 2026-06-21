<?php

session_start();

$validation_errors = [];
$server_error = '';

if (!empty($_SESSION['validation_errors'])) {
    $validation_errors = $_SESSION['validation_errors'];
    unset($_SESSION['validation_errors']);
}

if (!empty($_SESSION['server_error'])) {
    $server_error = $_SESSION['server_error'];
    unset($_SESSION['server_error']);
}

require_once '../config/config.php';

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MedTech Solutions</title>
    <link rel="shortcut icon" href="/PROJETO-HOSPITAL/8/private/assets/img/hospital125.png" type="image/png">
    <link rel="stylesheet" href="/PROJETO-HOSPITAL/8/private/assets/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="/PROJETO-HOSPITAL/8/private/assets/fontawesome/all.min.css">
    <link rel="stylesheet" href="/PROJETO-HOSPITAL/8/private/assets/css/1232099.css?v=20260617-login-white">
</head>
<body class="login-body">
<div class="login-page">

    <div class="login-card">

        <!-- Logótipo da aplicação -->

        <div class="text-center">

            <img
                src="/PROJETO-HOSPITAL/8/private/assets/img/hospital255.png"
                alt="Logo MedTech Solutions"
                class="login-logo">

        </div>

        <!-- Formulário de autenticação -->

        <form action="/PROJETO-HOSPITAL/8/private/index.php"
              method="post">

            <div class="mb-3">

                <label class="form-label login-label">

                    <i class="fa-regular fa-user me-2 text-primary"></i>

                    Utilizador

                </label>

                <input
                    type="email"
                    class="form-control login-input"
                    name="text_username"
                    placeholder="Introduza o email">

            </div>

            <div class="mb-4">

                <label class="form-label login-label">

                    <i class="fa-solid fa-lock me-2 text-primary"></i>

                    Password

                </label>

                <input
                    type="password"
                    class="form-control login-input"
                    name="text_password"
                    placeholder="Introduza a password">

            </div>

            <button
                type="submit"
                class="login-button">

                Entrar

                <i class="fa-solid fa-right-to-bracket ms-2"></i>

            </button>

            <?php if (!empty($validation_errors)) : ?>

                <div class="alert alert-danger mt-3 text-center">

                    <?php foreach ($validation_errors as $error) : ?>

                        <div>
                            <?= htmlspecialchars($error) ?>
                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

            <?php if (!empty($server_error)) : ?>

                <div class="alert alert-danger mt-3 text-center">

                    <?= htmlspecialchars($server_error) ?>

                </div>

            <?php endif; ?>

        </form>

        <div class="login-footer">

            <i class="fa-solid fa-shield-halved me-1"></i>

            Acesso restrito. Todos os direitos reservados.

        </div>

    </div>

</div>

<script src="/PROJETO-HOSPITAL/8/private/assets/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>



