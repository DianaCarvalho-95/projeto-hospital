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

<header class="topbar-custom">

    <div class="dropdown ms-auto">

        <button class="btn user-button dropdown-toggle"
                type="button"
                data-bs-toggle="dropdown">

            <span class="user-avatar">
                <i class="fa-regular fa-user"></i>
            </span>

            <span class="user-name">
                <?= $perfil ?>
            </span>

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

</header>

<style>

    .topbar-custom {

        position: fixed;

        top: 0;

        right: 0;

        left: 16.666666%;

        height: 70px;

        background: #2F5D8A;

        display: flex;

        align-items: center;

        justify-content: flex-end;

        padding: 0 25px;

        z-index: 1000;

        font-family: "Segoe UI", Arial, sans-serif;
    }

    .user-button {

        background: transparent;

        border: none;

        color: white;

        display: flex;

        align-items: center;

        gap: 10px;

        font-weight: 600;

        padding: 8px 12px;
    }

    .user-button:hover {

        background: rgba(255,255,255,0.15);

        border-radius: 12px;

        color: white;
    }

    .user-avatar {

        width: 42px;

        height: 42px;

        border-radius: 50%;

        background: rgba(255,255,255,0.15);

        color: white;

        display: flex;

        align-items: center;

        justify-content: center;

        font-size: 1.4rem;
    }

    .user-name {

        font-size: 1rem;

        font-weight: 700;
    }

    @media (max-width: 991px) {

        .topbar-custom {

            left: 25%;
        }
    }

</style>