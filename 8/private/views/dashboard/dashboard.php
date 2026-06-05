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

    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST .
            ";dbname=" . MYSQL_DATABASE .
            ";charset=utf8",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );

    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $total_equipamentos = $ligacao->query("SELECT COUNT(*) FROM equipamentos")->fetchColumn();

    $total_ativos = $ligacao->query("SELECT COUNT(*) FROM equipamentos WHERE estado = 'Ativo'")->fetchColumn();

    $total_manutencao = $ligacao->query("SELECT COUNT(*) FROM equipamentos WHERE estado = 'Em manutenção'")->fetchColumn();

    $total_inativos = $ligacao->query("SELECT COUNT(*) FROM equipamentos WHERE estado = 'Inativo'")->fetchColumn();

    $total_suporte_vida = $ligacao->query("SELECT COUNT(*) FROM equipamentos WHERE criticidade = 'Suporte de vida'")->fetchColumn();

    $total_sem_documentacao = $ligacao->query(
        "SELECT COUNT(*)
         FROM equipamentos e
         LEFT JOIN documentacao d ON e.id = d.equipamento_id
         WHERE d.id IS NULL"
    )->fetchColumn();

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

    $equipamentos_por_criticidade = $ligacao->query(
        "SELECT criticidade, COUNT(*) AS total
         FROM equipamentos
         GROUP BY criticidade
         ORDER BY total DESC"
    )->fetchAll(PDO::FETCH_OBJ);

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
         LIMIT 4"
    )->fetchAll(PDO::FETCH_OBJ);

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
     LIMIT 4"
    )->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $err) {

    $erro = 'Aconteceu um erro ao carregar os indicadores do dashboard.';
}

$ligacao = null;

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
    .dashboard-title {
        font-weight: 700;
        margin-bottom: 0;
    }

    .dashboard-subtitle {
        color: #6c757d;
        margin-bottom: 16px;
    }

    .kpi-card {
        border: none;
        border-radius: 18px;
        padding: 16px;
        min-height: 118px;
        color: #fff;
        box-shadow: 0 8px 22px rgba(0, 0, 0, 0.10);
    }

    .kpi-card h6 {
        font-size: 0.8rem;
        opacity: 0.9;
        margin-bottom: 8px;
    }

    .kpi-number {
        font-size: 2rem;
        font-weight: 700;
        line-height: 1;
    }

    .kpi-icon {
        font-size: 1.7rem;
        opacity: 0.75;
    }

    .kpi-dark {
        background: linear-gradient(135deg, #1f2937, #111827);
    }

    .kpi-green {
        background: linear-gradient(135deg, #198754, #0f5132);
    }

    .kpi-orange {
        background: linear-gradient(135deg, #f59f00, #d9480f);
    }

    .kpi-blue {
        background: linear-gradient(135deg, #0d6efd, #084298);
    }

    .kpi-red {
        background: linear-gradient(135deg, #dc3545, #842029);
    }

    .kpi-purple {
        background: linear-gradient(135deg, #6f42c1, #3d0a91);
    }

    .dashboard-box {
        border: none;
        border-radius: 18px;
        padding: 16px;
        background: #fff;
        box-shadow: 0 8px 22px rgba(0, 0, 0, 0.08);
        height: 100%;
    }

    .dashboard-box h5 {
        font-weight: 700;
        margin-bottom: 10px;
        font-size: 1rem;
    }

    .alert-item {
        border-left: 4px solid #dc3545;
        background: #fff5f5;
        padding: 8px 10px;
        border-radius: 8px;
        margin-bottom: 8px;
        font-size: 0.88rem;
    }

    .maintenance-item {
        border-left: 4px solid #0d6efd;
        background: #f4f8ff;
        padding: 8px 10px;
        border-radius: 8px;
        margin-bottom: 8px;
        font-size: 0.88rem;
    }

    .mini-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 6px;
        font-size: 0.88rem;
    }

    .mini-bar {
        height: 7px;
        background: #e9ecef;
        border-radius: 20px;
        overflow: hidden;
        margin-bottom: 9px;
    }

    .mini-bar-fill {
        height: 100%;
        background: #0d6efd;
        border-radius: 20px;
    }

    .compact-table td,
    .compact-table th {
        padding: 6px;
        font-size: 0.88rem;
    }
</style>

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 p-4">

            <div class="mb-2">
                <h2 class="dashboard-title">
                    <i class="fas fa-chart-line me-2"></i>Dashboard
                </h2>

                <p class="dashboard-subtitle">
                    Visão rápida do parque tecnológico hospitalar.
                </p>
            </div>

            <?php if (!empty($erro)) : ?>
                <div class="mensagem-erro">
                    <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <div class="row g-3 mb-3">

                <div class="col-md-2">
                    <div class="kpi-card kpi-dark">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>Total</h6>
                                <div class="kpi-number"><?= $total_equipamentos ?></div>
                            </div>
                            <i class="fa-solid fa-cogs kpi-icon"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="kpi-card kpi-green">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>Ativos</h6>
                                <div class="kpi-number"><?= $total_ativos ?></div>
                            </div>
                            <i class="fa-solid fa-circle-check kpi-icon"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="kpi-card kpi-orange">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>Manutenção</h6>
                                <div class="kpi-number"><?= $total_manutencao ?></div>
                            </div>
                            <i class="fa-solid fa-screwdriver-wrench kpi-icon"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="kpi-card kpi-blue">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>Inativos</h6>
                                <div class="kpi-number"><?= $total_inativos ?></div>
                            </div>
                            <i class="fa-solid fa-pause kpi-icon"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="kpi-card kpi-red">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>Garantias Exp.</h6>
                                <div class="kpi-number"><?= $total_garantias_expiradas ?></div>
                            </div>
                            <i class="fa-solid fa-shield-halved kpi-icon"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="kpi-card kpi-purple">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>Sem Docs</h6>
                                <div class="kpi-number"><?= $total_sem_documentacao ?></div>
                            </div>
                            <i class="fa-solid fa-file-circle-xmark kpi-icon"></i>
                        </div>
                    </div>
                </div>

            </div>

            <div class="row g-3 mb-3">

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

                        <div class="alert-item">
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
                                        <?= htmlspecialchars($manutencao->proxima_manutencao) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>

                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <div class="row g-3">

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
                            <i class="fa-solid fa-location-dot text-success me-2"></i>
                            Equipamentos por Serviço
                        </h5>

                        <table class="table table-bordered table-hover compact-table mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Serviço</th>
                                    <th>Total</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($equipamentos_por_servico as $linha) : ?>
                                    <tr>
                                        <td><?= htmlspecialchars($linha->servico) ?></td>
                                        <td><?= $linha->total ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    </div>
                </div>

                <div class="col-md-4">
                    <div class="dashboard-box">
                        <h5>
                            <i class="fa-solid fa-heart-pulse text-danger me-2"></i>
                            Estado Operacional
                        </h5>

                        <div class="mini-row">
                            <span>Ativos</span>
                            <strong><?= $total_ativos ?></strong>
                        </div>
                        <div class="mini-bar">
                            <div class="mini-bar-fill" style="width: <?= percentagem($total_ativos, $total_equipamentos) ?>%; background:#198754;"></div>
                        </div>

                        <div class="mini-row">
                            <span>Em manutenção</span>
                            <strong><?= $total_manutencao ?></strong>
                        </div>
                        <div class="mini-bar">
                            <div class="mini-bar-fill" style="width: <?= percentagem($total_manutencao, $total_equipamentos) ?>%; background:#f59f00;"></div>
                        </div>

                        <div class="mini-row">
                            <span>Inativos</span>
                            <strong><?= $total_inativos ?></strong>
                        </div>
                        <div class="mini-bar">
                            <div class="mini-bar-fill" style="width: <?= percentagem($total_inativos, $total_equipamentos) ?>%; background:#0d6efd;"></div>
                        </div>

                        <div class="mini-row">
                            <span>Suporte de vida</span>
                            <strong><?= $total_suporte_vida ?></strong>
                        </div>
                        <div class="mini-bar">
                            <div class="mini-bar-fill" style="width: <?= percentagem($total_suporte_vida, $total_equipamentos) ?>%; background:#dc3545;"></div>
                        </div>

                    </div>
                </div>

            </div>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>