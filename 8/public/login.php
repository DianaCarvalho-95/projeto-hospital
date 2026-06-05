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

<div class="container-fluid mt-5">
    <div class="row justify-content-center">

        <div class="col-lg-5 col-md-6 col-sm-8 col-10">

            <div class="card p-4">

                <!-- LOGÓTIPO -->
                <div class="text-center mb-0">

                    <img src="/PROJETO-HOSPITAL/8/private/assets/img/hospital255.png"
                         alt="Logo MedTech Solutions"
                         width="350"
                         class="mb-0">

                </div>

                <!-- FORMULÁRIO -->
                <form action="/PROJETO-HOSPITAL/8/private/index.php"
                      method="post">

                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Utilizador
                        </label>

                        <input type="email"
                               class="form-control"
                               name="text_username">

                    </div>

                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Password
                        </label>

                        <input type="password"
                               class="form-control"
                               name="text_password">

                    </div>

                    <div class="mb-3 text-center">

                        <button type="submit"
                                class="btn btn-secondary px-4">

                            Entrar
                            <i class="fa-solid fa-right-to-bracket ms-2"></i>

                        </button>

                    </div>

                    <?php if (!empty($validation_errors)) : ?>

                        <div class="alert alert-danger p-2 text-center">

                            <?php foreach ($validation_errors as $error) : ?>

                                <div>
                                    <?= htmlspecialchars($error) ?>
                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                    <?php if (!empty($server_error)) : ?>

                        <div class="alert alert-danger p-2 text-center">

                            <?= htmlspecialchars($server_error) ?>

                        </div>

                    <?php endif; ?>

                </form>

            </div>

        </div>

    </div>
</div>

<?php include '../private/includes/footer.php'; ?>