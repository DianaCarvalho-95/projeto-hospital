<?php
$current_path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

function menu_ativo($parte)
{
    global $current_path;
    return strpos($current_path, $parte) !== false ? 'active' : '';
}
?>

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

        <?php if (($_SESSION['profile'] ?? '') === 'admin') : ?>
            <a href="<?php echo BASE_URL; ?>/private/views/conteudos-publicos/lista.php"
               class="menu-link <?php echo menu_ativo('/conteudos-publicos/'); ?>">
                <i class="fas fa-pen-to-square"></i>
                Conteúdos Públicos
            </a>
        <?php endif; ?>

        <?php if (($_SESSION['profile'] ?? '') === 'admin') : ?>
            <a href="<?php echo BASE_URL; ?>/private/views/registos/lista.php"
               class="menu-link <?php echo menu_ativo('/registos/'); ?>">
                <i class="fas fa-clock-rotate-left"></i>
                Registos
            </a>
        <?php endif; ?>

        <a href="<?php echo BASE_URL; ?>/private/views/arquivo/lista.php"
           class="menu-link <?php echo menu_ativo('/arquivo/'); ?>">
            <i class="fas fa-box-archive"></i>
            Arquivo
        </a>
    </nav>

    <div class="sidebar-footer">
        MedTech Solutions<br>
        Gestão de equipamentos médicos
    </div>
</aside>




