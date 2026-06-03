<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedTech Solutions</title>

    <!-- favicon -->
    <link rel="shortcut icon" href="assets/img/hospital125.png" type="image/png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Titillium+Web:ital,wght@0,300;0,700;1,400&display=swap"
        rel="stylesheet">

    <!-- Font Awesome (local) -->
    <link rel="stylesheet" href="assets/fontawesome/all.min.css">

    <!-- estilos -->
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>

<body>

    <!-- Navegação -->
    <nav class="bng-navbar">
        <div class="nav-logo">
            <img src="assets/img/hospital255.png" alt="Logo MedTech Solutions">
            <h3>MedTech Solutions</h3>
        </div>

        <div class="container-navegacao">
            <a href="#quem-somos">Início</a>
            <a href="#nossa-equipa">Equipa</a>
            <a href="#servicos">Serviços</a>
            <a href="#aula-grupo">Áreas</a>
            <a href="#precario">Planos</a>
            <a href="#contacto">Contacto</a>
        </div>

        <div class="nav-cliente">
            <a href="../backend/index.html">Área Restrita</a>
        </div>
    </nav>

    <!-- Quem Somos / Hero -->
    <section class="container-texto-generico" id="quem-somos">
        <div class="quem-somos-content">
            <h1>Inovação e Controlo para a Tecnologia Hospitalar</h1>
            <p>
                A MedTech Solutions disponibiliza uma plataforma para registo, localização,
                documentação e acompanhamento dos equipamentos médicos ao longo do seu ciclo de vida.
            </p>
            <img src="assets/img/hospital_banner.jpg" alt="Imagem institucional da MedTech Solutions">
            <a href="#contacto" class="button">Pedir Demonstração</a>
        </div>
    </section>

    <!-- Nossa Equipa -->
    <section id="nossa-equipa">
        <h2>A Nossa Equipa</h2>
        <div class="equipa-container">
            <div class="pessoa">
                <img src="assets/img/equipa3.jpg" alt="Engenheiro Biomédico">
                <h3>Leonardo Maia</h3>
                <p>Engenheiro Biomédico</p>
            </div>

            <div class="pessoa">
                <img src="assets/img/equipa2.jpg" alt="Gestora de Projeto">
                <h3>Olívia Mendonça</h3>
                <p>Gestora de Projeto</p>
            </div>

            <div class="pessoa">
                <img src="assets/img/equipa1.jpg" alt="Especialista em Sistemas">
                <h3>Benedita Brandão</h3>
                <p>Especialista em Sistemas de Informação</p>
            </div>
        </div>
    </section>

    <!-- Serviços -->
    <section id="servicos">
        <h2>Os Nossos Serviços</h2>
        <div class="servicos-container">
            <div class="servico">
                <i class="fa-solid fa-laptop-medical fa-3x"></i>
                <h3>Gestão de Equipamentos</h3>
                <p>Registo centralizado de equipamentos médicos com identificação e estado operacional.</p>
            </div>

            <div class="servico">
                <i class="fa-solid fa-location-dot fa-3x"></i>
                <h3>Rastreabilidade</h3>
                <p>Organização dos equipamentos por edifício, piso, serviço e sala.</p>
            </div>

            <div class="servico">
                <i class="fa-solid fa-file-medical fa-3x"></i>
                <h3>Documentação Técnica</h3>
                <p>Consulta de manuais, certificados, garantias e contratos associados.</p>
            </div>

            <div class="servico">
                <i class="fa-solid fa-truck-medical fa-3x"></i>
                <h3>Fornecedores</h3>
                <p>Gestão de fabricantes, distribuidores e empresas de assistência técnica.</p>
            </div>

            <div class="servico">
                <i class="fa-solid fa-chart-column fa-3x"></i>
                <h3>Dashboard</h3>
                <p>Visualização de indicadores e estatísticas relevantes para apoio à decisão.</p>
            </div>
        </div>
    </section>

    <!-- Áreas -->
    <section id="aula-grupo">
        <h2>Áreas de Atuação</h2>
        <div class="servicos-container">
            <div class="servico">
                <i class="fa-solid fa-heart-pulse fa-3x"></i>
                <h3>Monitorização</h3>
                <p>Gestão de equipamentos dedicados à monitorização de sinais vitais.</p>
            </div>

            <div class="servico">
                <i class="fa-solid fa-lungs fa-3x"></i>
                <h3>Suporte de Vida</h3>
                <p>Controlo de ventiladores, desfibrilhadores e outros equipamentos críticos.</p>
            </div>

            <div class="servico">
                <i class="fa-solid fa-syringe fa-3x"></i>
                <h3>Terapia</h3>
                <p>Organização de bombas de infusão e outros dispositivos terapêuticos.</p>
            </div>

            <div class="servico">
                <i class="fa-solid fa-stethoscope fa-3x"></i>
                <h3>Diagnóstico</h3>
                <p>Registo e consulta de equipamentos usados em apoio ao diagnóstico clínico.</p>
            </div>

            <div class="servico">
                <i class="fa-solid fa-flask-vial fa-3x"></i>
                <h3>Laboratório</h3>
                <p>Inventário de equipamentos laboratoriais e respetiva documentação.</p>
            </div>
        </div>
    </section>

    <!-- Planos -->
    <section id="precario">
        <h2>Planos de Serviço</h2>
        <div class="pacotes-container">
            <div class="pacote">
                <h3>Plano Base</h3>
                <p class="preco">€50/mês</p>
                <ul>
                    <li>Registo de equipamentos</li>
                    <li>Consulta por localização</li>
                </ul>
                <a href="#contacto" class="button">Solicitar</a>
            </div>

            <div class="pacote">
                <h3>Plano Profissional</h3>
                <p class="preco">€80/mês</p>
                <ul>
                    <li>Gestão de fornecedores</li>
                    <li>Documentação técnica associada</li>
                    <li>Filtros avançados</li>
                </ul>
                <a href="#contacto" class="button">Solicitar</a>
            </div>

            <div class="pacote">
                <h3>Plano Institucional</h3>
                <p class="preco">€120/mês</p>
                <ul>
                    <li>Dashboard completo</li>
                    <li>Gestão documental avançada</li>
                    <li>Maior capacidade de registo</li>
                    <li>Suporte prioritário</li>
                </ul>
                <a href="#contacto" class="button">Solicitar</a>
            </div>
        </div>
    </section>

    <!-- Contacto -->
    <section id="contacto">
        <h2>Contacto</h2>
        <p>Entre em contacto connosco para pedir uma demonstração ou esclarecer dúvidas.</p>

        <form id="contactForm">
            <label for="nome">Nome:</label>
            <input type="text" id="nome" name="nome" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="mensagem">Mensagem:</label>
            <textarea id="mensagem" name="mensagem" rows="4" required></textarea>

            <button type="submit">Enviar Mensagem</button>
        </form>
    </section>

    <!-- Rodapé -->
    <footer class="footer-container">
        <div class="footer-section">
            <strong>LOCALIZAÇÃO</strong>
            <p>Porto, Portugal</p>
        </div>

        <div class="footer-section">
            <strong>HORÁRIO</strong>
            <p>Segunda a Sexta: 09h - 18h</p>
        </div>

        <div class="footer-section">
            <strong>CONTACTOS</strong>
            <p>Email: geral@medtechsolutions.pt</p>
            <p>Telefone: +351 220 000 000</p>
        </div>
    </footer>

</body>

</html>