<header class="container-fluid bg-dark text-white">
    <div class="row align-items-center">

        <div class="col-6 d-flex align-items-center p-3">

            <a href="<?php echo BASE_URL; ?>/private/views/dashboard/dashboard.php">
                <img src="<?php echo BASE_URL; ?>/private/assets/img/hospital125.png"
                    alt="Logo MedTech Solutions"
                    height="40"
                    class="me-3">
            </a>

            <h3 class="mb-0"><?php echo APP_NAME; ?></h3>

        </div>

        <div class="col-6 text-end p-3">

            <?php

            $perfil = 'Utilizador';

            if (!empty($_SESSION['utilizador'])) {

                if ($_SESSION['utilizador'] == 'admin@medtech.pt') {
                    $perfil = 'Administrador';
                }

                if ($_SESSION['utilizador'] == 'tecnico@medtech.pt') {
                    $perfil = 'Técnico';
                }
            }

            ?>

            <div class="dropdown">

                <button class="btn btn-secondary dropdown-toggle"
                    type="button"
                    data-bs-toggle="dropdown">

                    <i class="fa-regular fa-user me-2"></i>
                    <?php echo $perfil; ?>

                </button>

                <ul class="dropdown-menu dropdown-menu-end">

                    <li>
                        <a class="dropdown-item" href="#">
                            <i class="fa-solid fa-key me-2"></i>
                            Alterar password
                        </a>
                    </li>

                    <li>
                        <hr class="dropdown-divider">
                    </li>

                    <li>
                        <a class="dropdown-item"
                            href="<?php echo BASE_URL; ?>/public/login.php">

                            <i class="fa-solid fa-right-from-bracket me-2"></i>
                            Sair

                        </a>
                    </li>

                </ul>

            </div>

        </div>

    </div>
</header>