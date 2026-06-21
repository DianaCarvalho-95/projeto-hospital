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
        "mysql:host=" . MYSQL_HOST . ";port=" . MYSQL_PORT . ";dbname=" . MYSQL_DATABASE . ";charset=utf8",
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

    $resumo['tarefas_atrasadas'] = (int) $ligacao->query(
        "SELECT
            (SELECT COUNT(*)
             FROM manutencoes
             WHERE proxima_manutencao IS NOT NULL
             AND proxima_manutencao < CURDATE())
            +
            (SELECT COUNT(*)
             FROM garantias_contratos
             WHERE data_fim IS NOT NULL
             AND data_fim < CURDATE())"
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
                <a class="kpi-card kpi-link overdue" href="prazos-atrasados.php" title="Ver tarefas e prazos em atraso">
                    <i class="fas fa-clock-rotate-left kpi-icon"></i>
                    <div class="kpi-label">Prazos em atraso</div>
                    <div class="kpi-number"><?= (int) $resumo['tarefas_atrasadas'] ?></div>
                    <div class="kpi-help">Manutenções ou garantias vencidas</div>
                </a>
                <a class="kpi-card kpi-link warning" href="../equipamentos/lista.php?estado=Em+manuten%C3%A7%C3%A3o" title="Ver equipamentos em manutenção">
                    <i class="fas fa-screwdriver-wrench kpi-icon"></i>
                    <div class="kpi-label">Manutenção</div>
                    <div class="kpi-number"><?= (int) $resumo['manutencao'] ?></div>
                    <div class="kpi-help">Acompanhamento técnico</div>
                </a>
                <a class="kpi-card kpi-link warning" href="garantias-expirar.php" title="Ver garantias a expirar">
                    <i class="fas fa-file-contract kpi-icon"></i>
                    <div class="kpi-label">Garantias</div>
                    <div class="kpi-number"><?= (int) $resumo['garantias_30_dias'] ?></div>
                    <div class="kpi-help">A expirar em 30 dias</div>
                </a>
                <a class="kpi-card kpi-link info" href="documentacao-pendente.php" title="Ver documentação em falta">
                    <i class="fas fa-folder-open kpi-icon"></i>
                    <div class="kpi-label">Documentação</div>
                    <div class="kpi-number"><?= (int) $resumo['sem_documentacao'] ?></div>
                    <div class="kpi-help">Registos em falta</div>
                </a>
            </section>

            <div class="dashboard-grid">
                <section class="calendar-card" data-dashboard-calendar data-calendar-events="<?= h($eventos_json ?: '{}') ?>" data-calendar-initial-date="<?= h($hoje) ?>">
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
<?php include '../../includes/footer.php'; ?>







