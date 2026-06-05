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

include '../private/includes/header.php';

?>

<style>

/*
|--------------------------------------------------------------------------
| PÁGINA DE LOGIN
|--------------------------------------------------------------------------
*/

.login-page {

    min-height: 100vh;

    background-image:
        url('../private/assets/img/fundo_login.png');

    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;

    display: flex;
    align-items: center;
    justify-content: center;

    padding: 30px;
}

/*
|--------------------------------------------------------------------------
| CARTÃO CENTRAL
|--------------------------------------------------------------------------
| Contém o formulário de autenticação.
|--------------------------------------------------------------------------
*/

.login-card {

    width: 100%;
    max-width: 560px;

    background: rgba(255,255,255,0.92);

    backdrop-filter: blur(4px);

    border-radius: 22px;

    padding: 40px;

    box-shadow: 0 15px 35px rgba(0,0,0,0.15);

    border: 1px solid rgba(255,255,255,0.8);
}

/*
|--------------------------------------------------------------------------
| LOGÓTIPO
|--------------------------------------------------------------------------
*/

.login-logo {

    width: 240px;
    max-width: 100%;

    margin-bottom: 25px;
}

/*
|--------------------------------------------------------------------------
| LABELS
|--------------------------------------------------------------------------
*/

.login-label {

    font-weight: 600;
    color: #1f2937;
}

/*
|--------------------------------------------------------------------------
| CAMPOS DE TEXTO
|--------------------------------------------------------------------------
*/

.login-input {

    padding: 12px 14px;

    border-radius: 10px;

    border: 1px solid #ced4da;
}

/*
|--------------------------------------------------------------------------
| BOTÃO DE LOGIN
|--------------------------------------------------------------------------
*/

.login-button {

    width: 100%;

    padding: 12px;

    border: none;

    border-radius: 10px;

    background: linear-gradient(
        135deg,
        #0d6efd,
        #084298
    );

    color: white;

    font-weight: 600;

    transition: 0.2s;
}

.login-button:hover {

    background: linear-gradient(
        135deg,
        #0b5ed7,
        #052c65
    );

    color: white;
}

/*
|--------------------------------------------------------------------------
| TEXTO INFERIOR
|--------------------------------------------------------------------------
*/

.login-footer {

    margin-top: 20px;

    text-align: center;

    color: #6c757d;

    font-size: 0.9rem;
}

</style>

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

<?php include '../private/includes/footer.php'; ?>