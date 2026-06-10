<?php
require_once __DIR__ . '/../config/config.php';
$itens = [];
$conteudos = [
    'hero_titulo' => 'Inovação e controlo para a tecnologia hospitalar',
    'hero_texto' => 'A MedTech Solutions disponibiliza uma plataforma para registo, localização, documentação e acompanhamento dos equipamentos médicos ao longo do seu ciclo de vida.',
    'hero_botao' => 'Pedir demonstração',
    'contacto_intro' => 'Entre em contacto connosco para pedir uma demonstração ou esclarecer dúvidas.',
    'footer_localizacao' => 'Porto, Portugal',
    'footer_horario' => 'Segunda a Sexta: 09h - 18h',
    'footer_email' => 'geral@medtechsolutions.pt',
    'footer_telefone' => '+351 222 258 053',
];
try {
    $ligacao = new PDO("mysql:host=" . MYSQL_HOST . ";dbname=" . MYSQL_DATABASE . ";charset=utf8mb4", MYSQL_USERNAME, MYSQL_PASSWORD);
    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    foreach ($ligacao->query('SELECT chave, conteudo FROM conteudos_publicos') as $linha) { $conteudos[$linha['chave']] = $linha['conteudo']; }
    foreach ($ligacao->query('SELECT * FROM conteudos_publicos_itens WHERE ativo = 1 ORDER BY seccao, ordem') as $item) { $itens[$item['seccao']][] = $item; }
} catch (PDOException $err) {}
function h($valor) { return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8'); }
function linhas($texto) { return array_filter(array_map('trim', preg_split('/\R/u', $texto ?? ''))); }
?>
<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>MedTech Solutions</title><link rel="shortcut icon" href="assets/img/hospital125.png" type="image/png"><link rel="stylesheet" href="assets/fontawesome/all.min.css"><link rel="stylesheet" href="assets/css/estilos.css"></head><body>
<nav class="bng-navbar"><div class="nav-logo"><img src="assets/img/hospital255.png" alt="Logo MedTech Solutions"><h3>MedTech Solutions</h3></div><div class="container-navegacao"><a href="#quem-somos">Início</a><a href="#nossa-equipa">Equipa</a><a href="#servicos">Serviços</a><a href="#aula-grupo">Áreas</a><a href="#precario">Planos</a><a href="#contacto">Contacto</a></div><div class="nav-cliente"><a href="<?php echo BASE_URL; ?>/public/login.php" class="botao-area-restrita"><i class="fa-solid fa-lock"></i>Área Restrita</a></div></nav>
<section class="container-texto-generico" id="quem-somos"><div class="quem-somos-content"><div class="quem-somos-texto"><h1><?= h($conteudos['hero_titulo']) ?></h1><p><?= h($conteudos['hero_texto']) ?></p><a href="#contacto" class="button"><i class="fa-solid fa-calendar-check"></i><?= h($conteudos['hero_botao']) ?></a></div><img src="assets/img/imagem_public.png" alt="Hospital MedTech Solutions"></div></section>
<section id="nossa-equipa"><h2>A Nossa Equipa</h2><div class="equipa-container"><?php foreach (($itens['equipa'] ?? []) as $pessoa) : ?><div class="pessoa"><img src="assets/img/<?= h($pessoa['imagem']) ?>" alt="<?= h($pessoa['subtitulo']) ?>"><h3><?= h($pessoa['titulo']) ?></h3><p><?= h($pessoa['subtitulo']) ?></p></div><?php endforeach; ?></div></section>
<section id="servicos"><h2>Os Nossos Serviços</h2><div class="servicos-container servicos-grid"><?php foreach (($itens['servicos'] ?? []) as $servico) : ?><div class="servico"><i class="<?= h($servico['icone']) ?> fa-3x"></i><h3><?= h($servico['titulo']) ?></h3><p><?= h($servico['descricao']) ?></p></div><?php endforeach; ?></div></section>
<section id="aula-grupo"><h2>Áreas de Atuação</h2><div class="servicos-container areas-grid"><?php foreach (($itens['areas'] ?? []) as $area) : ?><div class="servico"><i class="<?= h($area['icone']) ?> fa-3x"></i><h3><?= h($area['titulo']) ?></h3><p><?= h($area['descricao']) ?></p></div><?php endforeach; ?></div></section>
<section id="precario"><h2>Planos de Serviço</h2><div class="pacotes-container"><?php foreach (($itens['planos'] ?? []) as $plano) : ?><div class="pacote"><h3><?= h($plano['titulo']) ?></h3><p class="preco"><?= h($plano['preco']) ?></p><ul><?php foreach (linhas($plano['descricao']) as $linha) : ?><li><?= h($linha) ?></li><?php endforeach; ?></ul><a href="#contacto" class="button">Solicitar</a></div><?php endforeach; ?></div></section>
<section id="contacto"><h2>Contacto</h2><p><?= h($conteudos['contacto_intro']) ?></p><form id="contactForm"><label for="nome">Nome:</label><input type="text" id="nome" name="nome" required><label for="email">Email:</label><input type="email" id="email" name="email" required><label for="mensagem">Mensagem:</label><textarea id="mensagem" name="mensagem" rows="4" required></textarea><button type="submit"><i class="fa-solid fa-paper-plane"></i>Enviar Mensagem</button></form></section>
<footer class="footer-container"><div class="footer-section"><strong>LOCALIZAÇÃO</strong><p><?= h($conteudos['footer_localizacao']) ?></p></div><div class="footer-section"><strong>HORÁRIO</strong><p><?= h($conteudos['footer_horario']) ?></p></div><div class="footer-section"><strong>CONTACTOS</strong><p>Email: <?= h($conteudos['footer_email']) ?></p><p>Telefone: <?= h($conteudos['footer_telefone']) ?></p></div></footer>
</body></html>
