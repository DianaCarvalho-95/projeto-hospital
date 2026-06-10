<?php

$nome_topbar = 'Utilizador';
$perfil_topbar = 'Acesso privado';
$foto_topbar = BASE_URL . '/private/assets/img/utilizadores/admin.png';

if (!empty($_SESSION['utilizador'])) {
    try {
        $ligacao_nav = new PDO(
            "mysql:host=" . MYSQL_HOST . ";dbname=" . MYSQL_DATABASE . ";charset=utf8mb4",
            MYSQL_USERNAME,
            MYSQL_PASSWORD
        );
        $ligacao_nav->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt_nav = $ligacao_nav->prepare(
            "SELECT display_name, name, profile, fotografia
             FROM agents
             WHERE name = :utilizador
             LIMIT 1"
        );
        $stmt_nav->execute([':utilizador' => $_SESSION['utilizador']]);
        $utilizador_nav = $stmt_nav->fetch(PDO::FETCH_OBJ);

        if ($utilizador_nav) {
            $nome_topbar = $utilizador_nav->display_name ?: $utilizador_nav->name;
            $perfil_topbar = $utilizador_nav->profile === 'admin' ? 'Administrador' : 'Técnico';
            if (!empty($utilizador_nav->fotografia)) {
                $foto_topbar = BASE_URL . '/private/assets/img/utilizadores/' . rawurlencode($utilizador_nav->fotografia);
            }
        }
    } catch (PDOException $err) {
        $perfil_topbar = ($_SESSION['profile'] ?? '') === 'admin' ? 'Administrador' : 'Técnico';
    }
}

?>

<header class="topbar-custom">
    <div class="dropdown ms-auto user-menu">
        <button class="btn user-button dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="<?= htmlspecialchars($foto_topbar, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($nome_topbar, ENT_QUOTES, 'UTF-8') ?>" class="user-avatar-img">
            <span class="user-info">
                <span class="user-role"><?= htmlspecialchars($perfil_topbar, ENT_QUOTES, 'UTF-8') ?></span>
                <span class="user-email"><?= htmlspecialchars($_SESSION['utilizador'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
            </span>
        </button>

        <ul class="dropdown-menu dropdown-menu-end user-dropdown">
            <li class="dropdown-header user-dropdown-header">
                <img src="<?= htmlspecialchars($foto_topbar, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($nome_topbar, ENT_QUOTES, 'UTF-8') ?>" class="dropdown-avatar">
                <span>
                    <strong><?= htmlspecialchars($nome_topbar, ENT_QUOTES, 'UTF-8') ?></strong>
                    <small><?= htmlspecialchars($perfil_topbar, ENT_QUOTES, 'UTF-8') ?></small>
                </span>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item user-dropdown-item" href="<?php echo BASE_URL; ?>/private/views/utilizadores/alterar-password.php">
                    <i class="fa-solid fa-key"></i>
                    Alterar password
                </a>
            </li>
            <li>
                <a class="dropdown-item user-dropdown-item logout-item" href="<?php echo BASE_URL; ?>/private/logout.php">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    Sair
                </a>
            </li>
        </ul>
    </div>
</header>

<style>
    .topbar-custom {
        align-items: center;
        background: #2F5D8A;
        display: flex;
        font-family: "Segoe UI", Arial, sans-serif;
        height: 70px;
        justify-content: flex-end;
        left: 16.666666%;
        padding: 0 24px;
        position: fixed;
        right: 0;
        top: 0;
        z-index: 1000;
    }

    .user-button {
        align-items: center;
        background: rgba(255, 255, 255, 0.10);
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 999px;
        color: #ffffff;
        display: flex;
        gap: 10px;
        min-height: 44px;
        padding: 5px 12px 5px 6px;
    }

    .user-button:hover,
    .user-button:focus {
        background: rgba(255, 255, 255, 0.18);
        color: #ffffff;
    }

    .user-avatar-img {
        border: 2px solid rgba(255, 255, 255, 0.55);
        border-radius: 50%;
        height: 34px;
        object-fit: cover;
        width: 34px;
    }

    .user-info {
        display: flex;
        flex-direction: column;
        line-height: 1.08;
        text-align: left;
    }

    .user-role {
        font-size: 0.9rem;
        font-weight: 850;
    }

    .user-email {
        color: rgba(255, 255, 255, 0.76);
        font-size: 0.68rem;
        max-width: 180px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .user-dropdown {
        border: 1px solid #dbe5ef;
        border-radius: 12px;
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.16);
        min-width: 250px;
        overflow: hidden;
        padding: 8px;
    }

    .user-dropdown-header {
        align-items: center;
        color: #1E3A5F;
        display: flex;
        gap: 10px;
        padding: 8px 8px 6px;
        white-space: normal;
    }

    .user-dropdown-header strong,
    .user-dropdown-header small {
        display: block;
    }

    .user-dropdown-header small {
        color: #64748b;
        font-size: 0.74rem;
        margin-top: 1px;
    }

    .dropdown-avatar {
        border: 2px solid #dbeafe;
        border-radius: 50%;
        height: 40px;
        object-fit: cover;
        width: 40px;
    }

    .user-dropdown-item {
        align-items: center;
        border-radius: 9px;
        color: #1E3A5F;
        display: flex;
        font-size: 0.9rem;
        font-weight: 750;
        gap: 10px;
        padding: 9px 10px;
    }

    .user-dropdown-item i {
        color: #2F5D8A;
        width: 18px;
    }

    .user-dropdown-item:hover {
        background: #edf4ff;
        color: #1E3A5F;
    }

    .logout-item {
        color: #be123c;
    }

    .logout-item i {
        color: #be123c;
    }

    .logout-item:hover {
        background: #fff1f2;
        color: #be123c;
    }

    @media (max-width: 991px) {
        .topbar-custom {
            left: 25%;
        }

        .user-email {
            display: none;
        }
    }
</style>
