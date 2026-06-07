<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$erro = '';
$equipamentos = [];
$avaliacao = null;

$equipamento_id = isset($_GET['equipamento_id']) ? intval($_GET['equipamento_id']) : 0;

try {

    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST .
            ";dbname=" . MYSQL_DATABASE .
            ";charset=utf8",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );

    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    /* Carrega a lista de equipamentos para a caixa de seleção */
    $equipamentos = $ligacao
        ->query(
            "SELECT id, codigo_inventario, designacao
             FROM equipamentos
             ORDER BY codigo_inventario ASC"
        )
        ->fetchAll(PDO::FETCH_OBJ);

    if ($equipamento_id > 0) {

        /* Consulta os dados necessários para calcular a avaliação técnica */
        $stmt = $ligacao->prepare(
            "SELECT
                e.*,
                gc.data_fim AS fim_garantia,
                m.data_manutencao,
                m.proxima_manutencao
             FROM equipamentos e
             LEFT JOIN garantias_contratos gc
                ON gc.equipamento_id = e.id
             LEFT JOIN manutencoes m
                ON m.equipamento_id = e.id
             WHERE e.id = :id
             ORDER BY gc.data_fim DESC, m.data_manutencao DESC
             LIMIT 1"
        );

        $stmt->execute([
            ':id' => $equipamento_id
        ]);

        $equipamento = $stmt->fetch(PDO::FETCH_OBJ);

        if ($equipamento) {

            $pontuacao = 0;
            $observacoes = [];

            /* Critério 1: estado operacional */
            if ($equipamento->estado == 'Ativo') {
                $pontuacao += 40;
                $observacoes[] = 'Equipamento ativo.';
            } elseif ($equipamento->estado == 'Em manutenção') {
                $pontuacao += 20;
                $observacoes[] = 'Equipamento em manutenção.';
            } elseif ($equipamento->estado == 'Em calibração') {
                $pontuacao += 25;
                $observacoes[] = 'Equipamento em calibração.';
            } else {
                $pontuacao += 10;
                $observacoes[] = 'Equipamento não se encontra em estado ativo.';
            }

            /* Critério 2: idade do equipamento */
            if (!empty($equipamento->ano_fabrico)) {
                $idade = date('Y') - intval($equipamento->ano_fabrico);

                if ($idade < 5) {
                    $pontuacao += 30;
                    $observacoes[] = 'Equipamento recente.';
                } elseif ($idade <= 10) {
                    $pontuacao += 15;
                    $observacoes[] = 'Equipamento com idade intermédia.';
                } else {
                    $pontuacao += 5;
                    $observacoes[] = 'Equipamento com idade elevada.';
                }
            } else {
                $idade = null;
                $observacoes[] = 'Ano de fabrico não registado.';
            }

            /* Critério 3: garantia ou contrato */
            if (!empty($equipamento->fim_garantia)) {
                if ($equipamento->fim_garantia >= date('Y-m-d')) {
                    $pontuacao += 20;
                    $observacoes[] = 'Garantia/contrato ainda ativo.';
                } else {
                    $observacoes[] = 'Garantia/contrato expirado.';
                }
            } else {
                $observacoes[] = 'Sem garantia/contrato associado.';
            }

            /* Critério 4: manutenção */
            if (!empty($equipamento->data_manutencao)) {
                $dias = (strtotime(date('Y-m-d')) - strtotime($equipamento->data_manutencao)) / 86400;

                if ($dias <= 365) {
                    $pontuacao += 10;
                    $observacoes[] = 'Manutenção realizada há menos de um ano.';
                } else {
                    $observacoes[] = 'Manutenção realizada há mais de um ano.';
                }
            } else {
                $observacoes[] = 'Sem manutenção registada.';
            }

            /* Critério 5: criticidade clínica */
            if ($equipamento->criticidade == 'Alta') {
                $pontuacao -= 5;
                $observacoes[] = 'Equipamento de criticidade alta: avaliação mais exigente.';
            } elseif ($equipamento->criticidade == 'Suporte de vida') {
                $pontuacao -= 10;
                $observacoes[] = 'Equipamento de suporte de vida: avaliação mais exigente.';
            }

            /* Garante que a pontuação fica entre 0 e 100 */
            if ($pontuacao > 100) {
                $pontuacao = 100;
            }

            if ($pontuacao < 0) {
                $pontuacao = 0;
            }

            /* Classificação final */
            if ($pontuacao >= 85) {
                $classificacao = 'Excelente';
                $classe = 'success';
            } elseif ($pontuacao >= 60) {
                $classificacao = 'Bom';
                $classe = 'primary';
            } elseif ($pontuacao >= 40) {
                $classificacao = 'Aceitável';
                $classe = 'warning';
            } else {
                $classificacao = 'Recomenda substituição';
                $classe = 'danger';
            }

            $avaliacao = [
                'equipamento' => $equipamento,
                'pontuacao' => $pontuacao,
                'classificacao' => $classificacao,
                'classe' => $classe,
                'idade' => $idade,
                'observacoes' => $observacoes
            ];

        } else {
            $erro = 'Equipamento não encontrado.';
        }
    }

} catch (PDOException $err) {

    $erro = 'Aconteceu um erro ao carregar a avaliação técnica.';
}

$ligacao = null;

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>

<style>
    .avaliacao-page {
        background: #f5f7fa;
        min-height: 100vh;
        padding: 24px;
    }

    .page-title {
        font-weight: 600;
        color: #1E3A5F;
        font-size: 1.8rem;
        margin-bottom: 0;
    }

    .page-subtitle {
        color: #64748b;
        font-size: 0.95rem;
        margin-bottom: 0;
    }

    .content-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        border: 1px solid #e5e7eb;
    }

    .form-label {
        font-weight: 600;
        color: #334155;
        font-size: 0.88rem;
    }

    .form-control {
        border-radius: 10px;
        border: 1px solid #dbe3ec;
        font-size: 0.9rem;
    }

    .form-control:focus {
        border-color: #2F5D8A;
        box-shadow: 0 0 0 0.15rem rgba(47, 93, 138, 0.18);
    }

    .btn-avaliar-custom {
        background: #edf4ff;
        border: 1px solid #d6e7ff;
        color: #2F5D8A;
        border-radius: 8px;
        font-weight: 600;
        padding: 8px 16px;
    }

    .btn-avaliar-custom:hover {
        background: #dcecff;
        color: #1E3A5F;
    }

    .btn-limpar-custom,
    .btn-voltar-custom {
        background: #eef2f7;
        border: 1px solid #dbe3ec;
        color: #475569;
        border-radius: 8px;
        font-weight: 600;
        padding: 8px 16px;
        text-decoration: none;
    }

    .btn-limpar-custom:hover,
    .btn-voltar-custom:hover {
        background: #e2e8f0;
        color: #334155;
    }

    /* Cartão do índice técnico com azul suave para sobressair */
    .score-box {
        background: #f4f8ff;
        border: 2px solid #bfd7ff;
        border-radius: 16px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 8px 18px rgba(47, 93, 138, 0.08);
        height: 100%;
    }

    .score-label {
        color: #1E3A5F;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .score-number {
        font-size: 4rem;
        font-weight: 700;
        line-height: 1;
    }

    .score-success {
        color: #198754;
    }

    .score-primary {
        color: #2F5D8A;
    }

    .score-warning {
        color: #c79200;
    }

    .score-danger {
        color: #dc3545;
    }

    .classification-badge {
        display: inline-block;
        margin-top: 16px;
        padding: 8px 18px;
        border-radius: 999px;
        font-weight: 600;
        font-size: 0.95rem;
    }

    .badge-success {
        background: #e8f5ee;
        color: #198754;
    }

    .badge-primary {
        background: #edf4ff;
        color: #2F5D8A;
    }

    .badge-warning {
        background: #fff6dd;
        color: #c79200;
    }

    .badge-danger {
        background: #fdeaea;
        color: #dc3545;
    }

    .section-title {
        color: #1E3A5F;
        font-weight: 600;
        font-size: 1rem;
        margin-bottom: 14px;
        border-bottom: 1px solid #e5e7eb;
        padding-bottom: 8px;
    }

    .info-item {
        margin-bottom: 10px;
        font-size: 0.92rem;
    }

    .info-label {
        display: block;
        color: #64748b;
        font-size: 0.78rem;
        font-weight: 600;
        margin-bottom: 2px;
    }

    .info-value {
        color: #0f172a;
        font-weight: 500;
    }

    .observacao-item {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 9px 12px;
        color: #475569;
        font-size: 0.9rem;
        height: 100%;
    }

    .mensagem-erro {
        background: #fdeaea;
        color: #bb2d3b;
        border: 1px solid #f8d3d3;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 16px;
    }
</style>

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 avaliacao-page">

            <div class="mb-4">

                <h2 class="page-title mb-1">
                    <i class="fa-solid fa-stethoscope me-2"></i>
                    Avaliação Técnica
                </h2>

                <p class="page-subtitle">
                    Avaliação automática do estado técnico dos equipamentos médicos.
                </p>

            </div>

            <?php if (!empty($erro)) : ?>

                <div class="mensagem-erro">
                    <?= htmlspecialchars($erro) ?>
                </div>

            <?php endif; ?>

            <div class="content-card mb-4">

                <form method="get" class="row align-items-end">

                    <div class="col-md-8">
                        <label class="form-label">Equipamento</label>

                        <select name="equipamento_id" class="form-control">
                            <option value="">Escolha um equipamento</option>

                            <?php foreach ($equipamentos as $equipamento) : ?>
                                <option value="<?= $equipamento->id ?>" <?= $equipamento_id == $equipamento->id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($equipamento->codigo_inventario . ' - ' . $equipamento->designacao) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-avaliar-custom w-100">
                            Avaliar
                        </button>
                    </div>

                    <div class="col-md-2">
                        <a href="avaliacao-tecnica.php" class="btn btn-limpar-custom w-100">
                            Limpar
                        </a>
                    </div>

                </form>

            </div>

            <?php if ($avaliacao) : ?>

                <div class="row g-3">

                    <div class="col-md-4">

                        <div class="score-box">

                            <p class="score-label">
                                Índice Técnico
                            </p>

                            <div class="score-number score-<?= $avaliacao['classe'] ?>">
                                <?= $avaliacao['pontuacao'] ?>
                            </div>

                            <p class="text-muted mb-0">
                                / 100
                            </p>

                            <div class="classification-badge badge-<?= $avaliacao['classe'] ?>">
                                <?= htmlspecialchars($avaliacao['classificacao']) ?>
                            </div>

                        </div>

                    </div>

                    <div class="col-md-8">

                        <div class="content-card h-100">

                            <!-- Resultado da Avaliação -->
                            <h5 class="section-title">
                                <i class="fa-solid fa-circle-info me-2"></i>
                                Resultado da Avaliação
                            </h5>

                            <div class="row">

                                <div class="col-md-6">

                                    <!-- Equipamento -->
                                    <div class="info-item">
                                        <span class="info-label">Equipamento</span>
                                        <span class="info-value">
                                            <?= htmlspecialchars($avaliacao['equipamento']->codigo_inventario) ?>
                                            -
                                            <?= htmlspecialchars($avaliacao['equipamento']->designacao) ?>
                                        </span>
                                    </div>

                                    <!-- Estado atual -->
                                    <div class="info-item">
                                        <span class="info-label">Estado atual</span>
                                        <span class="info-value">
                                            <?= htmlspecialchars($avaliacao['equipamento']->estado) ?>
                                        </span>
                                    </div>

                                    <!-- Criticidade -->
                                    <div class="info-item">
                                        <span class="info-label">Criticidade</span>
                                        <span class="info-value">
                                            <?= htmlspecialchars($avaliacao['equipamento']->criticidade) ?>
                                        </span>
                                    </div>

                                </div>

                                <div class="col-md-6">

                                    <!-- Idade estimada -->
                                    <div class="info-item">
                                        <span class="info-label">Idade estimada</span>
                                        <span class="info-value">
                                            <?= $avaliacao['idade'] !== null ? $avaliacao['idade'] . ' ano(s)' : 'Não disponível' ?>
                                        </span>
                                    </div>

                                    <!-- Garantia/Contrato até -->
                                    <div class="info-item">
                                        <span class="info-label">Garantia/Contrato até</span>
                                        <span class="info-value">
                                            <?= !empty($avaliacao['equipamento']->fim_garantia)
                                                ? date('d/m/Y', strtotime($avaliacao['equipamento']->fim_garantia))
                                                : 'Sem registo' ?>
                                        </span>
                                    </div>

                                    <!-- Última manutenção -->
                                    <div class="info-item">
                                        <span class="info-label">Última manutenção</span>
                                        <span class="info-value">
                                            <?= !empty($avaliacao['equipamento']->data_manutencao)
                                                ? date('d/m/Y', strtotime($avaliacao['equipamento']->data_manutencao))
                                                : 'Sem registo' ?>
                                        </span>
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <!-- Observações por baixo do índice técnico e do resultado -->
                <div class="content-card mt-3">

                    <h5 class="section-title">
                        <i class="fa-solid fa-list-check me-2"></i>
                        Observações automáticas
                    </h5>

                    <div class="row g-2">

                        <?php foreach ($avaliacao['observacoes'] as $obs) : ?>

                            <div class="col-md-6">

                                <div class="observacao-item">
                                    <i class="fa-solid fa-check me-2"></i>
                                    <?= htmlspecialchars($obs) ?>
                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>

            <?php endif; ?>

            <div class="mt-4 d-flex justify-content-center">

                <a href="ferramentas.php" class="btn btn-voltar-custom">
                    <i class="fa-solid fa-arrow-left me-1"></i>
                    Voltar
                </a>

            </div>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>