<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$erro = '';

$total_equipamentos = 0;
$total_ativos = 0;
$total_manutencao = 0;
$total_inativos = 0;
$total_sem_documentacao = 0;
$total_garantias_expiradas = 0;
$total_garantias_30_dias = 0;
$total_suporte_vida = 0;
$total_contratos_ativos = 0;

$equipamentos_por_criticidade = [];
$equipamentos_por_servico = [];
$garantias_a_expirar = [];
$proximas_manutencoes = [];

try {

    /*
        Ligação à base de dados.
        A Dashboard centraliza indicadores gerais do sistema.
    */
    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST .
            ";dbname=" . MYSQL_DATABASE .
            ";charset=utf8",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );

    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    /*
        Indicadores principais dos equipamentos.
    */
    $total_equipamentos = $ligacao->query("SELECT COUNT(*) FROM equipamentos")->fetchColumn();
    $total_ativos = $ligacao->query("SELECT COUNT(*) FROM equipamentos WHERE estado = 'Ativo'")->fetchColumn();
    $total_manutencao = $ligacao->query("SELECT COUNT(*) FROM equipamentos WHERE estado = 'Em manutenção'")->fetchColumn();
    $total_inativos = $ligacao->query("SELECT COUNT(*) FROM equipamentos WHERE estado = 'Inativo'")->fetchColumn();
    $total_suporte_vida = $ligacao->query("SELECT COUNT(*) FROM equipamentos WHERE criticidade = 'Suporte de vida'")->fetchColumn();

    /*
        Equipamentos sem documentação associada.
        Este indicador ajuda a controlar falhas documentais.
    */
    $total_sem_documentacao = $ligacao->query(
        "SELECT COUNT(*)
         FROM equipamentos e
         LEFT JOIN documentacao d ON e.id = d.equipamento_id
         WHERE d.id IS NULL"
    )->fetchColumn();

    /*
        Indicadores de garantias e contratos.
    */
    $total_garantias_expiradas = $ligacao->query(
        "SELECT COUNT(*)
         FROM garantias_contratos
         WHERE data_fim IS NOT NULL
         AND data_fim < CURDATE()"
    )->fetchColumn();

    $total_garantias_30_dias = $ligacao->query(
        "SELECT COUNT(*)
         FROM garantias_contratos
         WHERE data_fim IS NOT NULL
         AND data_fim BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)"
    )->fetchColumn();

    $total_contratos_ativos = $ligacao->query(
        "SELECT COUNT(*)
         FROM garantias_contratos
         WHERE existe_contrato = 1
         AND (data_fim IS NULL OR data_fim >= CURDATE())"
    )->fetchColumn();

    /*
        Distribuição dos equipamentos por criticidade.
    */
    $equipamentos_por_criticidade = $ligacao->query(
        "SELECT criticidade, COUNT(*) AS total
         FROM equipamentos
         GROUP BY criticidade
         ORDER BY total DESC"
    )->fetchAll(PDO::FETCH_OBJ);

    /*
        Top 5 serviços com mais equipamentos.
    */
    $equipamentos_por_servico = $ligacao->query(
        "SELECT 
            COALESCE(l.servico, 'Sem localização') AS servico,
            COUNT(e.id) AS total
         FROM equipamentos e
         LEFT JOIN localizacoes l ON e.localizacao_id = l.id
         GROUP BY l.servico
         ORDER BY total DESC
         LIMIT 5"
    )->fetchAll(PDO::FETCH_OBJ);

    /*
        Garantias a terminar nos próximos 30 dias.
    */
    $garantias_a_expirar = $ligacao->query(
        "SELECT 
            gc.tipo_contrato,
            gc.data_fim,
            gc.entidade_responsavel,
            e.codigo_inventario,
            e.designacao,
            DATEDIFF(gc.data_fim, CURDATE()) AS dias
         FROM garantias_contratos gc
         INNER JOIN equipamentos e ON gc.equipamento_id = e.id
         WHERE gc.data_fim IS NOT NULL
         AND gc.data_fim BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
         ORDER BY gc.data_fim ASC
         LIMIT 3"
    )->fetchAll(PDO::FETCH_OBJ);

    /*
        Próximas manutenções a partir da data atual.
    */
    $proximas_manutencoes = $ligacao->query(
        "SELECT 
            m.tipo_manutencao,
            m.proxima_manutencao,
            m.responsavel,
            e.codigo_inventario,
            e.designacao,
            f.nome_empresa
         FROM manutencoes m
         INNER JOIN equipamentos e ON m.equipamento_id = e.id
         LEFT JOIN fornecedores f ON m.fornecedor_id = f.id
         WHERE m.proxima_manutencao IS NOT NULL
         AND m.proxima_manutencao >= CURDATE()
         ORDER BY m.proxima_manutencao ASC
         LIMIT 3"
    )->fetchAll(PDO::FETCH_OBJ);

} catch (PDOException $err) {

    $erro = 'Aconteceu um erro ao carregar os indicadores do dashboard.';
}

$ligacao = null;

/*
    Função auxiliar para calcular percentagens nas barras visuais.
*/
function percentagem($valor, $total)
{
    if ($total == 0) {
        return 0;
    }

    return round(($valor / $total) * 100);
}

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<style>
    /*
        Fundo limpo da Dashboard.
        Foi retirada a imagem para o conteúdo ficar mais leve e profissional.
    */
    .dashboard-page {
        min-height: 100vh;
        background: #f5f7fa;
        padding: 24px;
    }

    /*
        Títulos com peso moderado.
        Assim a página fica menos pesada visualmente.
    */
    .dashboard-title {
        font-weight: 600;
        color: #1E3A5F;
        margin-bottom: 0;
        font-size: 1.8rem;
    }

    .dashboard-subtitle {
        color: #64748b;
        margin-bottom: 14px;
        font-size: 0.95rem;
    }

    /*
        Cartões principais.
        Fundo branco e borda lateral colorida para contrastar
        com a sidebar e a navbar.
    */
    .kpi-card {
        border: 1px solid #e5e7eb;
        border-left: 5px solid #1E3A5F;
        border-radius: 16px;
        padding: 14px;
        min-height: 105px;
        background: #ffffff;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        height: 100%;
    }

    .kpi-total {
        border-left-color: #1E3A5F;
    }

    .kpi-ativos {
        border-left-color: #198754;
    }

    .kpi-manutencao {
        border-left-color: #fd7e14;
    }

    .kpi-inativos {
        border-left-color: #dc3545;
    }

    .kpi-garantias {
        border-left-color: #2F5D8A;
    }

    .kpi-docs {
        border-left-color: #6f42c1;
    }

    /*
        Círculo dos ícones dos cartões.
    */
    .kpi-icon-circle {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #eef6ff;
        color: #2F5D8A;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        margin-bottom: 8px;
    }

    .kpi-card h6 {
        color: #475569;
        font-size: 0.78rem;
        margin-bottom: 4px;
        font-weight: 500;
    }

    .kpi-number {
        color: #0f172a;
        font-size: 1.75rem;
        font-weight: 700;
        line-height: 1;
    }

    /*
        Caixas secundárias da Dashboard.
    */
    .dashboard-box {
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 14px;
        background: #ffffff;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        height: 100%;
    }

    .dashboard-box h5 {
        font-weight: 600;
        color: #1E3A5F;
        margin-bottom: 10px;
        font-size: 0.98rem;
    }

    /*
        Itens de alerta e manutenção.
    */
    .alert-item {
        border-left: 4px solid #dc3545;
        background: #fff5f5;
        padding: 7px 9px;
        border-radius: 9px;
        margin-bottom: 7px;
        font-size: 0.84rem;
    }

    .maintenance-item {
        border-left: 4px solid #2F5D8A;
        background: #f0f7ff;
        padding: 7px 9px;
        border-radius: 9px;
        margin-bottom: 7px;
        font-size: 0.84rem;
    }

    /*
        Barras visuais utilizadas nas distribuições.
    */
    .mini-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 4px;
        font-size: 0.84rem;
        color: #334155;
    }

    .mini-bar {
        height: 6px;
        background: #e5edf7;
        border-radius: 20px;
        overflow: hidden;
        margin-bottom: 8px;
    }

    .mini-bar-fill {
        height: 100%;
        background: #2F5D8A;
        border-radius: 20px;
    }

    /*
        Redução de espaços para evitar scroll excessivo.
    */
    @media (min-width: 992px) {
        .row-compact {
            row-gap: 12px;
        }
    }
</style>

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 dashboard-page">

            <div class="mb-2">
                <h2 class="dashboard-title">
                    <i class="fas fa-chart-line me-2"></i>Dashboard
                </h2>

                <p class="dashboard-subtitle">
                    Visão rápida do parque tecnológico hospitalar.
                </p>
            </div>

            <?php if (!empty($erro)) : ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <!-- Indicadores principais -->
            <div class="row g-3 mb-3 row-compact">

                <div class="col-md-2">
                    <div class="kpi-card kpi-total">
                        <div class="kpi-icon-circle">
                            <i class="fa-solid fa-cogs"></i>
                        </div>
                        <h6>TOTAL DE EQUIPAMENTOS</h6>
                        <div class="kpi-number"><?= $total_equipamentos ?></div>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="kpi-card kpi-ativos">
                        <div class="kpi-icon-circle">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <h6>ATIVOS</h6>
                        <div class="kpi-number"><?= $total_ativos ?></div>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="kpi-card kpi-manutencao">
                        <div class="kpi-icon-circle">
                            <i class="fa-solid fa-screwdriver-wrench"></i>
                        </div>
                        <h6>EM MANUTENÇÃO</h6>
                        <div class="kpi-number"><?= $total_manutencao ?></div>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="kpi-card kpi-inativos">
                        <div class="kpi-icon-circle">
                            <i class="fa-solid fa-pause"></i>
                        </div>
                        <h6>INATIVOS</h6>
                        <div class="kpi-number"><?= $total_inativos ?></div>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="kpi-card kpi-garantias">
                        <div class="kpi-icon-circle">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <h6>GARANTIAS EXPIRADAS</h6>
                        <div class="kpi-number"><?= $total_garantias_expiradas ?></div>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="kpi-card kpi-docs">
                        <div class="kpi-icon-circle">
                            <i class="fa-solid fa-file-circle-xmark"></i>
                        </div>
                        <h6>SEM DOCUMENTAÇÃO</h6>
                        <div class="kpi-number"><?= $total_sem_documentacao ?></div>
                    </div>
                </div>

            </div>

            <!-- Alertas e listas rápidas -->
            <div class="row g-3 mb-3 row-compact">

                <div class="col-md-4">
                    <div class="dashboard-box">
                        <h5>
                            <i class="fa-solid fa-triangle-exclamation text-danger me-2"></i>
                            Alertas
                        </h5>

                        <div class="alert-item">
                            <strong><?= $total_garantias_expiradas ?></strong>
                            garantia(s)/contrato(s) expirado(s)
                        </div>

                        <div class="alert-item">
                            <strong><?= $total_garantias_30_dias ?></strong>
                            garantia(s) a expirar nos próximos 30 dias
                        </div>

                        <div class="alert-item">
                            <strong><?= $total_sem_documentacao ?></strong>
                            equipamento(s) sem documentação associada
                        </div>

                        <div class="alert-item mb-0">
                            <strong><?= $total_contratos_ativos ?></strong>
                            contrato(s) ativo(s)
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="dashboard-box">
                        <h5>
                            <i class="fa-solid fa-clock text-warning me-2"></i>
                            Garantias a Expirar
                        </h5>

                        <?php if (count($garantias_a_expirar) == 0) : ?>

                            <p class="text-muted mb-0">Sem garantias a expirar nos próximos 30 dias.</p>

                        <?php else : ?>

                            <?php foreach ($garantias_a_expirar as $garantia) : ?>
                                <div class="alert-item">
                                    <strong><?= htmlspecialchars($garantia->tipo_contrato) ?></strong><br>
                                    <?= htmlspecialchars($garantia->codigo_inventario) ?>
                                    -
                                    <?= htmlspecialchars($garantia->designacao) ?><br>
                                    <span class="text-danger">
                                        Expira em <?= htmlspecialchars($garantia->dias) ?> dia(s)
                                    </span>
                                </div>
                            <?php endforeach; ?>

                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="dashboard-box">
                        <h5>
                            <i class="fa-solid fa-screwdriver-wrench text-primary me-2"></i>
                            Próximas Manutenções
                        </h5>

                        <?php if (count($proximas_manutencoes) == 0) : ?>

                            <p class="text-muted mb-0">Sem manutenções agendadas.</p>

                        <?php else : ?>

                            <?php foreach ($proximas_manutencoes as $manutencao) : ?>
                                <div class="maintenance-item">
                                    <strong><?= htmlspecialchars($manutencao->tipo_manutencao) ?></strong><br>
                                    <?= htmlspecialchars($manutencao->codigo_inventario) ?>
                                    -
                                    <?= htmlspecialchars($manutencao->designacao) ?><br>
                                    <span class="text-primary">
                                        <?= date('d/m/Y', strtotime($manutencao->proxima_manutencao)) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>

                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Indicadores analíticos -->
            <div class="row g-3 row-compact">

                <div class="col-md-4">
                    <div class="dashboard-box">
                        <h5>
                            <i class="fa-solid fa-layer-group text-primary me-2"></i>
                            Criticidade
                        </h5>

                        <?php foreach ($equipamentos_por_criticidade as $linha) : ?>

                            <?php $perc = percentagem($linha->total, $total_equipamentos); ?>

                            <div class="mini-row">
                                <span><?= htmlspecialchars($linha->criticidade) ?></span>
                                <strong><?= $linha->total ?></strong>
                            </div>

                            <div class="mini-bar">
                                <div class="mini-bar-fill" style="width: <?= $perc ?>%;"></div>
                            </div>

                        <?php endforeach; ?>

                    </div>
                </div>

                <div class="col-md-4">
                    <div class="dashboard-box">
                        <h5>
                            <i class="fa-solid fa-location-dot text-primary me-2"></i>
                            Equipamentos por Serviço
                        </h5>

                        <?php foreach ($equipamentos_por_servico as $linha) : ?>

                            <?php $perc = percentagem($linha->total, $total_equipamentos); ?>

                            <div class="mini-row">
                                <span><?= htmlspecialchars($linha->servico) ?></span>
                                <strong><?= $linha->total ?></strong>
                            </div>

                            <div class="mini-bar">
                                <div class="mini-bar-fill" style="width: <?= $perc ?>%;"></div>
                            </div>

                        <?php endforeach; ?>

                    </div>
                </div>

                <div class="col-md-4">
                    <div class="dashboard-box">
                        <h5>
                            <i class="fa-solid fa-heart-pulse text-primary me-2"></i>
                            Estado Operacional
                        </h5>

                        <div class="mini-row">
                            <span>Ativos</span>
                            <strong><?= $total_ativos ?></strong>
                        </div>
                        <div class="mini-bar">
                            <div class="mini-bar-fill" style="width: <?= percentagem($total_ativos, $total_equipamentos) ?>%;"></div>
                        </div>

                        <div class="mini-row">
                            <span>Em manutenção</span>
                            <strong><?= $total_manutencao ?></strong>
                        </div>
                        <div class="mini-bar">
                            <div class="mini-bar-fill" style="width: <?= percentagem($total_manutencao, $total_equipamentos) ?>%;"></div>
                        </div>

                        <div class="mini-row">
                            <span>Inativos</span>
                            <strong><?= $total_inativos ?></strong>
                        </div>
                        <div class="mini-bar">
                            <div class="mini-bar-fill" style="width: <?= percentagem($total_inativos, $total_equipamentos) ?>%;"></div>
                        </div>

                        <div class="mini-row">
                            <span>Suporte de vida</span>
                            <strong><?= $total_suporte_vida ?></strong>
                        </div>
                        <div class="mini-bar mb-0">
                            <div class="mini-bar-fill" style="width: <?= percentagem($total_suporte_vida, $total_equipamentos) ?>%;"></div>
                        </div>

                    </div>
                </div>

            </div>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>