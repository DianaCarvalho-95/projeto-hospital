<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

if (($_SESSION['profile'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . '/private/views/dashboard/dashboard.php');
    exit;
}

$erro = '';
$sucesso = '';
$conteudos = [];
$itens = [];
$aba_ativa = isset($_GET['aba']) ? trim($_GET['aba']) : 'geral';
$abas_validas = ['geral', 'equipa', 'servicos', 'areas', 'planos'];

if (!in_array($aba_ativa, $abas_validas, true)) {
    $aba_ativa = 'geral';
}

$campos = [
    'hero_titulo' => 'Título principal',
    'hero_texto' => 'Texto de apresentação',
    'hero_botao' => 'Texto do botão',
    'contacto_intro' => 'Texto de contacto',
    'footer_localizacao' => 'Localização',
    'footer_horario' => 'Horário',
    'footer_email' => 'Email',
    'footer_telefone' => 'Telefone',
];

$nomes_seccoes = [
    'equipa' => 'Equipa',
    'servicos' => 'Serviços',
    'areas' => 'Áreas de Atuação',
    'planos' => 'Planos de Serviço'
];

try {
    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST . ";dbname=" . MYSQL_DATABASE . ";charset=utf8mb4",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );
    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $aba_ativa = isset($_POST['aba_ativa']) && in_array($_POST['aba_ativa'], $abas_validas, true)
            ? $_POST['aba_ativa']
            : 'geral';

        if ($aba_ativa === 'geral') {
            $stmt = $ligacao->prepare('UPDATE conteudos_publicos SET conteudo = :conteudo WHERE chave = :chave');
            foreach ($campos as $chave => $label) {
                $stmt->execute([
                    ':chave' => $chave,
                    ':conteudo' => trim($_POST[$chave] ?? '')
                ]);
            }
        }

        if (!empty($_POST['itens']) && is_array($_POST['itens'])) {
            $stmt_item = $ligacao->prepare(
                'UPDATE conteudos_publicos_itens
                 SET titulo = :titulo,
                     subtitulo = :subtitulo,
                     descricao = :descricao,
                     icone = :icone,
                     imagem = :imagem,
                     preco = :preco,
                     ativo = :ativo
                 WHERE id = :id'
            );

            foreach ($_POST['itens'] as $id => $dados) {
                $stmt_item->execute([
                    ':id' => (int) $id,
                    ':titulo' => trim($dados['titulo'] ?? ''),
                    ':subtitulo' => trim($dados['subtitulo'] ?? ''),
                    ':descricao' => trim($dados['descricao'] ?? ''),
                    ':icone' => trim($dados['icone'] ?? ''),
                    ':imagem' => trim($dados['imagem'] ?? ''),
                    ':preco' => trim($dados['preco'] ?? ''),
                    ':ativo' => isset($dados['ativo']) ? 1 : 0,
                ]);
            }
        }

        $sucesso = 'Conteúdos públicos atualizados com sucesso.';
    }

    foreach ($ligacao->query('SELECT chave, conteudo FROM conteudos_publicos') as $linha) {
        $conteudos[$linha['chave']] = $linha['conteudo'];
    }

    foreach ($ligacao->query('SELECT * FROM conteudos_publicos_itens ORDER BY seccao, ordem') as $linha) {
        $itens[$linha['seccao']][] = $linha;
    }
} catch (PDOException $err) {
    $erro = 'Aconteceu um erro ao carregar os conteúdos públicos.';
}

function h($valor)
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

function valor_conteudo($conteudos, $chave)
{
    return $conteudos[$chave] ?? '';
}

function classe_aba($aba, $aba_ativa)
{
    return $aba === $aba_ativa ? 'active' : '';
}

function resumo_item($texto)
{
    $texto = trim(preg_replace('/\s+/u', ' ', $texto ?? ''));
    return mb_strlen($texto, 'UTF-8') > 96 ? mb_substr($texto, 0, 96, 'UTF-8') . '...' : $texto;
}

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<style>
    .conteudos-page { background:#f5f7fa; min-height:100vh; padding:24px; }
    .page-title { color:#1E3A5F; font-size:1.8rem; font-weight:700; margin:0; }
    .page-subtitle { color:#64748b; font-size:.95rem; margin-bottom:0; }
    .tabs-card,.content-card { background:#fff; border:1px solid #dbe5ef; border-radius:14px; box-shadow:0 7px 18px rgba(15,23,42,.06); }
    .tabs-card { margin-bottom:14px; padding:8px 12px 0; }
    .content-card { padding:18px; }
    .content-tabs { border-bottom:1px solid #dbe5ef; display:flex; gap:4px; overflow-x:auto; }
    .content-tabs .nav-link { align-items:center; border:0; border-bottom:3px solid transparent; border-radius:0; color:#1E3A5F; display:inline-flex; font-size:.88rem; font-weight:700; gap:7px; padding:10px 14px; white-space:nowrap; }
    .content-tabs .nav-link.active { background:transparent; border-bottom-color:#0d6efd; color:#0d6efd; }
    .section-title { border-bottom:1px solid #e2e8f0; color:#1E3A5F; font-size:.98rem; font-weight:700; margin-bottom:12px; padding-bottom:8px; }
    .section-title::before { background:#2F5D8A; border-radius:999px; content:''; display:inline-block; height:18px; margin-right:8px; vertical-align:-3px; width:4px; }
    .form-label { color:#31506f; font-size:.72rem; font-weight:700; }
    .form-control { border-color:#d8e1ec; border-radius:9px; font-size:.86rem; min-height:34px; }
    textarea.form-control { min-height:68px; resize:vertical; }
    .btn-main { background:#2F5D8A; border-color:#2F5D8A; border-radius:9px; color:#fff; font-weight:700; min-height:38px; }
    .btn-main:hover { background:#1E3A5F; color:#fff; }
    .item-grid { display:grid; gap:12px; grid-template-columns:repeat(3,minmax(0,1fr)); }
    .item-box { background:#f8fafc; border:1px solid #dbe5ef; border-radius:12px; padding:12px; }
    .item-head { align-items:center; display:flex; gap:10px; justify-content:space-between; margin-bottom:8px; }
    .item-title { color:#1E3A5F; font-weight:700; }
    .small-help { color:#64748b; font-size:.76rem; white-space:nowrap; }
    .save-bar { border-top:1px solid #e2e8f0; display:flex; gap:8px; justify-content:flex-end; margin-top:14px; padding-top:12px; }
    .compact-grid { display:grid; gap:12px; grid-template-columns:repeat(5,minmax(0,1fr)); }
    .compact-item { background:#f8fafc; border:1px solid #dbe5ef; border-radius:12px; min-height:142px; padding:12px; display:flex; flex-direction:column; }
    .compact-icon { align-items:center; background:#edf4ff; border-radius:10px; color:#2F5D8A; display:inline-flex; height:34px; justify-content:center; margin-bottom:8px; width:34px; }
    .compact-title { color:#1E3A5F; font-weight:700; line-height:1.2; margin-bottom:5px; }
    .compact-desc { color:#64748b; font-size:.78rem; line-height:1.35; margin-bottom:10px; }
    .compact-footer { align-items:center; display:flex; justify-content:space-between; margin-top:auto; }
    .status-pill { border-radius:999px; font-size:.72rem; font-weight:700; padding:3px 8px; }
    .status-on { background:#dcfce7; color:#166534; }
    .status-off { background:#fee2e2; color:#991b1b; }
    .edit-mini { border-radius:8px; font-size:.76rem; font-weight:700; padding:4px 10px; }
    .edit-overlay { align-items:center; background:rgba(15,23,42,.45); bottom:0; display:none; justify-content:center; left:16.666666%; padding:20px; position:fixed; right:0; top:0; z-index:3000; }
    .edit-overlay.is-visible { display:flex; }
    .edit-modal { background:#fff; border:1px solid #dbe5ef; border-radius:14px; box-shadow:0 24px 60px rgba(15,23,42,.25); max-width:620px; padding:20px; width:100%; }
    .edit-modal-head { align-items:center; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; margin-bottom:14px; padding-bottom:10px; }
    .edit-modal-title { color:#1E3A5F; font-size:1.05rem; font-weight:700; }
    .modal-close { background:#f8fafc; border:1px solid #cbd5e1; border-radius:8px; color:#1E3A5F; font-weight:700; height:34px; width:34px; }
    .general-grid { display:grid; gap:14px; grid-template-columns:1.3fr .9fr; }
    .general-stack { display:grid; gap:12px; }
    .general-panel { background:#f8fafc; border:1px solid #dbe5ef; border-radius:12px; padding:14px; }
    .panel-title { align-items:center; color:#1E3A5F; display:flex; font-size:.95rem; font-weight:700; gap:8px; margin-bottom:10px; }
    .panel-title i { align-items:center; background:#edf4ff; border-radius:9px; color:#2F5D8A; display:inline-flex; height:30px; justify-content:center; width:30px; }
    .preview-card { background:#1E3A5F; border-radius:13px; color:#fff; overflow:hidden; }
    .preview-hero { background:linear-gradient(135deg,#1E3A5F,#2F5D8A); padding:18px; }
    .preview-label { color:rgba(255,255,255,.72); font-size:.68rem; font-weight:700; text-transform:uppercase; }
    .preview-title { font-size:1rem; font-weight:700; line-height:1.2; margin:6px 0; }
    .preview-text { color:rgba(255,255,255,.82); font-size:.8rem; line-height:1.4; margin:0; }
    .preview-button { background:#fff; border-radius:999px; color:#1E3A5F; display:inline-flex; font-size:.75rem; font-weight:700; margin-top:12px; padding:6px 11px; }
    .preview-footer { background:#fff; color:#1E3A5F; display:grid; gap:8px; padding:14px; }
    .preview-row { border:1px solid #dbe5ef; border-radius:9px; padding:8px 10px; }
    .preview-row strong { display:block; font-size:.68rem; text-transform:uppercase; }
    .preview-row span { color:#526985; font-size:.78rem; }
    @media (max-width:1400px){ .compact-grid{ grid-template-columns:repeat(3,minmax(0,1fr)); } }
    @media (max-width:1300px){ .general-grid{ grid-template-columns:1fr; } }
    @media (max-width:1300px){ .item-grid{ grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width:991px){ .edit-overlay{ left:25%; } }
</style>

<div class="container-fluid">
    <div class="row">
        <?php include '../../includes/sidebar.php'; ?>
        <main class="col-md-9 col-lg-10 conteudos-page">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h2 class="page-title"><i class="fa-solid fa-pen-to-square me-2"></i>Conteúdos Públicos</h2>
                    <p class="page-subtitle">Gestão dos textos, equipa, serviços, áreas e planos apresentados na página pública.</p>
                </div>
                <a href="<?php echo BASE_URL; ?>/public/index.php" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Ver página pública</a>
            </div>

            <?php if ($erro !== '') : ?><div class="alert alert-danger py-2"><?= h($erro) ?></div><?php endif; ?>
            <?php if ($sucesso !== '') : ?><div class="alert alert-success py-2"><?= h($sucesso) ?></div><?php endif; ?>

            <div class="tabs-card">
                <nav class="content-tabs nav">
                    <a class="nav-link <?= classe_aba('geral', $aba_ativa) ?>" href="?aba=geral"><i class="fa-solid fa-house"></i>Geral</a>
                    <a class="nav-link <?= classe_aba('equipa', $aba_ativa) ?>" href="?aba=equipa"><i class="fa-solid fa-users"></i>Equipa</a>
                    <a class="nav-link <?= classe_aba('servicos', $aba_ativa) ?>" href="?aba=servicos"><i class="fa-solid fa-briefcase-medical"></i>Serviços</a>
                    <a class="nav-link <?= classe_aba('areas', $aba_ativa) ?>" href="?aba=areas"><i class="fa-solid fa-layer-group"></i>Áreas</a>
                    <a class="nav-link <?= classe_aba('planos', $aba_ativa) ?>" href="?aba=planos"><i class="fa-solid fa-tags"></i>Planos</a>
                </nav>
            </div>

            <form method="post" class="content-card">
                <input type="hidden" name="aba_ativa" value="<?= h($aba_ativa) ?>">

                <?php if ($aba_ativa === 'geral') : ?>
                    <div class="general-grid">
                        <div class="general-stack">
                            <div class="general-panel">
                                <div class="panel-title"><i class="fa-solid fa-house"></i>Página inicial</div>
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label">Título principal</label>
                                        <input type="text" name="hero_titulo" class="form-control" value="<?= h(valor_conteudo($conteudos, 'hero_titulo')) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Texto do botão</label>
                                        <input type="text" name="hero_botao" class="form-control" value="<?= h(valor_conteudo($conteudos, 'hero_botao')) ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Texto de apresentação</label>
                                        <textarea name="hero_texto" class="form-control"><?= h(valor_conteudo($conteudos, 'hero_texto')) ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="general-panel">
                                <div class="panel-title"><i class="fa-solid fa-envelope"></i>Contacto</div>
                                <label class="form-label">Texto de contacto</label>
                                <textarea name="contacto_intro" class="form-control"><?= h(valor_conteudo($conteudos, 'contacto_intro')) ?></textarea>
                            </div>

                            <div class="general-panel">
                                <div class="panel-title"><i class="fa-solid fa-circle-info"></i>Rodapé</div>
                                <div class="row g-3">
                                    <div class="col-md-6"><label class="form-label">Localização</label><input type="text" name="footer_localizacao" class="form-control" value="<?= h(valor_conteudo($conteudos, 'footer_localizacao')) ?>"></div>
                                    <div class="col-md-6"><label class="form-label">Horário</label><input type="text" name="footer_horario" class="form-control" value="<?= h(valor_conteudo($conteudos, 'footer_horario')) ?>"></div>
                                    <div class="col-md-6"><label class="form-label">Email</label><input type="text" name="footer_email" class="form-control" value="<?= h(valor_conteudo($conteudos, 'footer_email')) ?>"></div>
                                    <div class="col-md-6"><label class="form-label">Telefone</label><input type="text" name="footer_telefone" class="form-control" value="<?= h(valor_conteudo($conteudos, 'footer_telefone')) ?>"></div>
                                </div>
                            </div>
                        </div>

                        <div class="preview-card">
                            <div class="preview-hero">
                                <div class="preview-label">Pré-visualização</div>
                                <div class="preview-title"><?= h(valor_conteudo($conteudos, 'hero_titulo')) ?></div>
                                <p class="preview-text"><?= h(valor_conteudo($conteudos, 'hero_texto')) ?></p>
                                <span class="preview-button"><?= h(valor_conteudo($conteudos, 'hero_botao')) ?></span>
                            </div>
                            <div class="preview-footer">
                                <div class="preview-row"><strong>Contacto</strong><span><?= h(valor_conteudo($conteudos, 'contacto_intro')) ?></span></div>
                                <div class="preview-row"><strong>Localização</strong><span><?= h(valor_conteudo($conteudos, 'footer_localizacao')) ?></span></div>
                                <div class="preview-row"><strong>Horário</strong><span><?= h(valor_conteudo($conteudos, 'footer_horario')) ?></span></div>
                                <div class="preview-row"><strong>Contactos</strong><span><?= h(valor_conteudo($conteudos, 'footer_email')) ?> · <?= h(valor_conteudo($conteudos, 'footer_telefone')) ?></span></div>
                            </div>
                        </div>
                    </div>
                    <div class="save-bar">
                        <a href="<?php echo BASE_URL; ?>/private/views/dashboard/dashboard.php" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-main">
                            <i class="fa-solid fa-floppy-disk me-1"></i>
                            Guardar conteúdos
                        </button>
                    </div>
                <?php else : ?>
                    <div class="section-title"><?= h($nomes_seccoes[$aba_ativa] ?? 'Conteúdos') ?></div>
                    <div class="compact-grid">
                        <?php foreach (($itens[$aba_ativa] ?? []) as $item) : ?>
                            <?php
                                $icone_item = !empty($item['icone']) ? $item['icone'] : ($aba_ativa === 'equipa' ? 'fa-solid fa-user' : 'fa-solid fa-tag');
                                $descricao_card = $aba_ativa === 'planos' && !empty($item['preco']) ? $item['preco'] : ($item['subtitulo'] ?: resumo_item($item['descricao']));
                            ?>
                            <div class="compact-item">
                                <div class="compact-icon"><i class="<?= h($icone_item) ?>"></i></div>
                                <div class="compact-title"><?= h($item['titulo']) ?></div>
                                <div class="compact-desc"><?= h(resumo_item($descricao_card)) ?></div>
                                <div class="compact-footer">
                                    <span class="status-pill <?= (int) $item['ativo'] === 1 ? 'status-on' : 'status-off' ?>"><?= (int) $item['ativo'] === 1 ? 'Ativo' : 'Inativo' ?></span>
                                    <button type="button" class="btn btn-sm btn-outline-primary edit-mini js-open-edit" data-target="edit-item-<?= (int) $item['id'] ?>"><i class="fa-solid fa-pen me-1"></i>Editar</button>
                                </div>
                            </div>

                            <div class="edit-overlay" id="edit-item-<?= (int) $item['id'] ?>" aria-hidden="true">
                                <div class="edit-modal" role="dialog" aria-modal="true">
                                    <div class="edit-modal-head">
                                        <div class="edit-modal-title">Editar <?= h($item['titulo']) ?></div>
                                        <button type="button" class="modal-close js-close-edit">×</button>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-8"><label class="form-label">Título</label><input type="text" name="itens[<?= (int) $item['id'] ?>][titulo]" class="form-control" value="<?= h($item['titulo']) ?>"></div>
                                        <div class="col-md-4"><label class="form-label">Estado</label><label class="form-control d-flex align-items-center gap-2"><input type="checkbox" name="itens[<?= (int) $item['id'] ?>][ativo]" <?= (int) $item['ativo'] === 1 ? 'checked' : '' ?>> Ativo</label></div>
                                        <div class="col-12"><label class="form-label">Subtítulo</label><input type="text" name="itens[<?= (int) $item['id'] ?>][subtitulo]" class="form-control" value="<?= h($item['subtitulo']) ?>"></div>
                                        <div class="col-12"><label class="form-label">Descrição</label><textarea name="itens[<?= (int) $item['id'] ?>][descricao]" class="form-control"><?= h($item['descricao']) ?></textarea></div>
                                        <div class="col-md-4"><label class="form-label">Ícone</label><input type="text" name="itens[<?= (int) $item['id'] ?>][icone]" class="form-control" value="<?= h($item['icone']) ?>"></div>
                                        <div class="col-md-4"><label class="form-label">Imagem</label><input type="text" name="itens[<?= (int) $item['id'] ?>][imagem]" class="form-control" value="<?= h($item['imagem']) ?>"></div>
                                        <div class="col-md-4"><label class="form-label">Preço</label><input type="text" name="itens[<?= (int) $item['id'] ?>][preco]" class="form-control" value="<?= h($item['preco']) ?>"></div>
                                    </div>
                                    <div class="save-bar"><button type="button" class="btn btn-outline-secondary js-close-edit">Fechar</button><button type="submit" class="btn btn-main">Guardar alterações</button></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </form>
        </main>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.js-open-edit').forEach(function (button) {
            button.addEventListener('click', function () {
                const modal = document.getElementById(button.dataset.target);
                if (modal) {
                    modal.classList.add('is-visible');
                    modal.setAttribute('aria-hidden', 'false');
                }
            });
        });

        document.querySelectorAll('.js-close-edit').forEach(function (button) {
            button.addEventListener('click', function () {
                const modal = button.closest('.edit-overlay');
                if (modal) {
                    modal.classList.remove('is-visible');
                    modal.setAttribute('aria-hidden', 'true');
                }
            });
        });

        document.querySelectorAll('.edit-overlay').forEach(function (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    modal.classList.remove('is-visible');
                    modal.setAttribute('aria-hidden', 'true');
                }
            });
        });
    });
</script>

<?php include '../../includes/footer.php'; ?>
