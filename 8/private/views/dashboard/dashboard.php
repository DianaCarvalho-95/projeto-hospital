<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$erro = '';
$hoje = date('Y-m-d');
$fim_periodo = max(date('Y-m-d', strtotime('+14 days')), date('Y-m-t'));
$inicio_mes = date('Y-m-01');
$dias_mes = (int) date('t');
$primeiro_dia_semana_mes = (int) date('N', strtotime($inicio_mes));

$resumo = [
    'total' => 0,
    'ativos' => 0,
    'manutencao' => 0,
    'criticos' => 0,
    'sem_documentacao' => 0,
    'garantias_expiradas' => 0,
    'garantias_30_dias' => 0,
    'custo_aquisicao' => 0,
    'custo_manutencao' => 0
];

$plano_hoje = [
    'manutencoes' => 0,
    'movimentacoes' => 0,
    'garantias' => 0
];

$eventos_agenda = [];
$dias_agenda = [];

for ($i = 0; $i < 7; $i++) {
    $dias_agenda[] = date('Y-m-d', strtotime('+' . $i . ' days'));
}

try {
    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST . ";dbname=" . MYSQL_DATABASE . ";charset=utf8",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );
    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $ligacao->query(
        "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN estado = 'Ativo' THEN 1 ELSE 0 END) AS ativos,
            SUM(CASE WHEN estado = 'Em manutenção' THEN 1 ELSE 0 END) AS manutencao,
            SUM(CASE WHEN criticidade IN ('Alta', 'Suporte de vida') THEN 1 ELSE 0 END) AS criticos,
            COALESCE(SUM(custo_aquisicao), 0) AS custo_aquisicao
         FROM equipamentos"
    );
    $dados_equipamentos = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($dados_equipamentos) {
        foreach ($dados_equipamentos as $chave => $valor) {
            $resumo[$chave] = $valor ?? 0;
        }
    }

    $resumo['sem_documentacao'] = (int) $ligacao->query(
        "SELECT COUNT(*)
         FROM equipamentos e
         LEFT JOIN documentacao d ON d.equipamento_id = e.id
         LEFT JOIN manuais_equipamentos m ON m.id_equipamento = e.id
         WHERE d.id IS NULL AND m.id_manual IS NULL"
    )->fetchColumn();

    $resumo['garantias_expiradas'] = (int) $ligacao->query(
        "SELECT COUNT(*)
         FROM garantias_contratos
         WHERE data_fim IS NOT NULL AND data_fim < CURDATE()"
    )->fetchColumn();

    $resumo['garantias_30_dias'] = (int) $ligacao->query(
        "SELECT COUNT(*)
         FROM garantias_contratos
         WHERE data_fim IS NOT NULL
         AND data_fim BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)"
    )->fetchColumn();

    $resumo['custo_manutencao'] = (float) $ligacao->query(
        "SELECT COALESCE(SUM(custo), 0) FROM manutencoes"
    )->fetchColumn();

    $stmt = $ligacao->prepare(
        "SELECT
            m.proxima_manutencao AS data_evento,
            m.tipo_manutencao,
            m.responsavel,
            e.id AS equipamento_id,
            e.codigo_inventario,
            e.designacao
         FROM manutencoes m
         INNER JOIN equipamentos e ON e.id = m.equipamento_id
         WHERE m.proxima_manutencao BETWEEN :inicio AND :fim
         ORDER BY m.proxima_manutencao, e.codigo_inventario"
    );
    $stmt->execute([':inicio' => $hoje, ':fim' => $fim_periodo]);
    foreach ($stmt->fetchAll(PDO::FETCH_OBJ) as $linha) {
        adicionar_evento(
            $eventos_agenda,
            $linha->data_evento,
            'Manutenção',
            $linha->codigo_inventario . ' · ' . $linha->tipo_manutencao,
            $linha->designacao . ($linha->responsavel ? ' · ' . $linha->responsavel : ''),
            'event-maintenance',
            $linha->equipamento_id
        );
    }

    $stmt = $ligacao->prepare(
        "SELECT
            mv.data_movimentacao AS data_evento,
            mv.local_origem,
            mv.local_destino,
            e.id AS equipamento_id,
            e.codigo_inventario,
            e.designacao
         FROM movimentacoes mv
         INNER JOIN equipamentos e ON e.id = mv.equipamento_id
         WHERE mv.data_movimentacao BETWEEN :inicio AND :fim
         ORDER BY mv.data_movimentacao, mv.id DESC"
    );
    $stmt->execute([':inicio' => $hoje, ':fim' => $fim_periodo]);
    foreach ($stmt->fetchAll(PDO::FETCH_OBJ) as $linha) {
        adicionar_evento(
            $eventos_agenda,
            $linha->data_evento,
            'Movimentação',
            $linha->codigo_inventario . ' · Transferência',
            $linha->local_origem . ' → ' . $linha->local_destino,
            'event-move',
            $linha->equipamento_id
        );
    }

    $stmt = $ligacao->prepare(
        "SELECT
            gc.data_fim AS data_evento,
            gc.tipo_contrato,
            gc.entidade_responsavel,
            e.id AS equipamento_id,
            e.codigo_inventario,
            e.designacao
         FROM garantias_contratos gc
         INNER JOIN equipamentos e ON e.id = gc.equipamento_id
         WHERE gc.data_fim BETWEEN :inicio AND :fim
         ORDER BY gc.data_fim, e.codigo_inventario"
    );
    $stmt->execute([':inicio' => $hoje, ':fim' => $fim_periodo]);
    foreach ($stmt->fetchAll(PDO::FETCH_OBJ) as $linha) {
        adicionar_evento(
            $eventos_agenda,
            $linha->data_evento,
            'Garantia',
            $linha->codigo_inventario . ' · ' . ($linha->tipo_contrato ?: 'Garantia'),
            'Termina nesta data' . ($linha->entidade_responsavel ? ' · ' . $linha->entidade_responsavel : ''),
            'event-warranty',
            $linha->equipamento_id
        );
    }

    $plano_hoje = contar_eventos_do_dia($eventos_agenda, $hoje);
} catch (PDOException $err) {
    $erro = 'Aconteceu um erro ao carregar os indicadores da dashboard.';
}

$ligacao = null;

function adicionar_evento(&$eventos, $data, $tipo, $titulo, $descricao, $classe, $equipamento_id = null)
{
    if (empty($data)) {
        return;
    }

    if (!isset($eventos[$data])) {
        $eventos[$data] = [];
    }

    $eventos[$data][] = [
        'tipo' => $tipo,
        'titulo' => $titulo,
        'descricao' => $descricao,
        'classe' => $classe,
        'equipamento_id' => $equipamento_id
    ];
}

function contar_eventos_do_dia($eventos, $data)
{
    $contagem = ['manutencoes' => 0, 'movimentacoes' => 0, 'garantias' => 0];

    foreach ($eventos[$data] ?? [] as $evento) {
        if ($evento['tipo'] === 'Manutenção') {
            $contagem['manutencoes']++;
        } elseif ($evento['tipo'] === 'Movimentação') {
            $contagem['movimentacoes']++;
        } elseif ($evento['tipo'] === 'Garantia') {
            $contagem['garantias']++;
        }
    }

    return $contagem;
}

function h($valor)
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

function percentagem($valor, $total)
{
    if ($total <= 0) {
        return 0;
    }

    return min(100, round(($valor / $total) * 100));
}

function dinheiro($valor)
{
    return number_format((float) $valor, 2, ',', '.') . ' €';
}

function data_curta($data)
{
    if (empty($data)) {
        return 'Sem data';
    }

    return date('d/m/Y', strtotime($data));
}

function nome_dia_curto($data)
{
    $dias = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    return $dias[(int) date('w', strtotime($data))];
}

function etiqueta_dia($data, $hoje)
{
    if ($data === $hoje) {
        return 'Hoje';
    }

    if ($data === date('Y-m-d', strtotime($hoje . ' +1 day'))) {
        return 'Amanhã';
    }

    return nome_dia_curto($data);
}


function interpretacao_dia($plano, $alertas)
{
    $total = (int) $plano['manutencoes'] + (int) $plano['movimentacoes'] + (int) $plano['garantias'];

    if ($total == 0 && $alertas == 0) {
        return 'Dia estável, sem tarefas planeadas nem alertas prioritários.';
    }

    if ($total == 0) {
        return 'Sem tarefas planeadas para hoje, mas existem alertas para acompanhar.';
    }

    $partes = [];
    if ($plano['manutencoes'] > 0) {
        $partes[] = $plano['manutencoes'] . ' manutenção' . ($plano['manutencoes'] > 1 ? 'ões' : '');
    }
    if ($plano['movimentacoes'] > 0) {
        $partes[] = $plano['movimentacoes'] . ' movimentação' . ($plano['movimentacoes'] > 1 ? 'ões' : '');
    }
    if ($plano['garantias'] > 0) {
        $partes[] = $plano['garantias'] . ' garantia' . ($plano['garantias'] > 1 ? 's' : '') . ' a terminar';
    }

    return 'Prioridade do dia: ' . implode(', ', $partes) . '.';
}

$peso_manutencao = percentagem($resumo['custo_manutencao'], max(1, $resumo['custo_aquisicao']));
$total_plano_hoje = (int) $plano_hoje['manutencoes'] + (int) $plano_hoje['movimentacoes'] + (int) $plano_hoje['garantias'];
$total_alertas = (int) $resumo['garantias_expiradas'] + (int) $resumo['garantias_30_dias'] + (int) $resumo['sem_documentacao'];
$interpretacao_hoje = interpretacao_dia($plano_hoje, $total_alertas);
$percentagem_ativos = percentagem($resumo['ativos'], max(1, $resumo['total']));
$estado_sistema = $total_alertas > 0 ? 'Atenção' : 'Operacional';
$eventos_json = json_encode($eventos_agenda, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<style>
    .dashboard-page {
        min-height: 100vh;
        background: #f5f7fa;
        padding: 10px 18px;
    }

    .page-head {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 7px;
    }

    .page-title {
        color: #1E3A5F;
        font-size: 1.48rem;
        font-weight: 900;
        margin: 0;
    }

    .page-subtitle {
        color: #52677d;
        font-size: 0.84rem;
        margin: 1px 0 0;
    }

    .kpi-strip {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 9px;
        margin-bottom: 8px;
    }

    .kpi-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 5px 14px rgba(15, 23, 42, 0.05);
        min-height: 64px;
        overflow: hidden;
        padding: 9px 12px;
        position: relative;
    }
    .kpi-card::after {
        content: "";
        position: absolute;
        inset: auto -24px -34px auto;
        width: 72px;
        height: 72px;
        border-radius: 999px;
        background: rgba(47, 93, 138, 0.08);
    }

    .kpi-card.primary {
        background: #1E3A5F;
        border-color: #1E3A5F;
        color: #ffffff;
    }

    .kpi-card.primary::after { background: rgba(255, 255, 255, 0.13); }
    .kpi-card.warning { border-top: 4px solid #f59e0b; }
    .kpi-card.danger { border-top: 4px solid #ef4444; }
    .kpi-card.success { border-top: 4px solid #22c55e; }
    .kpi-card.info { border-top: 4px solid #2563eb; }

    .kpi-label {
        color: #52677d;
        font-size: 0.62rem;
        font-weight: 900;
        text-transform: uppercase;
    }

    .kpi-card.primary .kpi-label,
    .kpi-card.primary .kpi-help,
    .kpi-card.primary .kpi-icon { color: rgba(255, 255, 255, 0.78); }

    .kpi-number {
        color: #0f172a;
        font-size: 1.28rem;
        font-weight: 900;
        line-height: 1;
        margin-top: 3px;
    }

    .kpi-card.primary .kpi-number { color: #ffffff; }

    .kpi-help {
        color: #64748b;
        font-size: 0.66rem;
        font-weight: 750;
        margin-top: 3px;
    }

    .kpi-icon {
        color: #2F5D8A;
        font-size: 1rem;
        position: absolute;
        right: 12px;
        top: 12px;
        z-index: 1;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 12px;
        align-items: start;
    }

    .calendar-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 5px 14px rgba(15, 23, 42, 0.05);
        padding: 11px 14px;
    }

    .calendar-head {
        align-items: center;
        border-bottom: 1px solid #e8eef5;
        display: flex;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 8px;
        padding-bottom: 8px;
    }

    .section-title {
        align-items: center;
        color: #1E3A5F;
        display: flex;
        font-size: 0.95rem;
        font-weight: 900;
        gap: 8px;
        margin: 0;
    }

    .section-title::before {
        background: #2F5D8A;
        border-radius: 999px;
        content: "";
        height: 16px;
        width: 4px;
    }

    .calendar-legend {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
        justify-content: flex-end;
    }

    .legend-chip {
        align-items: center;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        color: #52677d;
        display: inline-flex;
        font-size: 0.66rem;
        font-weight: 850;
        gap: 6px;
        padding: 5px 9px;
        white-space: nowrap;
    }

    .legend-dot {
        border-radius: 999px;
        display: inline-block;
        height: 6px;
        width: 6px;
    }

    .dot-maintenance { background: #2563eb; }
    .dot-move { background: #d97706; }
    .dot-warranty { background: #dc2626; }

    .calendar-tabs {
        background: #eef4fb;
        border: 1px solid #c9d8e8;
        border-radius: 10px;
        display: inline-flex;
        gap: 3px;
        padding: 3px;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.75);
    }

    .calendar-tab {
        background: transparent;
        border: 1px solid transparent;
        border-radius: 8px;
        color: #31506f;
        font-size: 0.78rem;
        font-weight: 900;
        padding: 6px 12px;
        transition: 0.12s ease;
    }

    .calendar-tab:hover {
        background: #ffffff;
        border-color: #dbe4ef;
        color: #1E3A5F;
    }

    .calendar-tab.active {
        background: #1E3A5F;
        border-color: #1E3A5F;
        box-shadow: 0 3px 8px rgba(30, 58, 95, 0.18);
        color: #ffffff;
    }

    .calendar-view[hidden] { display: none !important; }


    .calendar-card:not(.month-mode) .selected-day-card {
        display: none;
    }


    #calendar-month .pill.status-muted {
        display: none;
    }

    .week-calendar {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 8px;
    }

    .week-day {
        background: #fbfdff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        min-height: 218px;
        padding: 8px;
        cursor: pointer;
        transition: border-color 0.12s ease, box-shadow 0.12s ease, transform 0.12s ease;
    }

    .week-day:hover {
        border-color: #b7cbe0;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
    }

    .week-day.selected {
        box-shadow: 0 8px 20px rgba(47, 93, 138, 0.12);
        transform: translateY(-1px);
    }

    .week-day.today {
        background: linear-gradient(180deg, #eef6ff 0%, #ffffff 78%);
        border: 3px solid #0d6efd;
        box-shadow: 0 10px 24px rgba(13, 110, 253, 0.14);
        position: relative;
    }

    .week-day-header {
        align-items: flex-start;
        border-bottom: 1px solid #e8eef5;
        display: flex;
        gap: 6px;
        justify-content: space-between;
        margin-bottom: 8px;
        padding-bottom: 8px;
    }

    .week-day-name {
        color: #1E3A5F;
        font-size: 0.82rem;
        font-weight: 900;
    }

    .week-day-date {
        color: #64748b;
        font-size: 0.66rem;
        font-weight: 800;
        margin-top: 2px;
    }

    .week-events {
        display: grid;
        gap: 7px;
    }

    .week-event {
        background: #ffffff;
        border: 1px solid #e8eef5;
        border-left-width: 4px;
        border-radius: 8px;
        padding: 7px 8px;
    }

    .event-maintenance {
        background: #eff6ff;
        border-color: #bfdbfe;
        border-left-color: #2563eb;
    }

    .event-move {
        background: #fff7ed;
        border-color: #fed7aa;
        border-left-color: #d97706;
    }

    .event-warranty {
        background: #fef2f2;
        border-color: #fecaca;
        border-left-color: #dc2626;
    }

    .week-event-title,
    .week-event-title a {
        color: #0f172a;
        font-size: 0.76rem;
        font-weight: 900;
        line-height: 1.25;
        text-decoration: none;
    }

    .week-event-title a:hover {
        color: #1E3A5F;
        text-decoration: underline;
    }

    .week-event-sub {
        color: #64748b;
        font-size: 0.66rem;
        line-height: 1.25;
        margin-top: 3px;
    }

    .week-empty {
        color: #94a3b8;
        font-size: 0.76rem;
        font-style: italic;
        padding: 7px 2px;
    }

    .pill {
        border-radius: 999px;
        display: inline-flex;
        font-size: 0.66rem;
        font-weight: 900;
        margin-bottom: 4px;
        padding: 3px 8px;
        white-space: nowrap;
    }

    .status-muted { background: #e5e7eb; color: #374151; }

    .month-calendar {
        display: grid;
        gap: 6px;
        grid-template-columns: repeat(7, minmax(0, 1fr));
    }

    .month-head {
        color: #52677d;
        font-size: 0.66rem;
        font-weight: 900;
        padding-bottom: 2px;
        text-align: center;
        text-transform: uppercase;
    }

    .month-empty,
    .month-day {
        border-radius: 9px;
        min-height: 47px;
    }

    .month-day {
        background: #fbfdff;
        border: 1px solid #e2e8f0;
        padding: 4px 5px;
        cursor: pointer;
    }

    .month-day.selected {
        outline: 2px solid #2F5D8A;
        outline-offset: 1px;
    }

    .month-day.today {
        background: #eef6ff;
        border: 2px solid #0d6efd;
        box-shadow: 0 5px 14px rgba(13, 110, 253, 0.12);
    }

    .month-day-top {
        align-items: center;
        display: flex;
        gap: 6px;
        justify-content: space-between;
        margin-bottom: 3px;
    }

    .month-number {
        color: #0f172a;
        font-size: 0.78rem;
        font-weight: 900;
    }

    .month-event {
        background: #ffffff;
        border-left: 3px solid #2F5D8A;
        border-radius: 6px;
        color: #172033;
        font-size: 0.62rem;
        font-weight: 800;
        line-height: 1.2;
        margin-top: 2px;
        overflow: hidden;
        padding: 4px 5px;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .month-more {
        color: #52677d;
        font-size: 0.62rem;
        font-weight: 900;
        margin-top: 4px;
    }


    .month-dots {
        display: flex;
        flex-wrap: wrap;
        gap: 3px;
        margin-top: 3px;
    }

    .month-task-dot {
        border-radius: 999px;
        height: 6px;
        width: 6px;
    }

    .month-task-dot.event-maintenance { background: #2563eb; border-color: #2563eb; }
    .month-task-dot.event-move { background: #d97706; border-color: #d97706; }
    .month-task-dot.event-warranty { background: #dc2626; border-color: #dc2626; }


    .selected-day-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        margin-top: 6px;
        padding: 7px 9px;
    }

    .selected-day-head {
        align-items: center;
        display: flex;
        gap: 10px;
        justify-content: space-between;
        margin-bottom: 5px;
    }

    .selected-day-title {
        color: #1E3A5F;
        font-size: 0.84rem;
        font-weight: 900;
    }

    .selected-day-date {
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 850;
        margin-top: 1px;
    }

    .selected-events {
        display: grid;
        gap: 6px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .selected-event {
        border: 1px solid #e8eef5;
        border-left-width: 4px;
        border-radius: 8px;
        padding: 5px 7px;
    }

    .selected-event-title,
    .selected-event-title a {
        color: #0f172a;
        font-size: 0.72rem;
        font-weight: 900;
        line-height: 1.2;
        text-decoration: none;
    }

    .selected-event-sub {
        color: #64748b;
        font-size: 0.66rem;
        line-height: 1.25;
        margin-top: 2px;
    }

    .selected-empty {
        color: #64748b;
        font-size: 0.78rem;
        margin: 0;
    }

    @media (max-width: 1400px) {
        .kpi-strip { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .week-calendar { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }

    @media (max-width: 900px) {
        .page-head,
        .calendar-head {
            align-items: flex-start;
            flex-direction: column;
        }

        .calendar-legend { justify-content: flex-start; }

        .kpi-strip,
        .week-calendar,
        .month-calendar,
        .selected-events {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
</style>

<div class="container-fluid">
    <div class="row">
        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 dashboard-page">
            <header class="page-head">
                <div>
                    <h2 class="page-title">
                        <i class="fas fa-chart-line me-2"></i>
                        Dashboard
                    </h2>
                    <p class="page-subtitle">Planeamento diário e calendário operacional dos equipamentos médicos.</p>
                </div>
            </header>

            <?php if (!empty($erro)) : ?>
                <div class="alert alert-danger mt-2 mb-2"><?= h($erro) ?></div>
            <?php endif; ?>



            <section class="kpi-strip">
                <article class="kpi-card primary">
                    <i class="fas fa-desktop kpi-icon"></i>
                    <div class="kpi-label">Equipamentos</div>
                    <div class="kpi-number"><?= (int) $resumo['total'] ?></div>
                    <div class="kpi-help">Total registado</div>
                </article>
                <article class="kpi-card success">
                    <i class="fas fa-circle-check kpi-icon"></i>
                    <div class="kpi-label">Ativos</div>
                    <div class="kpi-number"><?= (int) $resumo['ativos'] ?></div>
                    <div class="kpi-help"><?= (int) $percentagem_ativos ?>% do parque</div>
                </article>
                <article class="kpi-card warning">
                    <i class="fas fa-screwdriver-wrench kpi-icon"></i>
                    <div class="kpi-label">Manutenção</div>
                    <div class="kpi-number"><?= (int) $resumo['manutencao'] ?></div>
                    <div class="kpi-help">Acompanhamento técnico</div>
                </article>
                <article class="kpi-card danger">
                    <i class="fas fa-triangle-exclamation kpi-icon"></i>
                    <div class="kpi-label">Críticos</div>
                    <div class="kpi-number"><?= (int) $resumo['criticos'] ?></div>
                    <div class="kpi-help">Alta criticidade</div>
                </article>
                <article class="kpi-card warning">
                    <i class="fas fa-file-contract kpi-icon"></i>
                    <div class="kpi-label">Garantias</div>
                    <div class="kpi-number"><?= (int) $resumo['garantias_30_dias'] ?></div>
                    <div class="kpi-help">A expirar em 30 dias</div>
                </article>
                <article class="kpi-card info">
                    <i class="fas fa-folder-open kpi-icon"></i>
                    <div class="kpi-label">Documentação</div>
                    <div class="kpi-number"><?= (int) $resumo['sem_documentacao'] ?></div>
                    <div class="kpi-help">Registos em falta</div>
                </article>
            </section>

            <div class="dashboard-grid">
                <section class="calendar-card">
                    <div class="calendar-head">
                        <h5 class="section-title">Calendário operacional</h5>
                        <div class="calendar-legend" aria-label="Legenda do calendário">
                            <span class="legend-chip"><span class="legend-dot dot-maintenance"></span>Manutenção</span>
                            <span class="legend-chip"><span class="legend-dot dot-move"></span>Movimentação</span>
                            <span class="legend-chip"><span class="legend-dot dot-warranty"></span>Garantia</span>
                            <span class="calendar-tabs" aria-label="Alternar vista do calendário">
                                <button type="button" class="calendar-tab active" data-calendar-view="week">Semana</button>
                                <button type="button" class="calendar-tab" data-calendar-view="month">Mês</button>
                            </span>
                        </div>
                    </div>

                    <div class="calendar-view" id="calendar-week">
                        <div class="week-calendar">
                            <?php foreach ($dias_agenda as $data) : ?>
                                <?php $eventos_dia = $eventos_agenda[$data] ?? []; ?>
                                <div class="week-day <?= $data === $hoje ? 'today selected' : '' ?>" data-calendar-date="<?= h($data) ?>">
                                    <div class="week-day-header">
                                        <div>
                                            <div class="week-day-name"><?= h(etiqueta_dia($data, $hoje)) ?></div>
                                            <div class="week-day-date"><?= h(data_curta($data)) ?></div>
                                        </div>
                                        <span class="pill status-muted"><?= count($eventos_dia) ?></span>
                                    </div>
                                    <div class="week-events">
                                        <?php if (count($eventos_dia) == 0) : ?>
                                            <div class="week-empty">Sem tarefas</div>
                                        <?php else : ?>
                                            <?php foreach ($eventos_dia as $evento) : ?>
                                                <div class="week-event <?= h($evento['classe']) ?>">
                                                    <div class="week-event-title">
                                                        <?php if (!empty($evento['equipamento_id'])) : ?>
                                                            <a href="../equipamentos/detalhes.php?id=<?= (int) $evento['equipamento_id'] ?>"><?= h($evento['titulo']) ?></a>
                                                        <?php else : ?>
                                                            <?= h($evento['titulo']) ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="week-event-sub"><?= h($evento['descricao']) ?></div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="calendar-view" id="calendar-month" hidden>
                        <div class="month-calendar">
                            <div class="month-head">Seg</div><div class="month-head">Ter</div><div class="month-head">Qua</div><div class="month-head">Qui</div><div class="month-head">Sex</div><div class="month-head">Sáb</div><div class="month-head">Dom</div>
                            <?php for ($i = 1; $i < $primeiro_dia_semana_mes; $i++) : ?>
                                <div class="month-empty"></div>
                            <?php endfor; ?>
                            <?php for ($dia = 1; $dia <= $dias_mes; $dia++) : ?>
                                <?php
                                    $data_mes = date('Y-m-d', strtotime(date('Y-m-') . str_pad($dia, 2, '0', STR_PAD_LEFT)));
                                    $eventos_mes = $eventos_agenda[$data_mes] ?? [];
                                    $eventos_visiveis = array_slice($eventos_mes, 0, 2);
                                ?>
                                <div class="month-day <?= $data_mes === $hoje ? 'today selected' : '' ?>" data-calendar-date="<?= h($data_mes) ?>">
                                    <div class="month-day-top">
                                        <span class="month-number"><?= $dia ?></span>
                                        <?php if (count($eventos_mes) > 0) : ?>
                                            <span class="pill status-muted"><?= count($eventos_mes) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (count($eventos_mes) > 0) : ?>
                                        <div class="month-dots">
                                            <?php foreach (array_slice($eventos_mes, 0, 4) as $evento) : ?>
                                                <span class="month-task-dot <?= h($evento['classe']) ?>"></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (count($eventos_mes) > 4) : ?>
                                        <div class="month-more">+<?= count($eventos_mes) - 4 ?> tarefa(s)</div>
                                    <?php endif; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <section class="selected-day-card" aria-live="polite">
                        <div class="selected-day-head">
                            <div>
                                <div class="selected-day-title">Informação do dia selecionado</div>
                                <div class="selected-day-date" id="selectedDayDate"><?= h(data_curta($hoje)) ?></div>
                            </div>
                            <span class="pill status-muted" id="selectedDayCount"><?= count($eventos_agenda[$hoje] ?? []) ?></span>
                        </div>
                        <div class="selected-events" id="selectedDayEvents"></div>
                    </section>
                </section>
            </div>
        </main>
    </div>
</div>

<script>
    const eventosAgenda = <?= $eventos_json ?: '{}' ?>;

    function formatarData(data) {
        const partes = data.split('-');
        return `${partes[2]}/${partes[1]}/${partes[0]}`;
    }

    function criarEvento(evento) {
        const card = document.createElement('div');
        card.className = `selected-event ${evento.classe}`;

        const titulo = document.createElement('div');
        titulo.className = 'selected-event-title';

        if (evento.equipamento_id) {
            const link = document.createElement('a');
            link.href = `../equipamentos/detalhes.php?id=${evento.equipamento_id}`;
            link.textContent = evento.titulo;
            titulo.appendChild(link);
        } else {
            titulo.textContent = evento.titulo;
        }

        const descricao = document.createElement('div');
        descricao.className = 'selected-event-sub';
        descricao.textContent = evento.descricao || '';

        card.appendChild(titulo);
        card.appendChild(descricao);
        return card;
    }

    function selecionarDia(data) {
        const eventos = eventosAgenda[data] || [];
        document.querySelectorAll('[data-calendar-date]').forEach((dia) => {
            dia.classList.toggle('selected', dia.dataset.calendarDate === data);
        });

        document.getElementById('selectedDayDate').textContent = formatarData(data);
        document.getElementById('selectedDayCount').textContent = eventos.length;

        const lista = document.getElementById('selectedDayEvents');
        lista.innerHTML = '';

        if (eventos.length === 0) {
            const vazio = document.createElement('p');
            vazio.className = 'selected-empty';
            vazio.textContent = 'Sem tarefas planeadas para este dia.';
            lista.appendChild(vazio);
            return;
        }

        eventos.forEach((evento) => lista.appendChild(criarEvento(evento)));
    }

    document.querySelectorAll('[data-calendar-view]').forEach((botao) => {
        botao.addEventListener('click', () => {
            const vista = botao.dataset.calendarView;
            document.querySelectorAll('[data-calendar-view]').forEach((item) => item.classList.remove('active'));
            botao.classList.add('active');
            document.getElementById('calendar-week').hidden = vista !== 'week';
            document.getElementById('calendar-month').hidden = vista !== 'month';
            document.querySelector('.calendar-card').classList.toggle('month-mode', vista === 'month');
        });
    });

    document.querySelectorAll('[data-calendar-date]').forEach((dia) => {
        dia.addEventListener('click', () => selecionarDia(dia.dataset.calendarDate));
    });

    selecionarDia('<?= h($hoje) ?>');
</script>

<?php include '../../includes/footer.php'; ?>