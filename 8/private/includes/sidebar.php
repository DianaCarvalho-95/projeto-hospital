<style>

    body {

        font-family: "Segoe UI", Arial, sans-serif;

        background: #f5f7fa;
    }

    .sidebar-custom {

        position: fixed;

        top: 0;

        left: 0;

        width: 16.666666%;

        min-height: 100vh;

        background: #1E3A5F;

        box-shadow: 4px 0 15px rgba(0,0,0,0.15);

        padding: 24px 16px;

        z-index: 1001;
    }

    .sidebar-logo {

        max-width: 150px;

        margin-bottom: 35px;

        filter: brightness(1.1);
    }

    .menu-link {

        display: flex;

        align-items: center;

        gap: 12px;

        text-decoration: none;

        color: white;

        padding: 12px 14px;

        border-radius: 12px;

        margin-bottom: 8px;

        font-weight: 600;

        transition: 0.2s;
    }

    .menu-link:hover {

        background: rgba(255,255,255,0.15);

        color: white;
    }

    .menu-link i {

        width: 20px;

        text-align: center;

        color: white;
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
    }

</style>

<aside class="sidebar-custom">

    <div class="text-center">

        <img
            src="<?php echo BASE_URL; ?>/private/assets/img/hospital255.png"
            alt="MedTech Solutions"
            class="sidebar-logo">

    </div>

    <nav>

        <a href="<?php echo BASE_URL; ?>/private/views/dashboard/dashboard.php"
           class="menu-link">

            <i class="fas fa-chart-line"></i>
            Dashboard

        </a>

        <a href="<?php echo BASE_URL; ?>/private/views/equipamentos/lista.php"
           class="menu-link">

            <i class="fas fa-cogs"></i>
            Equipamentos

        </a>

        <a href="<?php echo BASE_URL; ?>/private/views/localizacoes/lista.php"
           class="menu-link">

            <i class="fas fa-location-dot"></i>
            Localizações

        </a>

        <a href="<?php echo BASE_URL; ?>/private/views/fornecedores/lista.php"
           class="menu-link">

            <i class="fas fa-truck-medical"></i>
            Fornecedores

        </a>

        <a href="<?php echo BASE_URL; ?>/private/views/documentacao/lista.php"
           class="menu-link">

            <i class="fas fa-file-medical"></i>
            Documentação

        </a>

        <a href="<?php echo BASE_URL; ?>/private/views/ferramentas/ferramentas.php"
           class="menu-link">

            <i class="fas fa-screwdriver-wrench"></i>
            Ferramentas

        </a>

    </nav>

</aside>