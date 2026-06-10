<?php
$current_path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

function menu_ativo($parte)
{
    global $current_path;
    return strpos($current_path, $parte) !== false ? 'active' : '';
}
?>

<style>
    body {
        background: #f5f7fa;
        font-family: "Segoe UI", Arial, sans-serif;
    }

    .sidebar-custom {
        background: linear-gradient(180deg, #1E3A5F 0%, #183252 100%);
        box-shadow: 4px 0 18px rgba(15, 23, 42, 0.18);
        display: flex;
        flex-direction: column;
        left: 0;
        min-height: 100vh;
        padding: 18px 14px;
        position: fixed;
        top: 0;
        width: 16.666666%;
        z-index: 1001;
    }

    .sidebar-brand {
        align-items: center;
        display: flex;
        flex-direction: column;
        margin-bottom: 22px;
        padding: 0 8px 18px;
        position: relative;
    }

    .sidebar-brand::after {
        background: rgba(255, 255, 255, 0.14);
        bottom: 0;
        content: '';
        height: 1px;
        left: 8px;
        position: absolute;
        right: 8px;
    }

    .sidebar-logo-wrap {
        align-items: center;
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid rgba(255, 255, 255, 0.35);
        border-radius: 14px;
        box-shadow: 0 12px 28px rgba(7, 18, 34, 0.18);
        display: flex;
        justify-content: center;
        min-height: 118px;
        padding: 10px;
        width: min(150px, 100%);
    }

    .sidebar-logo {
        display: block;
        max-width: 128px;
        width: 100%;
    }

    .menu-label {
        color: rgba(255, 255, 255, 0.58);
        font-size: 0.66rem;
        font-weight: 850;
        letter-spacing: 0.08em;
        margin: 4px 10px 10px;
        text-transform: uppercase;
    }

    .menu-link {
        align-items: center;
        border: 1px solid transparent;
        border-radius: 11px;
        color: rgba(255, 255, 255, 0.86);
        display: flex;
        font-weight: 750;
        gap: 11px;
        margin-bottom: 6px;
        padding: 10px 12px;
        position: relative;
        text-decoration: none;
        transition: background 0.18s ease, border-color 0.18s ease, transform 0.18s ease;
    }

    .menu-link i {
        color: rgba(255, 255, 255, 0.84);
        font-size: 0.95rem;
        text-align: center;
        width: 22px;
    }

    .menu-link:hover {
        background: rgba(255, 255, 255, 0.10);
        border-color: rgba(255, 255, 255, 0.10);
        color: #ffffff;
        transform: translateX(2px);
    }

    .menu-link:hover i {
        color: #ffffff;
    }

    .menu-link.active {
        background: #ffffff;
        border-color: rgba(255, 255, 255, 0.85);
        box-shadow: 0 8px 20px rgba(7, 18, 34, 0.18);
        color: #1E3A5F;
    }

    .menu-link.active i {
        color: #2F5D8A;
    }

    .menu-link.active::before {
        background: #2F5D8A;
        border-radius: 999px;
        bottom: 9px;
        content: '';
        left: -6px;
        position: absolute;
        top: 9px;
        width: 4px;
    }

    .sidebar-footer {
        color: rgba(255, 255, 255, 0.48);
        font-size: 0.68rem;
        line-height: 1.35;
        margin-top: auto;
        padding: 14px 10px 4px;
    }

    main {
        margin-left: 16.666666%;
        padding-top: 90px !important;
    }

    @media (max-width: 991px) {
        .sidebar-custom {
            width: 25%;
        }

        main {
            margin-left: 25%;
        }

        .sidebar-logo-wrap {
            min-height: 96px;
        }
    }
</style>

<aside class="sidebar-custom">
    <div class="sidebar-brand">
        <div class="sidebar-logo-wrap">
            <img
                src="<?php echo BASE_URL; ?>/private/assets/img/hospital255.png"
                alt="MedTech Solutions"
                class="sidebar-logo">
        </div>
    </div>

    <nav>
        <div class="menu-label">Menu principal</div>

        <a href="<?php echo BASE_URL; ?>/private/views/dashboard/dashboard.php"
           class="menu-link <?php echo menu_ativo('/dashboard/'); ?>">
            <i class="fas fa-chart-line"></i>
            Dashboard
        </a>

        <a href="<?php echo BASE_URL; ?>/private/views/equipamentos/lista.php"
           class="menu-link <?php echo menu_ativo('/equipamentos/'); ?>">
            <i class="fas fa-desktop"></i>
            Equipamentos
        </a>

        <a href="<?php echo BASE_URL; ?>/private/views/localizacoes/lista.php"
           class="menu-link <?php echo menu_ativo('/localizacoes/'); ?>">
            <i class="fas fa-location-dot"></i>
            Localizações
        </a>

        <a href="<?php echo BASE_URL; ?>/private/views/fornecedores/lista.php"
           class="menu-link <?php echo menu_ativo('/fornecedores/'); ?>">
            <i class="fas fa-truck-medical"></i>
            Fornecedores
        </a>

        <?php if (($_SESSION['profile'] ?? '') === 'admin') : ?>
            <a href="<?php echo BASE_URL; ?>/private/views/utilizadores/lista.php"
               class="menu-link <?php echo menu_ativo('/utilizadores/'); ?>">
                <i class="fas fa-users"></i>
                Utilizadores
            </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        MedTech Solutions<br>
        Gestão de equipamentos médicos
    </div>
</aside>
