MedTech Solutions - Sistema de Gestao de Inventario Hospitalar

Projeto desenvolvido por: Diana Carvalho
Numero de estudante: 1232099
Curso: Sistemas de Informacao para a Saude

1. Descricao do projeto

A MedTech Solutions e uma aplicacao web para apoio a gestao de equipamentos medicos em contexto hospitalar.
A plataforma permite registar, consultar e acompanhar equipamentos, localizacoes, fornecedores, utilizadores, documentacao tecnica, garantias, manutencoes, movimentacoes e emprestimos.

O projeto inclui uma area publica de apresentacao da plataforma e uma area privada destinada a administradores e tecnicos.

2. Tecnologias utilizadas

- PHP
- MySQL
- HTML
- CSS
- JavaScript
- Bootstrap
- Font Awesome
- Laragon / Apache / MySQL

3. Instalacao e configuracao

1. Colocar a pasta do projeto em:
   C:\laragon\www\projeto-hospital\8

2. Iniciar o Laragon e garantir que os servicos Apache e MySQL estao ativos.

3. Importar a base de dados atraves do ficheiro:
   database\base_dados_medtech.sql

4. Confirmar os dados de ligacao a base de dados no ficheiro:
   config\config.php

5. Abrir a pagina publica no browser:
   http://localhost/PROJETO-HOSPITAL/8/public/index.php

6. Abrir a area privada no browser:
   http://localhost/PROJETO-HOSPITAL/8/private/index.php

4. Credenciais de acesso

Administrador principal:
Email: admin@medtech.pt
Password: admin123

Tecnico:
Email: tecnico@medtech.pt
Password: tecnico123

Outros utilizadores de teste:
Email: ana.silva@medtech.pt
Password: ana123

Email: mariana.costa@medtech.pt
Password: mariana123

Email: joao.pereira@medtech.pt
Password: joao123

Email: ines.carvalho@medtech.pt
Password: ines123

As passwords estao guardadas na base de dados de forma segura, usando password_hash() e validadas com password_verify().

5. Funcionalidades principais

Area publica:
- Pagina inicial de apresentacao da plataforma
- Secao Sobre Nos
- Equipa
- Servicos
- Areas de atuacao
- Planos de servico
- Perguntas frequentes
- Contacto, redes sociais e localizacao
- Ligacao para a area restrita

Area privada:
- Login e logout
- Dashboard com calendario operacional
- Notificacoes diarias
- Listagem, pesquisa e filtros de equipamentos
- Consulta detalhada de equipamentos
- Registo e edicao de equipamentos
- Desativacao de equipamentos
- Gestao de localizacoes
- Gestao de fornecedores
- Gestao de utilizadores
- Alteracao de password
- Gestao de conteudos da pagina publica
- Documentacao tecnica e manuais por tipo de equipamento
- Garantias e contratos
- Manutencoes
- Movimentacoes
- Emprestimos
- Exportacao de dados para Excel/CSV

6. Testes recomendados

Para testar o projeto, recomenda-se verificar:

- Entrada na area privada com as credenciais indicadas
- Navegacao entre Dashboard, Equipamentos, Localizacoes, Fornecedores, Utilizadores e Conteudos Publicos
- Pesquisa e filtragem nas listagens
- Consulta da ficha de um equipamento
- Abertura de manuais, fichas tecnicas, garantias e contratos
- Registo de nova manutencao
- Registo de nova movimentacao
- Consulta do calendario operacional da dashboard
- Edicao dos conteudos publicos e visualizacao na pagina publica
- Alteracao de password de utilizador
- Exportacao de listagens

7. Observacoes

O projeto foi preparado para funcionar localmente em Laragon.
Caso seja colocado noutra pasta ou endereco, pode ser necessario ajustar a constante BASE_URL no ficheiro config\config.php.

Para a demonstracao, recomenda-se garantir previamente que:
- O Laragon esta ligado
- A base de dados foi importada
- O Apache e o MySQL estao ativos
- O projeto esta acessivel atraves do browser
