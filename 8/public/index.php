<?php
require_once __DIR__ . '/../config/config.php';

$itens = [];
$conteudos = [
    'hero_titulo' => 'Inovação e controlo para a tecnologia hospitalar',
    'hero_texto' => 'A MedTech Solutions disponibiliza uma plataforma para registo, localização, documentação e acompanhamento dos equipamentos médicos ao longo do seu ciclo de vida.',
    'hero_botao' => 'Pedir demonstração',
    'contacto_intro' => 'Entre em contacto connosco para pedir uma demonstração ou esclarecer dúvidas.',
    'footer_localizacao' => 'Rua de Júlio Dinis, 728, 4050-012 Porto',
    'footer_horario' => 'Segunda a Sexta: 09h - 18h',
    'footer_email' => 'geral@medtechsolutions.pt',
    'footer_telefone' => '+351 222 258 053',
];

try {
    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST . ";port=" . MYSQL_PORT . ";dbname=" . MYSQL_DATABASE . ";charset=utf8mb4",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );
    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    foreach ($ligacao->query('SELECT chave, conteudo FROM conteudos_publicos') as $linha) {
        $conteudos[$linha['chave']] = $linha['conteudo'];
    }

    foreach ($ligacao->query('SELECT * FROM conteudos_publicos_itens WHERE ativo = 1 ORDER BY seccao, ordem') as $item) {
        $itens[$item['seccao']][] = $item;
    }
} catch (PDOException $err) {
    // Mantém os conteúdos por defeito se a base de dados não estiver disponível.
}

function h($valor)
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

function linhas($texto)
{
    return array_filter(array_map('trim', preg_split('/\R/u', $texto ?? '')));
}
function linhas_morada($texto)
{
    $texto = trim($texto ?? '');
    $texto = preg_replace('/,\s*(\d{4}-\d{3}\s+.+)$/u', "\n$1", $texto);
    return linhas($texto);
}

function linhas_horario($texto)
{
    $texto = trim($texto ?? '');
    $texto = preg_replace('/:\s*/u', "\n", $texto, 1);
    return linhas($texto);
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedTech Solutions</title>
    <link rel="shortcut icon" href="assets/img/hospital125.png" type="image/png">
    <link rel="stylesheet" href="assets/fontawesome/all.min.css">
    <link rel="stylesheet" href="assets/css/1232099.css?v=20260617-sem-barra-topo">
</head>
<body>
    <nav class="bng-navbar">
        <div class="nav-logo">
            <img src="assets/img/hospital255.png" alt="Logo MedTech Solutions">
            <span>MedTech Solutions</span>
        </div>

        <div class="container-navegacao">
            <a href="#quem-somos">Início</a>
            <a href="#sobre-nos">Sobre Nós</a>
            <a href="#nossa-equipa">Equipa</a>
            <a href="#servicos">Serviços</a>
            <a href="#aula-grupo">Áreas</a>
            <a href="#precario">Planos</a>
            <a href="#perguntas-frequentes">FAQ</a>
            <a href="#contacto">Contacto</a>
            <a href="#localizacao">Localização</a>
        </div>

        <div class="nav-cliente">
            <a href="<?php echo BASE_URL; ?>/public/login.php" class="botao-area-restrita">
                <i class="fa-solid fa-lock"></i>
                Área Restrita
            </a>
        </div>
    </nav>

    <main>
        <section class="hero-section" id="quem-somos">
            <div class="hero-content">
                <div class="hero-text">
                    <span class="hero-kicker">Gestão de equipamentos médicos</span>
                    <h1><?= h($conteudos['hero_titulo']) ?></h1>
                    <p><?= h($conteudos['hero_texto']) ?></p>
                    <div class="hero-actions">
                        <a href="#contacto" class="button">
                            <i class="fa-solid fa-calendar-check"></i>
                            <?= h($conteudos['hero_botao']) ?>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/public/login.php" class="button button-secondary">
                            <i class="fa-solid fa-right-to-bracket"></i>
                            Entrar na plataforma
                        </a>
                    </div>
                </div>
                <div class="hero-media">
                    <img src="assets/img/imagem_public_equipamentos.png" alt="Equipamentos médicos hospitalares">
                </div>
            </div>
        </section>

        <section id="sobre-nos">
            <div class="sobre-container">
                <h2>Sobre Nós</h2>
                <p>A MedTech Solutions apoia instituições hospitalares na organização do parque tecnológico, reunindo informação técnica, localização, documentação, garantias e acompanhamento operacional numa plataforma simples de consultar.</p>
                <div class="sobre-lista">
                    <span><i class="fa-solid fa-check"></i> Gestão centralizada</span>
                    <span><i class="fa-solid fa-check"></i> Acompanhamento técnico</span>
                    <span><i class="fa-solid fa-check"></i> Apoio à decisão</span>
                </div>
            </div>
        </section>

        <section id="nossa-equipa">
            <div class="section-heading">
                <span>Equipa</span>
                <h2>A Nossa Equipa</h2>
            </div>
            <div class="equipa-container">
                <?php foreach (($itens['equipa'] ?? []) as $pessoa) : ?>
                    <div class="pessoa">
                        <img src="assets/img/<?= h($pessoa['imagem']) ?>" alt="<?= h($pessoa['subtitulo']) ?>">
                        <h3><?= h($pessoa['titulo']) ?></h3>
                        <p><?= h($pessoa['subtitulo']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="servicos">
            <div class="section-heading">
                <span>Funcionalidades</span>
                <h2>Os Nossos Serviços</h2>
            </div>
            <div class="servicos-container servicos-equilibrados">
                <?php foreach (($itens['servicos'] ?? []) as $servico) : ?>
                    <div class="servico">
                        <i class="<?= h($servico['icone']) ?>"></i>
                        <h3><?= h($servico['titulo']) ?></h3>
                        <p><?= h($servico['descricao']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="aula-grupo">
            <div class="section-heading">
                <span>Inventário hospitalar</span>
                <h2>Áreas de Atuação</h2>
            </div>
            <div class="servicos-container areas-container">
                <?php foreach (($itens['areas'] ?? []) as $area) : ?>
                    <div class="servico">
                        <i class="<?= h($area['icone']) ?>"></i>
                        <h3><?= h($area['titulo']) ?></h3>
                        <p><?= h($area['descricao']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="precario">
            <div class="section-heading">
                <span>Planos</span>
                <h2>Planos de Serviço</h2>
            </div>
            <div class="pacotes-container">
                <?php foreach (($itens['planos'] ?? []) as $plano) : ?>
                    <div class="pacote">
                        <h3><?= h($plano['titulo']) ?></h3>
                        <p class="preco"><?= h($plano['preco']) ?></p>
                        <ul>
                            <?php foreach (linhas($plano['descricao']) as $linha) : ?>
                                <li><?= h($linha) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="#contacto" class="button">Solicitar</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="perguntas-frequentes">
            <div class="section-heading">
                <h2>Perguntas Frequentes</h2>
            </div>
            <div class="faq-container">
                <details>
                    <summary>Que tipo de equipamentos podem ser registados?</summary>
                    <p>Podem ser registados equipamentos médicos de diferentes categorias, incluindo monitorização, suporte de vida, diagnóstico, terapia e laboratório.</p>
                </details>
                <details>
                    <summary>A plataforma permite acompanhar manutenções?</summary>
                    <p>Sim. A área privada permite consultar o histórico, registar intervenções e acompanhar próximas manutenções planeadas.</p>
                </details>
                <details>
                    <summary>É possível associar documentos aos equipamentos?</summary>
                    <p>Sim. Cada equipamento pode ter ficha técnica, manual, garantias e contratos associados, facilitando a consulta documental.</p>
                </details>
                <details>
                    <summary>O sistema funciona sem internet?</summary>
                    <p>Sim, a demonstração local funciona no ambiente instalado no computador, desde que o servidor local e a base de dados estejam ativos.</p>
                </details>
            </div>
        </section>
        <section id="contacto">
            <div class="section-heading contacto-heading">
                <h2>Fale Connosco</h2>
                <p><?= h($conteudos['contacto_intro']) ?></p>
            </div>
            <form id="contactForm" class="form-contacto">
                <div class="form-grid">
                    <div>
                        <label for="nome">Nome</label>
                        <input type="text" id="nome" name="nome" required>
                    </div>
                    <div>
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                </div>

                <label for="mensagem">Mensagem</label>
                <textarea id="mensagem" name="mensagem" rows="4" required></textarea>

                <div class="form-actions">
                    <button type="submit">
                        <i class="fa-solid fa-paper-plane"></i>
                        Enviar mensagem
                    </button>
                </div>
            </form>
        </section>

        <section id="localizacao" class="localizacao-section">
            <div class="section-heading">
                <h2>Localização</h2>
            </div>
            <div class="mapa-localizacao">
                <div class="mapa-info">
                    <strong>MedTech Solutions</strong>
                    <p class="texto-linhas"><?php foreach (linhas_morada($conteudos['footer_localizacao']) as $linha) : ?><span><?= h($linha) ?></span><?php endforeach; ?></p>
                </div>
                <div class="mapa-foto-wrap mapa-google-wrap">
                    <iframe
                        class="mapa-google"
                        src="https://www.google.com/maps?q=<?= rawurlencode($conteudos['footer_localizacao']) ?>&output=embed"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        title="Mapa da morada da MedTech Solutions no Porto"></iframe>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer-container">
        <div class="footer-section">
            <strong>Horário</strong>
            <p class="texto-linhas"><?php foreach (linhas_horario($conteudos['footer_horario']) as $linha) : ?><span><?= h($linha) ?></span><?php endforeach; ?></p>
        </div>
        <div class="footer-section">
            <strong>Contactos</strong>
            <p>Email: <?= h($conteudos['footer_email']) ?></p>
            <p>Telefone: <?= h($conteudos['footer_telefone']) ?></p>
        </div>
        <div class="footer-section">
            <strong>Redes sociais</strong>
            <div class="social-links">
                <a href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer" aria-label="Instagram MedTech Solutions" title="Instagram">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="3" y="3" width="18" height="18" rx="5"></rect>
                        <circle cx="12" cy="12" r="4"></circle>
                        <circle cx="17.5" cy="6.5" r="1"></circle>
                    </svg>
                </a>
                <a href="https://www.linkedin.com/" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn MedTech Solutions" title="LinkedIn">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M6.8 9.4v9.1"></path>
                        <path d="M6.8 6.2v.1"></path>
                        <path d="M11 18.5V9.4"></path>
                        <path d="M11 13.2c0-2.4 1.4-3.9 3.5-3.9 2 0 3.1 1.3 3.1 3.7v5.5"></path>
                    </svg>
                </a>
            </div>
        </div>
    </footer>
</body>
</html>




















