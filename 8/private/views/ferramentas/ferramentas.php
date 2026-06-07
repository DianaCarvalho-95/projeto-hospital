<?php

require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<style>
    /* Fundo da página */
    .ferramentas-page {
        background: #f5f7fa;
        min-height: 100vh;
        padding: 24px;
    }

    /* Título principal */
    .page-title {
        font-weight: 600;
        color: #1E3A5F;
        font-size: 1.8rem;
        margin-bottom: 0;
    }

    /* Subtítulo da página */
    .page-subtitle {
        color: #64748b;
        font-size: 0.95rem;
        margin-bottom: 0;
    }

    /* Link que envolve cada cartão */
    .tool-card {
        display: block;
        height: 100%;
        text-decoration: none;
    }

    /* Cartão de cada ferramenta */
    .tool-box {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-left: 5px solid #2F5D8A;
        border-radius: 16px;
        padding: 22px;
        min-height: 155px;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        transition: all 0.2s ease-in-out;

        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        text-align: left;
    }

    /* Efeito ao passar o rato */
    .tool-box:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.10);
        border-left-color: #1E3A5F;
    }

    /* Ícone circular no topo do cartão */
    .tool-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: #edf4ff;
        color: #2F5D8A;

        display: flex;
        align-items: center;
        justify-content: center;

        font-size: 1.25rem;
        margin-bottom: 14px;
    }

    /* Título do cartão */
    .tool-box h5 {
        color: #1E3A5F;
        font-weight: 600;
        font-size: 1rem;
        margin-bottom: 8px;
        text-align: left;
    }

    /* Texto do cartão */
    .tool-box p {
        color: #64748b;
        margin-bottom: 0;
        font-size: 0.88rem;
        line-height: 1.35;
        text-align: left;
    }

    /* Alinhamento dos cartões */
    .tools-wrapper {
        justify-content: center;
    }
</style>

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 ferramentas-page">

            <section class="mb-4">

                <h2 class="page-title mb-1">
                    <i class="fa-solid fa-screwdriver-wrench me-2"></i>
                    Ferramentas
                </h2>

                <p class="page-subtitle">
                    Centro de apoio à gestão técnica dos equipamentos hospitalares.
                </p>

            </section>

            <div class="row g-3 tools-wrapper">

                <!-- Próxima Manutenção -->
                <div class="col-md-4">
                    <a href="proxima-manutencao.php" class="tool-card">
                        <div class="tool-box">

                            <div class="tool-icon">
                                <i class="fa-solid fa-calendar-check"></i>
                            </div>

                            <h5>Próximas Manutenções</h5>

                            <p>
                                Consulta rápida das manutenções previstas.
                            </p>

                        </div>
                    </a>
                </div>

                <!-- Avaliação Técnica -->
                <div class="col-md-4">
                    <a href="avaliacao-tecnica.php" class="tool-card">
                        <div class="tool-box">

                            <div class="tool-icon">
                                <i class="fa-solid fa-stethoscope"></i>
                            </div>

                            <h5>Avaliação Técnica</h5>

                            <p>
                                Apoio à avaliação do estado técnico dos equipamentos.
                            </p>

                        </div>
                    </a>
                </div>

                <!-- Estimativa/Custo -->
                <div class="col-md-4">
                    <a href="estimativa-custo.php" class="tool-card">
                        <div class="tool-box">

                            <div class="tool-icon">
                                <i class="fa-solid fa-euro-sign"></i>
                            </div>

                            <h5>Estimativa de Custo</h5>

                            <p>
                                Resumo dos custos associados às intervenções.
                            </p>

                        </div>
                    </a>
                </div>

                <!-- Garantias/Contratos -->
                <div class="col-md-4">
                    <a href="garantias-contratos.php" class="tool-card">
                        <div class="tool-box">

                            <div class="tool-icon">
                                <i class="fa-solid fa-file-signature"></i>
                            </div>

                            <h5>Garantias e Contratos</h5>

                            <p>
                                Consulta de garantias, contratos e datas de validade.
                            </p>

                        </div>
                    </a>
                </div>

                <!-- Histórico -->
                <div class="col-md-4">
                    <a href="historico.php" class="tool-card">
                        <div class="tool-box">

                            <div class="tool-icon">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </div>

                            <h5>Histórico de Movimentações</h5>

                            <p>
                                Consulta das transferências de equipamentos entre serviços.
                            </p>

                        </div>
                    </a>
                </div>

                <!-- Empréstimos -->
                <div class="col-md-4">
                    <a href="emprestimos.php" class="tool-card">
                        <div class="tool-box">

                            <div class="tool-icon">
                                <i class="fa-solid fa-handshake"></i>
                            </div>

                            <h5>Empréstimos entre Serviços</h5>

                            <p>
                                Controlo de equipamentos emprestados e devoluções.
                            </p>

                        </div>
                    </a>
                </div>

            </div>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>