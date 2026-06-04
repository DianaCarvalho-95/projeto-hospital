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

    /*
        Lista de equipamentos usada para preencher a caixa de seleção.
        O utilizador escolhe o equipamento que pretende avaliar.
    */
    $equipamentos = $ligacao
        ->query(
            "SELECT id, codigo_inventario, designacao
             FROM equipamentos
             ORDER BY codigo_inventario ASC"
        )
        ->fetchAll(PDO::FETCH_OBJ);

    if ($equipamento_id > 0) {

        /*
            Consulta que junta dados do equipamento, garantia/contrato
            e manutenção. Estes dados serão usados para calcular
            o índice técnico.
        */
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

            /*
                O índice técnico começa em 0 e vai aumentado
                de acordo com critérios técnicos simples.
                No final, a pontuação máxima é limitada a 100.
            */
            $pontuacao = 0;
            $observacoes = [];

            /*
                Critério 1: estado atual do equipamento.
                Equipamentos ativos recebem maior pontuação.
            */
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

            /*
                Critério 2: idade do equipamento.
                Equipamentos mais recentes recebem maior pontuação.
            */
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

            /*
                Critério 3: garantia ou contrato.
                Equipamentos com garantia ou contrato ativo têm menor risco operacional.
            */
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

            /*
                Critério 4: manutenção.
                Equipamentos com manutenção realizada no último ano recebem pontuação adicional.
            */
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

            /*
                Critério 5: criticidade clínica.
                Equipamentos de criticidade alta ou de suporte de vida
                exigem maior rigor. Por isso, é aplicada uma penalização.
            */
            if ($equipamento->criticidade == 'Alta') {
                $pontuacao -= 5;
                $observacoes[] = 'Equipamento de criticidade alta: avaliação mais exigente.';
            } elseif ($equipamento->criticidade == 'Suporte de vida') {
                $pontuacao -= 10;
                $observacoes[] = 'Equipamento de suporte de vida: avaliação mais exigente.';
            }

            /*
                Garante que a pontuação fica entre 0 e 100.
            */
            if ($pontuacao > 100) {
                $pontuacao = 100;
            }

            if ($pontuacao < 0) {
                $pontuacao = 0;
            }

            /*
                Classificação final com base na pontuação obtida.
            */
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
    .avaliacao-card {
        border: none;
        border-radius: 18px;
        padding: 22px;
        background: #fff;
        box-shadow: 0 8px 22px rgba(0, 0, 0, 0.08);
    }

    .score-box {
        border-radius: 18px;
        padding: 24px;
        color: #fff;
        text-align: center;
        box-shadow: 0 8px 22px rgba(0, 0, 0, 0.10);
    }

    .score-number {
        font-size: 3rem;
        font-weight: 700;
        line-height: 1;
    }

    .score-success {
        background: linear-gradient(135deg, #198754, #0f5132);
    }

    .score-primary {
        background: linear-gradient(135deg, #0d6efd, #084298);
    }

    .score-warning {
        background: linear-gradient(135deg, #f59f00, #d9480f);
    }

    .score-danger {
        background: linear-gradient(135deg, #dc3545, #842029);
    }

    .btn-voltar-wrapper {
        display: flex;
        justify-content: center;
        margin-top: 18px;
    }

    .btn-voltar-custom {
        background: #6c757d;
        color: #fff;
        border-radius: 8px;
        padding: 9px 22px;
        text-decoration: none;
        box-shadow: 0 6px 14px rgba(0, 0, 0, 0.15);
    }

    .btn-voltar-custom:hover {
        background: #5c636a;
        color: #fff;
    }
</style>

<div class="container-fluid">
    <div class="row">

        <?php include '../../includes/sidebar.php'; ?>

        <main class="col-md-9 col-lg-10 p-4">

            <h2>
                <i class="fa-solid fa-stethoscope me-2"></i>
                Avaliação Técnica
            </h2>

            <p class="text-muted">
                Avaliação automática do estado técnico de um equipamento com base no estado,
                idade, garantia, manutenção e criticidade clínica.
            </p>

            <?php if (!empty($erro)) : ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <div class="avaliacao-card mb-4">

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
                        <button type="submit" class="btn btn-secondary w-100">
                            Avaliar
                        </button>
                    </div>

                    <div class="col-md-2">
                        <a href="avaliacao-tecnica.php" class="btn btn-outline-secondary w-100">
                            Limpar
                        </a>
                    </div>

                </form>

            </div>

            <?php if ($avaliacao) : ?>

                <div class="row g-3">

                    <div class="col-md-4">
                        <div class="score-box score-<?= $avaliacao['classe'] ?>">
                            <p class="mb-1">Índice Técnico</p>
                            <div class="score-number">
                                <?= $avaliacao['pontuacao'] ?>
                            </div>
                            <p class="mb-0">/ 100</p>
                            <hr>
                            <h5><?= htmlspecialchars($avaliacao['classificacao']) ?></h5>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="avaliacao-card">

                            <h5>
                                <i class="fa-solid fa-circle-info me-2"></i>
                                Resultado da Avaliação
                            </h5>

                            <hr>

                            <p>
                                <strong>Equipamento:</strong>
                                <?= htmlspecialchars($avaliacao['equipamento']->codigo_inventario) ?>
                                -
                                <?= htmlspecialchars($avaliacao['equipamento']->designacao) ?>
                            </p>

                            <p>
                                <strong>Estado atual:</strong>
                                <?= htmlspecialchars($avaliacao['equipamento']->estado) ?>
                            </p>

                            <p>
                                <strong>Criticidade:</strong>
                                <?= htmlspecialchars($avaliacao['equipamento']->criticidade) ?>
                            </p>

                            <p>
                                <strong>Idade estimada:</strong>
                                <?= $avaliacao['idade'] !== null ? $avaliacao['idade'] . ' ano(s)' : 'Não disponível' ?>
                            </p>

                            <p>
                                <strong>Garantia/Contrato até:</strong>
                                <?= !empty($avaliacao['equipamento']->fim_garantia)
                                    ? date('d/m/Y', strtotime($avaliacao['equipamento']->fim_garantia))
                                    : 'Sem registo' ?>
                            </p>

                            <p>
                                <strong>Última manutenção:</strong>
                                <?= !empty($avaliacao['equipamento']->data_manutencao)
                                    ? date('d/m/Y', strtotime($avaliacao['equipamento']->data_manutencao))
                                    : 'Sem registo' ?>
                            </p>

                            <hr>

                            <h6>Observações automáticas:</h6>

                            <ul class="mb-0">
                                <?php foreach ($avaliacao['observacoes'] as $obs) : ?>
                                    <li><?= htmlspecialchars($obs) ?></li>
                                <?php endforeach; ?>
                            </ul>

                        </div>
                    </div>

                </div>

            <?php endif; ?>

            <div class="btn-voltar-wrapper">
                <a href="ferramentas.php" class="btn-voltar-custom">
                    <i class="fa-solid fa-arrow-left me-1"></i>
                    Voltar
                </a>
            </div>

        </main>

    </div>
</div>

<?php include '../../includes/footer.php'; ?>