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
        "mysql:host=" . MYSQL_HOST . ";port=" . MYSQL_PORT . ";dbname=" . MYSQL_DATABASE . ";charset=utf8mb4",
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

        registar_evento('Conteúdos Públicos', 'Edição', 'Conteúdo público', null, 'Conteúdos da página pública atualizados.');

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
<div class="container-fluid">
    <div class="row">
        <?php include '../../includes/sidebar.php'; ?>
        <main class="col-md-9 col-lg-10 conteudos-page">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h2 class="page-title"><i class="fa-solid fa-pen-to-square me-2"></i>Conteúdos Públicos</h2>
                    <p class="page-subtitle">Gestão dos textos, equipa, serviços, áreas e planos apresentados na página pública.</p>
                </div>
                <a href="<?php echo BASE_URL; ?>/public/index.php" target="_blank" class="btn-public-page"><i class="fa-solid fa-arrow-up-right-from-square"></i>Ver página pública</a>
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
<?php include '../../includes/footer.php'; ?>







