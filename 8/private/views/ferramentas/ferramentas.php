<?php

require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<style>
    /*
        Título e subtítulo da página de ferramentas.
        Mantém o mesmo estilo visual usado na dashboard.
    */
    .ferramentas-title {
        font-weight: 700;
        margin-bottom: 0;
    }

    .ferramentas-subtitle {
        color: #6c757d;
        margin-bottom: 24px;
    }

    /*
        Link que envolve cada cartão.
        O display:block permite que o cartão inteiro seja clicável.
    */
    .tool-card {
        display: block;
        height: 100%;
        text-decoration: none;
    }

    /*
        Cartão principal de cada ferramenta.
        Usa gradientes, cantos arredondados e sombra para manter
        coerência com o visual da dashboard.
    */
    .tool-box {
        border: none;
        border-radius: 18px;
        padding: 24px;
        min-height: 165px;
        color: #fff;
        box-shadow: 0 8px 22px rgba(0, 0, 0, 0.10);
        transition: all 0.2s ease-in-out;

        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;

        position: relative;
        overflow: hidden;
    }

    /*
        Efeito visual ao passar o rato por cima do cartão.
    */
    .tool-box:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.16);
    }

    .tool-box i {
        font-size: 2.1rem;
        opacity: 0.9;
        margin-bottom: 18px;
    }

    .tool-box h5 {
        font-weight: 700;
        margin-bottom: 8px;
        text-align: center;
    }

    .tool-box p {
        margin-bottom: 0;
        font-size: 0.9rem;
        opacity: 0.9;
        text-align: center;
    }

    /*
        Cores dos cartões.
        Cada ferramenta tem uma cor distinta para facilitar a identificação.
    */
    .tool-dark {
        background: linear-gradient(135deg, #1f2937, #111827);
    }

    .tool-green {
        background: linear-gradient(135deg, #198754, #0f5132);
    }

    .tool-orange {
        background: linear-gradient(135deg, #f59f00, #d9480f);
    }

    .tool-blue {
        background: linear-gradient(135deg, #0d6efd, #084298);
    }

    .tool-purple {
        background: linear-gradient(135deg, #6f42c1, #3d0a91);
    }

    .tool-red {
        background: linear-gradient(135deg, #dc3545, #842029);
    }

    /*
        Área dos cartões.
        O justify-content-center garante que os cartões ficam centrados
        mesmo quando o número de cartões não preenche a linha toda.
    */
    .tools-wrapper {
        justify-content: center;
    }
</style>

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 p-4">

            <section class="mb-4">
                <h2 class="ferramentas-title">
                    <i class="fa-solid fa-screwdriver-wrench me-2"></i>
                    Ferramentas
                </h2>

                <p class="ferramentas-subtitle">
                    Centro de apoio à gestão técnica dos equipamentos hospitalares.
                </p>
            </section>

            <!--
                Cartões das ferramentas técnicas.
                Cada cartão encaminha para uma funcionalidade específica.
            -->
            <div class="row g-3 tools-wrapper">

                <div class="col-md-4">
                    <a href="proxima-manutencao.php" class="tool-card">
                        <div class="tool-box tool-blue">
                            <i class="fa-solid fa-calendar-check"></i>
                            <h5>Próximas Manutenções</h5>
                            <p>Consulta rápida das manutenções previstas.</p>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="avaliacao-tecnica.php" class="tool-card">
                        <div class="tool-box tool-green">
                            <i class="fa-solid fa-stethoscope"></i>
                            <h5>Avaliação Técnica</h5>
                            <p>Apoio à avaliação do estado técnico dos equipamentos.</p>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="estimativa-custo.php" class="tool-card">
                        <div class="tool-box tool-orange">
                            <i class="fa-solid fa-euro-sign"></i>
                            <h5>Estimativa de Custo</h5>
                            <p>Resumo dos custos associados às intervenções.</p>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="garantias-contratos.php" class="tool-card">
                        <div class="tool-box tool-purple">
                            <i class="fa-solid fa-file-signature"></i>
                            <h5>Garantias e Contratos</h5>
                            <p>Consulta de garantias, contratos e datas de validade.</p>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="historico.php" class="tool-card">
                        <div class="tool-box tool-dark">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            <h5>Histórico de Movimentações</h5>
                            <p>Consulta das transferências de equipamentos entre serviços.</p>
                        </div>
                    </a>
                </div>

                <div class="col-md-4">
                    <a href="emprestimos.php" class="tool-card">
                        <div class="tool-box tool-red">
                            <i class="fa-solid fa-handshake"></i>
                            <h5>Empréstimos entre Serviços</h5>
                            <p>Controlo de equipamentos emprestados e devoluções.</p>
                        </div>
                    </a>
                </div>

            </div>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>