<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MedTech Solutions</title>

    <link rel="stylesheet" href="/9/public/assets/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="/9/public/assets/fontawesome/all.min.css">

    <link rel="shortcut icon" href="/9/public/assets/img/hospital125.png" type="image/png">
</head>

<body>

<div class="container-fluid mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-6 col-sm-8 col-10">

            <div class="card p-4">

                <div class="d-flex align-items-center justify-content-center my-4">
                    <img src="/9/public/assets/img/hospital125.png" alt="Logo MedTech Solutions" width="80" class="me-3">
                    <h2><strong>MedTech Solutions</strong></h2>
                </div>

                <form action="/9/private/index.php" method="post">

                    <div class="mb-3">
                        <label for="email" class="form-label">Utilizador</label>
                        <input type="email" name="email" id="email" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" id="password" class="form-control">
                    </div>

                    <div class="mb-3 text-center">
                        <button type="submit" class="btn btn-secondary px-4">
                            Entrar <i class="fa-solid fa-right-to-bracket ms-2"></i>
                        </button>
                    </div>

                    <div class="alert alert-danger p-2 text-center">
                        Erro: Utilizador não registado
                    </div>

                </form>

            </div>

        </div>
    </div>
</div>

<script src="/9/public/assets/bootstrap/bootstrap.bundle.min.js"></script>

</body>
</html>