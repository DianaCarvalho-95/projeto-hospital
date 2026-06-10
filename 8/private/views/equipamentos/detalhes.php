<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$erro = '';

$equipamento = null;
$documentos = [];
$manuais = [];
$garantias = [];
$manutencoes = [];
$movimentacoes = [];
$emprestimos = [];
$historico = [];
$ultima_manutencao = null;
$proxima_manutencao = null;
$total_custos_manutencao = 0;
$estado_manutencao = 'Sem plano';
$classe_estado_manutencao = 'secondary';
$avaliacao_itens = [];
$pontuacao_avaliacao = 0;
$estado_avaliacao = 'Sem avaliação';
$classe_avaliacao = 'secondary';
$recomendacao_avaliacao = 'Sem dados suficientes para recomendação.';
$percentagem_custos_manutencao = null;
$custo_total_estimado = 0;
$estado_custos = 'Sem análise';
$classe_estado_custos = 'secondary';
$recomendacao_custos = 'Sem dados suficientes para análise financeira.';

function h($valor)
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

function data_pt($data)
{
    return !empty($data) ? date('d/m/Y', strtotime($data)) : '-';
}

function moeda_pt($valor)
{
    return $valor !== null && $valor !== ''
        ? number_format($valor, 2, ',', '.') . ' €'
        : '-';
}

if ($id <= 0) {
    $erro = 'Equipamento inválido.';
} else {
    try {
        $ligacao = new PDO(
            "mysql:host=" . MYSQL_HOST . ";dbname=" . MYSQL_DATABASE . ";charset=utf8",
            MYSQL_USERNAME,
            MYSQL_PASSWORD
        );

        $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $ligacao->prepare("
            SELECT 
                e.*,
                l.edificio,
                l.piso,
                l.servico,
                l.sala,
                f.nome_empresa,
                f.tipo_fornecedor,
                f.email AS email_fornecedor,
                f.telefone AS telefone_fornecedor,
                ie.imagem
            FROM equipamentos e
            LEFT JOIN localizacoes l ON e.localizacao_id = l.id
            LEFT JOIN fornecedores f ON e.fornecedor_id = f.id
            LEFT JOIN imagens_equipamentos ie ON e.categoria = ie.categoria
            WHERE e.id = :id
        ");

        $stmt->execute([':id' => $id]);
        $equipamento = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$equipamento) {
            $erro = 'Equipamento não encontrado.';
        } else {
            $stmt = $ligacao->prepare("
                SELECT *
                FROM documentacao
                WHERE equipamento_id = :id
                  AND LOWER(tipo_documento) NOT LIKE '%manual%'
                  AND LOWER(nome_documento) NOT LIKE '%manual%'
                ORDER BY data_documento DESC
            ");
            $stmt->execute([':id' => $id]);
            $documentos = $stmt->fetchAll(PDO::FETCH_OBJ);

            $stmt = $ligacao->prepare("SELECT * FROM manuais_equipamentos WHERE id_equipamento = :id ORDER BY data_upload DESC");
            $stmt->execute([':id' => $id]);
            $manuais = $stmt->fetchAll(PDO::FETCH_OBJ);

            $stmt = $ligacao->prepare("SELECT * FROM garantias_contratos WHERE equipamento_id = :id ORDER BY data_fim DESC");
            $stmt->execute([':id' => $id]);
            $garantias = $stmt->fetchAll(PDO::FETCH_OBJ);

            $stmt = $ligacao->prepare("SELECT * FROM manutencoes WHERE equipamento_id = :id ORDER BY data_manutencao DESC");
            $stmt->execute([':id' => $id]);
            $manutencoes = $stmt->fetchAll(PDO::FETCH_OBJ);

            foreach ($manutencoes as $m) {
                if ($ultima_manutencao === null || strtotime($m->data_manutencao) > strtotime($ultima_manutencao->data_manutencao)) {
                    $ultima_manutencao = $m;
                }

                if (!empty($m->proxima_manutencao)) {
                    if ($proxima_manutencao === null || strtotime($m->proxima_manutencao) < strtotime($proxima_manutencao->proxima_manutencao)) {
                        $proxima_manutencao = $m;
                    }
                }

                $total_custos_manutencao += (float) ($m->custo ?? 0);
            }

            if ($proxima_manutencao !== null) {
                $hoje = strtotime(date('Y-m-d'));
                $proxima_data = strtotime($proxima_manutencao->proxima_manutencao);
                $dias_para_proxima = (int) floor(($proxima_data - $hoje) / 86400);

                if ($dias_para_proxima < 0) {
                    $estado_manutencao = 'Atrasada';
                    $classe_estado_manutencao = 'danger';
                } elseif ($dias_para_proxima <= 30) {
                    $estado_manutencao = 'A vencer';
                    $classe_estado_manutencao = 'warning text-dark';
                } else {
                    $estado_manutencao = 'Em dia';
                    $classe_estado_manutencao = 'success';
                }
            }

            if (!empty($equipamento->estado)) {
                $estado_equipamento = mb_strtolower($equipamento->estado, 'UTF-8');

                if (strpos($estado_equipamento, 'manutenção') !== false || strpos($estado_equipamento, 'manutencao') !== false) {
                    $estado_manutencao = 'Em manutenção';
                    $classe_estado_manutencao = 'warning text-dark';
                } elseif (strpos($estado_equipamento, 'calibração') !== false || strpos($estado_equipamento, 'calibracao') !== false) {
                    $estado_manutencao = 'Em calibração';
                    $classe_estado_manutencao = 'info text-dark';
                } elseif (strpos($estado_equipamento, 'inativo') !== false) {
                    $estado_manutencao = 'Inativo';
                    $classe_estado_manutencao = 'secondary';
                }
            }

            $stmt = $ligacao->prepare("SELECT * FROM movimentacoes WHERE equipamento_id = :id ORDER BY data_movimentacao DESC");
            $stmt->execute([':id' => $id]);
            $movimentacoes = $stmt->fetchAll(PDO::FETCH_OBJ);

            $stmt = $ligacao->prepare("SELECT * FROM emprestimos WHERE equipamento_id = :id ORDER BY data_emprestimo DESC");
            $stmt->execute([':id' => $id]);
            $emprestimos = $stmt->fetchAll(PDO::FETCH_OBJ);

            foreach ($movimentacoes as $mov) {
                $historico[] = [
                    'tipo' => 'Movimentação',
                    'origem' => $mov->local_origem,
                    'destino' => $mov->local_destino,
                    'data' => $mov->data_movimentacao,
                    'devolucao' => '-',
                    'responsavel' => $mov->responsavel,
                    'estado' => $mov->motivo,
                ];
            }

            foreach ($emprestimos as $emp) {
                $historico[] = [
                    'tipo' => 'Empréstimo',
                    'origem' => $emp->servico_origem,
                    'destino' => $emp->servico_destino,
                    'data' => $emp->data_emprestimo,
                    'devolucao' => !empty($emp->data_devolucao)
                        ? data_pt($emp->data_devolucao)
                        : (!empty($emp->data_prevista_devolucao) ? 'Prevista: ' . data_pt($emp->data_prevista_devolucao) : '-'),
                    'responsavel' => $emp->responsavel,
                    'estado' => $emp->estado,
                ];
            }

            usort($historico, function ($a, $b) {
                return strtotime($b['data']) <=> strtotime($a['data']);
            });

            $estado_equipamento = mb_strtolower($equipamento->estado ?? '', 'UTF-8');
            $criticidade = mb_strtolower($equipamento->criticidade ?? '', 'UTF-8');

            if (strpos($estado_equipamento, 'ativo') !== false) {
                $avaliacao_itens[] = ['critério' => 'Condição operacional', 'pontos' => 20, 'máximo' => 20, 'estado' => 'Bom', 'classe' => 'success', 'observação' => 'Equipamento disponível para utilização.'];
            } elseif (strpos($estado_equipamento, 'manuten') !== false) {
                $avaliacao_itens[] = ['critério' => 'Condição operacional', 'pontos' => 10, 'máximo' => 20, 'estado' => 'Atenção', 'classe' => 'warning text-dark', 'observação' => 'Equipamento encontra-se em manutenção.'];
            } elseif (strpos($estado_equipamento, 'calibra') !== false) {
                $avaliacao_itens[] = ['critério' => 'Condição operacional', 'pontos' => 12, 'máximo' => 20, 'estado' => 'Atenção', 'classe' => 'warning text-dark', 'observação' => 'Equipamento encontra-se em calibração.'];
            } elseif (strpos($estado_equipamento, 'inativo') !== false) {
                $avaliacao_itens[] = ['critério' => 'Condição operacional', 'pontos' => 2, 'máximo' => 20, 'estado' => 'Crítico', 'classe' => 'danger', 'observação' => 'Equipamento inativo.'];
            } else {
                $avaliacao_itens[] = ['critério' => 'Condição operacional', 'pontos' => 8, 'máximo' => 20, 'estado' => 'Atenção', 'classe' => 'warning text-dark', 'observação' => 'Estado operacional pouco claro.'];
            }

            if (strpos($criticidade, 'alta') !== false) {
                $avaliacao_itens[] = ['critério' => 'Criticidade', 'pontos' => 10, 'máximo' => 15, 'estado' => 'Atenção', 'classe' => 'warning text-dark', 'observação' => 'Equipamento crítico; requer acompanhamento regular.'];
            } elseif (strpos($criticidade, 'média') !== false || strpos($criticidade, 'media') !== false) {
                $avaliacao_itens[] = ['critério' => 'Criticidade', 'pontos' => 13, 'máximo' => 15, 'estado' => 'Bom', 'classe' => 'success', 'observação' => 'Criticidade moderada.'];
            } else {
                $avaliacao_itens[] = ['critério' => 'Criticidade', 'pontos' => 15, 'máximo' => 15, 'estado' => 'Bom', 'classe' => 'success', 'observação' => 'Criticidade baixa.'];
            }

            if (count($manutencoes) == 0) {
                $avaliacao_itens[] = ['critério' => 'Manutenção', 'pontos' => 4, 'máximo' => 20, 'estado' => 'Crítico', 'classe' => 'danger', 'observação' => 'Sem histórico de manutenção registado.'];
            } elseif ($proxima_manutencao === null) {
                $avaliacao_itens[] = ['critério' => 'Manutenção', 'pontos' => 8, 'máximo' => 20, 'estado' => 'Atenção', 'classe' => 'warning text-dark', 'observação' => 'Sem próxima manutenção definida.'];
            } elseif ($estado_manutencao === 'Atrasada') {
                $avaliacao_itens[] = ['critério' => 'Manutenção', 'pontos' => 5, 'máximo' => 20, 'estado' => 'Crítico', 'classe' => 'danger', 'observação' => 'Próxima manutenção encontra-se atrasada.'];
            } elseif ($estado_manutencao === 'A vencer') {
                $avaliacao_itens[] = ['critério' => 'Manutenção', 'pontos' => 14, 'máximo' => 20, 'estado' => 'Atenção', 'classe' => 'warning text-dark', 'observação' => 'Manutenção aproxima-se do prazo.'];
            } else {
                $avaliacao_itens[] = ['critério' => 'Manutenção', 'pontos' => 20, 'máximo' => 20, 'estado' => 'Bom', 'classe' => 'success', 'observação' => 'Plano de manutenção dentro do prazo.'];
            }

            if (count($documentos) > 0 && count($manuais) > 0) {
                $avaliacao_itens[] = ['critério' => 'Documentação', 'pontos' => 15, 'máximo' => 15, 'estado' => 'Bom', 'classe' => 'success', 'observação' => 'Ficha técnica e manual associados.'];
            } elseif (count($documentos) > 0 || count($manuais) > 0) {
                $avaliacao_itens[] = ['critério' => 'Documentação', 'pontos' => 9, 'máximo' => 15, 'estado' => 'Atenção', 'classe' => 'warning text-dark', 'observação' => 'Documentação parcialmente associada.'];
            } else {
                $avaliacao_itens[] = ['critério' => 'Documentação', 'pontos' => 2, 'máximo' => 15, 'estado' => 'Crítico', 'classe' => 'danger', 'observação' => 'Sem documentação técnica associada.'];
            }

            $garantia_ativa = false;
            foreach ($garantias as $g) {
                if (!empty($g->data_fim) && strtotime($g->data_fim) >= strtotime(date('Y-m-d'))) {
                    $garantia_ativa = true;
                    break;
                }
            }

            if ($garantia_ativa) {
                $avaliacao_itens[] = ['critério' => 'Garantia / Contrato', 'pontos' => 15, 'máximo' => 15, 'estado' => 'Bom', 'classe' => 'success', 'observação' => 'Garantia ou contrato ativo.'];
            } elseif (count($garantias) > 0) {
                $avaliacao_itens[] = ['critério' => 'Garantia / Contrato', 'pontos' => 7, 'máximo' => 15, 'estado' => 'Atenção', 'classe' => 'warning text-dark', 'observação' => 'Garantia/contrato registado, mas sem validade ativa.'];
            } else {
                $avaliacao_itens[] = ['critério' => 'Garantia / Contrato', 'pontos' => 3, 'máximo' => 15, 'estado' => 'Crítico', 'classe' => 'danger', 'observação' => 'Sem garantia ou contrato associado.'];
            }

            $custo_aquisicao = (float) ($equipamento->custo_aquisicao ?? 0);
            if ($custo_aquisicao > 0) {
                $percentagem_custos = ($total_custos_manutencao / $custo_aquisicao) * 100;

                if ($percentagem_custos <= 10) {
                    $avaliacao_itens[] = ['critério' => 'Custos', 'pontos' => 15, 'máximo' => 15, 'estado' => 'Bom', 'classe' => 'success', 'observação' => 'Custos de manutenção controlados.'];
                } elseif ($percentagem_custos <= 25) {
                    $avaliacao_itens[] = ['critério' => 'Custos', 'pontos' => 10, 'máximo' => 15, 'estado' => 'Atenção', 'classe' => 'warning text-dark', 'observação' => 'Custos de manutenção relevantes face ao valor de aquisição.'];
                } else {
                    $avaliacao_itens[] = ['critério' => 'Custos', 'pontos' => 5, 'máximo' => 15, 'estado' => 'Crítico', 'classe' => 'danger', 'observação' => 'Custos elevados; avaliar substituição ou contrato.'];
                }
            } else {
                $avaliacao_itens[] = ['critério' => 'Custos', 'pontos' => 10, 'máximo' => 15, 'estado' => 'Atenção', 'classe' => 'warning text-dark', 'observação' => 'Sem valor de aquisição para comparação.'];
            }

            foreach ($avaliacao_itens as $item) {
                $pontuacao_avaliacao += $item['pontos'];
            }

            if ($pontuacao_avaliacao >= 85) {
                $estado_avaliacao = 'Bom';
                $classe_avaliacao = 'success';
                $recomendacao_avaliacao = 'Manter em utilização e seguir o plano normal de manutenção.';
            } elseif ($pontuacao_avaliacao >= 70) {
                $estado_avaliacao = 'Requer acompanhamento';
                $classe_avaliacao = 'warning text-dark';
                $recomendacao_avaliacao = 'Manter em utilização com acompanhamento técnico próximo.';
            } elseif ($pontuacao_avaliacao >= 50) {
                $estado_avaliacao = 'Atenção';
                $classe_avaliacao = 'warning text-dark';
                $recomendacao_avaliacao = 'Agendar revisão técnica e validar documentação, contrato e manutenção.';
            } else {
                $estado_avaliacao = 'Crítico';
                $classe_avaliacao = 'danger';
                $recomendacao_avaliacao = 'Avaliar retirada temporária de utilização ou substituição.';
            }

            $custo_aquisicao = (float) ($equipamento->custo_aquisicao ?? 0);
            $custo_total_estimado = $custo_aquisicao + $total_custos_manutencao;

            if ($custo_aquisicao > 0) {
                $percentagem_custos_manutencao = ($total_custos_manutencao / $custo_aquisicao) * 100;

                if ($percentagem_custos_manutencao <= 10) {
                    $estado_custos = 'Custos controlados';
                    $classe_estado_custos = 'success';
                    $recomendacao_custos = 'Manter o plano atual de manutenção e continuar a acompanhar custos futuros.';
                } elseif ($percentagem_custos_manutencao <= 25) {
                    $estado_custos = 'Custos relevantes';
                    $classe_estado_custos = 'warning text-dark';
                    $recomendacao_custos = 'Acompanhar a evolução dos custos e avaliar renegociação de contrato ou manutenção preventiva.';
                } else {
                    $estado_custos = 'Avaliar substituição';
                    $classe_estado_custos = 'danger';
                    $recomendacao_custos = 'Custos de manutenção elevados face ao valor de aquisição; considerar substituição ou revisão contratual.';
                }
            } elseif ($total_custos_manutencao > 0) {
                $estado_custos = 'Aquisição sem valor';
                $classe_estado_custos = 'warning text-dark';
                $recomendacao_custos = 'Existem custos de manutenção, mas o custo de aquisição não está definido para comparação.';
            } else {
                $estado_custos = 'Sem custos registados';
                $classe_estado_custos = 'secondary';
                $recomendacao_custos = 'Não existem custos suficientes para análise financeira.';
            }
        }

        $ligacao = null;
    } catch (PDOException $err) {
        $erro = 'Aconteceu um erro ao carregar a ficha do equipamento.';
    }
}

$imagem = !empty($equipamento->imagem)
    ? BASE_URL . '/private/assets/img/equipamentos/' . $equipamento->imagem
    : BASE_URL . '/private/assets/img/hospital125.png';

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>
<?php include '../../includes/sidebar.php'; ?>

<style>
    .equipamento-main {
        margin-left: 16.666666%;
        padding: 88px 24px 18px 24px;
        background: #f5f7fa;
        height: 100vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .page-title {
        font-weight: 700;
        color: #0f172a;
        font-size: 1.45rem;
    }

    .page-subtitle {
        color: #64748b;
        font-size: 0.86rem;
    }

    .btn-voltar {
        background: #fff;
        color: #1E3A5F;
        border: 1px solid #dbe4ef;
        border-radius: 8px;
        padding: 7px 12px;
        font-weight: 700;
        font-size: 0.86rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
    }

    .btn-voltar:hover {
        background: #f6faff;
        border-color: #bdd5f0;
        color: #1E3A5F;
    }

    .summary-card,
    .tabs-card,
    .inner-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06);
    }

    .summary-card {
        padding: 12px 14px;
        overflow: hidden;
        flex: 0 0 auto;
    }

    .equipment-hero {
        display: grid;
        grid-template-columns: minmax(280px, 0.8fr) minmax(620px, 1.2fr);
        gap: 16px;
        align-items: center;
    }

    .equipment-left {
        display: grid;
        grid-template-columns: 92px 1fr;
        gap: 14px;
        align-items: center;
        min-width: 0;
    }

    .equipment-identity {
        min-width: 0;
    }

    .summary-actions {
        margin-top: 0;
    }

    .equipment-media {
        width: 92px;
        height: 78px;
        border: 1px solid #dbe3ed;
        border-radius: 12px;
        background: #f8fafc;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .equipment-img {
        width: 78px;
        height: 62px;
        object-fit: contain;
    }

    .equipment-title {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 700;
        margin-bottom: 4px;
        line-height: 1.25;
    }

    .equipment-subtitle {
        color: #46627f;
        font-size: 0.82rem;
        margin-bottom: 7px;
    }

    .equipment-meta-line {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .equipment-chip {
        background: #edf4fb;
        color: #24496d;
        border: 1px solid #d8e7f5;
        border-radius: 999px;
        padding: 3px 8px;
        font-size: 0.7rem;
        font-weight: 700;
    }

    .info-label {
        font-weight: 700;
        font-size: 0.7rem;
        color: #52677d;
        margin-bottom: 5px;
        text-transform: uppercase;
        letter-spacing: 0;
    }

    .info-value {
        color: #0f172a;
        font-size: 0.84rem;
        font-weight: 600;
        line-height: 1.35;
    }

    .badge-status {
        background: #ffe8a3;
        color: #8a5a00;
        padding: 5px 10px;
        border-radius: 7px;
        font-weight: 700;
        font-size: 0.72rem;
        display: inline-block;
    }

    .badge-critical {
        background: #ffd4d4;
        color: #b42318;
        padding: 5px 10px;
        border-radius: 7px;
        font-weight: 700;
        font-size: 0.72rem;
        display: inline-block;
    }

    .tabs-card {
        overflow: visible;
        margin-bottom: 0;
        flex: 1 1 auto;
        min-height: 0;
        display: flex;
        flex-direction: column;
    }

    .tabs-header {
        background: #fff;
        border-bottom: 1px solid #e5e7eb;
        padding: 0 16px;
        flex: 0 0 auto;
    }

    .nav-tabs {
        border-bottom: none;
        flex-wrap: nowrap;
        overflow-x: auto;
        overflow-y: hidden;
    }

    .nav-tabs .nav-link {
        border: none;
        color: #334155;
        font-weight: 600;
        padding: 12px 13px;
        white-space: nowrap;
        font-size: 0.82rem;
    }

    .nav-tabs .nav-link i {
        margin-right: 7px;
        color: #64748b;
    }

    .nav-tabs .nav-link.active {
        color: #0d6efd;
        background: transparent;
        border-bottom: 3px solid #0d6efd;
    }

    .nav-tabs .nav-link.active i {
        color: #0d6efd;
    }

    .tabs-body {
        padding: 16px;
        overflow: auto;
        min-height: 0;
        flex: 1 1 auto;
    }

    .inner-card {
        padding: 12px 14px;
        height: 100%;
    }

    .inner-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 800;
        color: #1E3A5F;
        margin-bottom: 12px;
        padding-bottom: 9px;
        border-bottom: 1px solid #e8eef5;
        font-size: 0.95rem;
    }

    .inner-title::before {
        content: "";
        width: 4px;
        height: 18px;
        border-radius: 999px;
        background: #2F5D8A;
        flex: 0 0 auto;
    }

    .details-row {
        display: grid;
        grid-template-columns: 118px 1fr;
        gap: 9px;
        margin-bottom: 7px;
        font-size: 0.84rem;
    }

    .details-row strong {
        color: #0f172a;
    }

    .notes-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 11px 12px;
        color: #334155;
        line-height: 1.55;
        font-size: 0.88rem;
    }

    .notes-empty {
        color: #64748b;
        font-style: italic;
    }

    .notes-list {
        display: flex;
        flex-direction: column;
        gap: 9px;
    }

    .note-item {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #f8fafc;
        padding: 10px 11px;
    }

    .note-item-general {
        background: #fffdf5;
        border-color: #f4d58d;
    }

    .note-top {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        align-items: center;
        margin-bottom: 5px;
    }

    .note-title {
        color: #0f172a;
        font-weight: 800;
        font-size: 0.84rem;
    }

    .note-date {
        color: #64748b;
        font-size: 0.72rem;
        white-space: nowrap;
    }

    .note-text {
        color: #334155;
        font-size: 0.82rem;
        line-height: 1.45;
        margin-bottom: 6px;
    }

    .note-footer {
        display: flex;
        gap: 8px;
        align-items: center;
        color: #64748b;
        font-size: 0.72rem;
    }

    .note-badge {
        display: inline-flex;
        align-items: center;
        padding: 2px 7px;
        border-radius: 999px;
        background: #e8f1fb;
        color: #1E3A5F;
        font-weight: 700;
    }

    .quick-actions {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .quick-actions-compact {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 8px;
    }

    .summary-actions .quick-action-copy small {
        display: none;
    }

    .summary-actions .quick-action-item {
        min-height: 40px;
        padding: 7px 8px;
    }

    .quick-action-item {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        min-height: 44px;
        padding: 8px 10px;
        border: 1px solid #dbe4ef;
        border-radius: 8px;
        background: #fff;
        color: #1E3A5F;
        text-decoration: none;
        transition: background 0.15s ease, border-color 0.15s ease, transform 0.15s ease;
    }

    .quick-action-item:hover {
        background: #f6faff;
        border-color: #bdd5f0;
        color: #1E3A5F;
        transform: translateY(-1px);
    }

    .quick-action-icon {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 7px;
        background: #edf4ff;
        color: #2F5D8A;
        flex: 0 0 auto;
        font-size: 0.9rem;
    }

    .quick-action-copy {
        display: flex;
        flex-direction: column;
        line-height: 1.2;
    }

    .quick-action-copy strong {
        font-size: 0.86rem;
        font-weight: 800;
    }

    .quick-action-copy small {
        margin-top: 2px;
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 500;
    }

    .quick-action-main {
        background: #f8fbff;
        border-color: #cfe0f5;
    }

    .quick-action-danger-zone {
        border-top: 1px solid #e5e7eb;
        padding-top: 8px;
        margin-top: 2px;
    }

    .quick-actions-compact .quick-action-danger-zone {
        border-top: 0;
        padding-top: 0;
        margin-top: 0;
    }

    .quick-action-danger {
        background: #fffafa;
        border-color: #f3c7cd;
        color: #9f1239;
    }

    .quick-action-danger .quick-action-icon {
        background: #fff1f2;
        color: #be123c;
    }

    .quick-action-danger:hover {
        background: #fff1f2;
        border-color: #f3a8b4;
        color: #9f1239;
    }

    .compact-status-row {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 8px;
    }

    .footer-update {
        border-top: 1px solid #e5e7eb;
        background: #f8fafc;
        padding: 9px 16px;
        color: #64748b;
        font-size: 0.78rem;
        flex: 0 0 auto;
    }

    .evaluation-panel {
        display: grid;
        grid-template-columns: 240px 1fr;
        gap: 18px;
        margin-bottom: 22px;
    }

    .evaluation-score-card {
        background: #f8fafc;
        border: 1px solid #e3eaf2;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
    }

    .evaluation-score {
        color: #0f172a;
        font-size: 2.6rem;
        font-weight: 800;
        line-height: 1;
        margin-bottom: 10px;
    }

    .evaluation-score span {
        color: #64748b;
        font-size: 1rem;
        font-weight: 700;
    }

    .evaluation-summary {
        background: #fff;
        border: 1px solid #e3eaf2;
        border-radius: 12px;
        padding: 18px;
    }

    .evaluation-summary p {
        color: #475569;
        margin-bottom: 0;
        line-height: 1.6;
    }

    .table-custom th {
        background: #2F5D8A;
        color: #fff;
        font-size: 0.85rem;
    }

    .table-custom td {
        font-size: 0.88rem;
        vertical-align: middle;
    }

    @media (max-width: 991px) {
        .equipamento-main {
            margin-left: 25%;
        }

        .equipment-hero {
            grid-template-columns: 1fr;
        }

        .evaluation-panel {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .equipment-identity {
            grid-template-columns: 1fr;
        }

        .equipment-media {
            width: 100%;
        }

    }
</style>

<main class="equipamento-main">

    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="page-title mb-1">Equipamentos</h2>
            <p class="page-subtitle mb-0">Consulta e gestão dos equipamentos médicos</p>
        </div>
    </div>

    <a href="lista.php" class="btn-voltar mb-4">
        <i class="fas fa-arrow-left me-1"></i>
        Voltar à lista
    </a>

    <?php if (!empty($erro)) : ?>

        <div class="alert alert-danger">
            <?= h($erro) ?>
        </div>

    <?php else : ?>

        <section class="summary-card mb-3">
            <div class="equipment-hero">

                <div class="equipment-left">
                    <div class="equipment-media">
                        <img src="<?= h($imagem) ?>" alt="Equipamento" class="equipment-img">
                    </div>

                    <div class="equipment-identity">
                        <h3 class="equipment-title">
                            <?= h($equipamento->codigo_inventario) ?> - <?= h($equipamento->designacao) ?>
                        </h3>

                        <div class="equipment-subtitle">
                            <?= h($equipamento->marca) ?> | <?= h($equipamento->modelo) ?>
                        </div>

                        <div class="equipment-meta-line">
                            <span class="equipment-chip"><?= h($equipamento->categoria) ?></span>
                            <span class="equipment-chip"><?= h($equipamento->numero_serie) ?></span>
                        </div>
                    </div>
                </div>

                <div class="summary-actions">
                    <div class="quick-actions-compact">
                        <a href="editar.php?id=<?= $equipamento->id ?>" class="quick-action-item quick-action-main">
                            <span class="quick-action-icon"><i class="fa-regular fa-pen-to-square"></i></span>
                            <span class="quick-action-copy"><strong>Editar equipamento</strong><small>Dados gerais</small></span>
                        </a>

                        <a href="nova-manutencao.php?equipamento_id=<?= $equipamento->id ?>" class="quick-action-item">
                            <span class="quick-action-icon"><i class="fa-solid fa-wrench"></i></span>
                            <span class="quick-action-copy"><strong>Registar manutenção</strong><small>Nova intervenção</small></span>
                        </a>

                        <a href="nova-movimentacao.php?equipamento_id=<?= $equipamento->id ?>" class="quick-action-item">
                            <span class="quick-action-icon"><i class="fa-solid fa-right-left"></i></span>
                            <span class="quick-action-copy"><strong>Registar movimentação</strong><small>Nova localização</small></span>
                        </a>

                        <a href="#" onclick="window.print(); return false;" class="quick-action-item">
                            <span class="quick-action-icon"><i class="fa-solid fa-print"></i></span>
                            <span class="quick-action-copy"><strong>Imprimir</strong><small>Ficha atual</small></span>
                        </a>

                        <div class="quick-action-danger-zone">
                            <a href="apagar.php?id=<?= $equipamento->id ?>" class="quick-action-item quick-action-danger">
                                <span class="quick-action-icon"><i class="fa-solid fa-trash-can"></i></span>
                                <span class="quick-action-copy"><strong>Desativar equipamento</strong><small>Retirar de uso</small></span>
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <section class="tabs-card">

            <div class="tabs-header">
                <ul class="nav nav-tabs" id="equipamentoTabs" role="tablist">

                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#dados" type="button">
                            <i class="fa-regular fa-clipboard"></i> Dados Gerais
                        </button>
                    </li>

                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#documentacao" type="button">
                            <i class="fa-regular fa-file-lines"></i> Documentação / Manuais
                        </button>
                    </li>

                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#garantias" type="button">
                            <i class="fa-solid fa-shield-halved"></i> Garantias / Contratos
                        </button>
                    </li>

                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#manutencoes" type="button">
                            <i class="fa-solid fa-wrench"></i> Manutenções
                        </button>
                    </li>

                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#movimentacoes" type="button">
                            <i class="fa-solid fa-clock-rotate-left"></i> Histórico
                        </button>
                    </li>

                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#avaliacao" type="button">
                            <i class="fa-solid fa-list-check"></i> Avaliação
                        </button>
                    </li>

                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#custos" type="button">
                            <i class="fa-solid fa-chart-line"></i> Custos
                        </button>
                    </li>

                </ul>
            </div>

            <div class="tabs-body">
                <div class="tab-content">

                    <div class="tab-pane fade show active" id="dados">

                        <div class="row g-2 align-items-stretch">

                            <div class="col-xl-3 col-lg-6">
                                <div class="inner-card h-100">
                                    <h5 class="inner-title">Estado e Classificação</h5>

                                    <div class="compact-status-row">
                                        <span class="badge-status"><?= h($equipamento->estado) ?></span>
                                        <span class="badge-critical"><?= h($equipamento->criticidade) ?></span>
                                    </div>
                                    <div class="details-row"><strong>Código:</strong><span><?= h($equipamento->codigo_inventario) ?></span></div>
                                    <div class="details-row"><strong>Categoria:</strong><span><?= h($equipamento->categoria) ?></span></div>
                                    <div class="details-row"><strong>Entrada:</strong><span><?= h($equipamento->tipo_entrada) ?></span></div>
                                </div>
                            </div>

                            <div class="col-xl-3 col-lg-6">
                                <div class="inner-card h-100">
                                    <h5 class="inner-title">Identificação Técnica</h5>

                                    <div class="details-row"><strong>Designação:</strong><span><?= h($equipamento->designacao) ?></span></div>
                                    <div class="details-row"><strong>Marca:</strong><span><?= h($equipamento->marca) ?></span></div>
                                    <div class="details-row"><strong>Modelo:</strong><span><?= h($equipamento->modelo) ?></span></div>
                                    <div class="details-row"><strong>Série:</strong><span><?= h($equipamento->numero_serie) ?></span></div>
                                    <div class="details-row"><strong>Fabricante:</strong><span><?= h($equipamento->fabricante) ?></span></div>
                                </div>
                            </div>

                            <div class="col-xl-3 col-lg-6">
                                <div class="inner-card h-100">
                                    <h5 class="inner-title">Aquisição e Localização</h5>

                                    <div class="details-row"><strong>Aquisição:</strong><span><?= data_pt($equipamento->data_aquisicao) ?></span></div>
                                    <div class="details-row"><strong>Ano:</strong><span><?= h($equipamento->ano_fabrico) ?></span></div>
                                    <div class="details-row"><strong>Custo:</strong><span><?= moeda_pt($equipamento->custo_aquisicao) ?></span></div>
                                    <div class="details-row"><strong>Serviço:</strong><span><?= h($equipamento->servico) ?></span></div>
                                    <div class="details-row"><strong>Sala:</strong><span><?= h($equipamento->sala) ?></span></div>
                                </div>
                            </div>

                            <div class="col-xl-3 col-lg-6">
                                <div class="inner-card h-100">
                                    <h5 class="inner-title">Fornecedor e Contactos</h5>

                                    <div class="details-row"><strong>Fornecedor:</strong><span><?= h($equipamento->nome_empresa) ?></span></div>
                                    <div class="details-row"><strong>Tipo:</strong><span><?= h($equipamento->tipo_fornecedor) ?></span></div>
                                    <div class="details-row"><strong>Email:</strong><span><?= h($equipamento->email_fornecedor) ?></span></div>
                                    <div class="details-row"><strong>Telefone:</strong><span><?= h($equipamento->telefone_fornecedor) ?></span></div>
                                </div>
                            </div>

                        </div>

                    </div>

                    <div class="tab-pane fade" id="documentacao">

                        <?php if (count($documentos) == 0 && count($manuais) == 0) : ?>

                            <div class="alert alert-info mb-0">
                                Não existem documentos ou manuais associados a este equipamento.
                            </div>

                        <?php else : ?>

                            <?php if (count($documentos) > 0) : ?>

                                <h6 class="mb-3 mt-2">
                                    <i class="fa-regular fa-file-lines me-2"></i>
                                    Documentação Técnica
                                </h6>

                                <div class="table-responsive mb-4">
                                    <table class="table table-bordered table-hover table-custom align-middle">
                                        <thead>
                                            <tr>
                                                <th>Tipo</th>
                                                <th>Nome</th>
                                                <th>Data</th>
                                                <th>Validade</th>
                                                <th>Ficheiro</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php foreach ($documentos as $doc) : ?>
                                                <tr>
                                                    <td><?= h($doc->tipo_documento) ?></td>
                                                    <td><?= h($doc->nome_documento) ?></td>
                                                    <td><?= data_pt($doc->data_documento) ?></td>
                                                    <td><?= data_pt($doc->data_validade) ?></td>
                                                    <td>
                                                        <?php if (!empty($doc->caminho_ficheiro)) : ?>
                                                            <a href="abrir-pdf.php?ficheiro=<?= urlencode($doc->caminho_ficheiro) ?>"
                                                               target="_blank"
                                                               class="btn btn-sm btn-outline-primary">
                                                                <i class="fa-solid fa-file-arrow-down me-1"></i>
                                                                Abrir
                                                            </a>
                                                        <?php else : ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                            <?php endif; ?>

                            <?php if (count($manuais) > 0) : ?>

                                <h6 class="mb-3 mt-2">
                                    <i class="fa-solid fa-book-open me-2"></i>
                                    Manuais do Equipamento
                                </h6>

                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover table-custom align-middle">
                                        <thead>
                                            <tr>
                                                <th>Título</th>
                                                <th>Tipo</th>
                                                <th>Idioma</th>
                                                <th>Data Upload</th>
                                                <th>Observações</th>
                                                <th>Ficheiro</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php foreach ($manuais as $manual) : ?>
                                                <tr>
                                                    <td><?= h($manual->titulo) ?></td>
                                                    <td><?= h($manual->tipo_manual) ?></td>
                                                    <td><?= h($manual->idioma) ?></td>
                                                    <td><?= data_pt($manual->data_upload) ?></td>
                                                    <td><?= !empty($manual->observacoes) ? h($manual->observacoes) : '-' ?></td>
                                                    <td>
                                                        <?php if (!empty($manual->ficheiro)) : ?>
                                                            <a href="abrir-pdf.php?ficheiro=<?= urlencode($manual->ficheiro) ?>"
                                                               target="_blank"
                                                               class="btn btn-sm btn-outline-primary">
                                                                <i class="fa-solid fa-book-open me-1"></i>
                                                                Abrir
                                                            </a>
                                                        <?php else : ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                            <?php endif; ?>

                        <?php endif; ?>

                    </div>

                    <div class="tab-pane fade" id="garantias">
                        <h5 class="inner-title">Garantias / Contratos</h5>

                        <?php if (count($garantias) == 0) : ?>
                            <p class="text-muted">Não existem garantias ou contratos associados.</p>
                        <?php else : ?>
                            <table class="table table-bordered table-custom">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Entidade</th>
                                        <th>Início</th>
                                        <th>Fim</th>
                                        <th>Periodicidade</th>
                                        <th>Ficheiro</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($garantias as $g) : ?>
                                        <tr>
                                            <td><?= h($g->tipo_contrato) ?></td>
                                            <td><?= h($g->entidade_responsavel) ?></td>
                                            <td><?= data_pt($g->data_inicio) ?></td>
                                            <td><?= data_pt($g->data_fim) ?></td>
                                            <td><?= h($g->periodicidade) ?></td>
                                            <td>
                                                <?php if (!empty($g->caminho_ficheiro)) : ?>
                                                    <a href="abrir-pdf.php?ficheiro=<?= urlencode($g->caminho_ficheiro) ?>"
                                                       target="_blank"
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fa-solid fa-file-contract me-1"></i>
                                                        Abrir
                                                    </a>
                                                <?php else : ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane fade" id="manutencoes">
                        <h5 class="inner-title">Manutenções</h5>

                        <?php if (count($manutencoes) == 0) : ?>
                            <p class="text-muted">Não existem manutenções associadas.</p>
                        <?php else : ?>
                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <div class="inner-card h-100">
                                        <div class="info-label">Estado</div>
                                        <span class="badge bg-<?= h($classe_estado_manutencao) ?>">
                                            <?= h($estado_manutencao) ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="inner-card h-100">
                                        <div class="info-label">Última manutenção</div>
                                        <div class="info-value">
                                            <?= $ultima_manutencao ? data_pt($ultima_manutencao->data_manutencao) : '-' ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="inner-card h-100">
                                        <div class="info-label">Próxima manutenção</div>
                                        <div class="info-value">
                                            <?= $proxima_manutencao ? data_pt($proxima_manutencao->proxima_manutencao) : '-' ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="inner-card h-100">
                                        <div class="info-label">Custo acumulado</div>
                                        <div class="info-value">
                                            <?= moeda_pt($total_custos_manutencao) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-custom align-middle">
                                    <thead>
                                        <tr>
                                            <th>Tipo</th>
                                            <th>Data da intervenção</th>
                                            <th>Próxima</th>
                                            <th>Estado</th>
                                            <th>Responsável</th>
                                            <th>Custo</th>
                                            <th>Descrição</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($manutencoes as $m) : ?>
                                            <?php
                                            $estado_linha = '-';
                                            $classe_linha = 'secondary';

                                            if (!empty($m->proxima_manutencao)) {
                                                $dias_linha = (int) floor((strtotime($m->proxima_manutencao) - strtotime(date('Y-m-d'))) / 86400);

                                                if ($dias_linha < 0) {
                                                    $estado_linha = 'Atrasada';
                                                    $classe_linha = 'danger';
                                                } elseif ($dias_linha <= 30) {
                                                    $estado_linha = 'A vencer';
                                                    $classe_linha = 'warning text-dark';
                                                } else {
                                                    $estado_linha = 'Em dia';
                                                    $classe_linha = 'success';
                                                }
                                            }
                                            ?>
                                            <tr>
                                                <td><?= h($m->tipo_manutencao) ?></td>
                                                <td><?= data_pt($m->data_manutencao) ?></td>
                                                <td><?= data_pt($m->proxima_manutencao) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= h($classe_linha) ?>">
                                                        <?= h($estado_linha) ?>
                                                    </span>
                                                </td>
                                                <td><?= h($m->responsavel) ?></td>
                                                <td><?= moeda_pt($m->custo) ?></td>
                                                <td><?= !empty($m->descricao) ? h($m->descricao) : '-' ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane fade" id="movimentacoes">
                        <h5 class="inner-title">Histórico de Movimentações e Empréstimos</h5>

                        <?php if (count($historico) == 0) : ?>
                            <p class="text-muted">Não existem movimentações ou empréstimos associados.</p>
                        <?php else : ?>
                            <table class="table table-bordered table-custom">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Origem</th>
                                        <th>Destino</th>
                                        <th>Data</th>
                                        <th>Devolução</th>
                                        <th>Responsável</th>
                                        <th>Estado / Motivo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historico as $item) : ?>
                                        <tr>
                                            <td><?= h($item['tipo']) ?></td>
                                            <td><?= h($item['origem']) ?></td>
                                            <td><?= h($item['destino']) ?></td>
                                            <td><?= data_pt($item['data']) ?></td>
                                            <td><?= h($item['devolucao']) ?></td>
                                            <td><?= h($item['responsavel']) ?></td>
                                            <td><?= h($item['estado']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane fade" id="avaliacao">
                        <h5 class="inner-title">Avaliação Técnica</h5>

                        <div class="evaluation-panel">
                            <div class="evaluation-score-card">
                                <div class="info-label">Pontuação geral</div>
                                <div class="evaluation-score">
                                    <?= h($pontuacao_avaliacao) ?><span>/100</span>
                                </div>
                                <span class="badge bg-<?= h($classe_avaliacao) ?>">
                                    <?= h($estado_avaliacao) ?>
                                </span>
                            </div>

                            <div class="evaluation-summary">
                                <div class="info-label">Recomendação</div>
                                <h6 class="mb-2"><?= h($recomendacao_avaliacao) ?></h6>
                                <p>
                                    Esta avaliação é calculada automaticamente com base no estado operacional,
                                    criticidade, manutenção, documentação, garantia/contrato e custos registados.
                                </p>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-custom align-middle">
                                <thead>
                                    <tr>
                                        <th>Critério</th>
                                        <th>Pontuação</th>
                                        <th>Estado</th>
                                        <th>Observação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($avaliacao_itens as $item) : ?>
                                        <tr>
                                            <td><?= h($item['critério']) ?></td>
                                            <td><?= h($item['pontos']) ?> / <?= h($item['máximo'] ?? '-') ?></td>
                                            <td>
                                                <span class="badge bg-<?= h($item['classe']) ?>">
                                                    <?= h($item['estado']) ?>
                                                </span>
                                            </td>
                                            <td><?= h($item['observação']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="custos">
                        <h5 class="inner-title">Custos</h5>

                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="inner-card h-100">
                                    <div class="info-label">Aquisição</div>
                                    <div class="info-value"><?= moeda_pt($equipamento->custo_aquisicao) ?></div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="inner-card h-100">
                                    <div class="info-label">Manutenção</div>
                                    <div class="info-value"><?= moeda_pt($total_custos_manutencao) ?></div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="inner-card h-100">
                                    <div class="info-label">Peso da manutenção</div>
                                    <div class="info-value">
                                        <?= $percentagem_custos_manutencao !== null ? number_format($percentagem_custos_manutencao, 1, ',', '.') . ' %' : '-' ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="inner-card h-100">
                                    <div class="info-label">Custo estimado</div>
                                    <div class="info-value"><?= moeda_pt($custo_total_estimado) ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="evaluation-summary mb-4">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div class="info-label mb-0">Interpretação</div>
                                <span class="badge bg-<?= h($classe_estado_custos) ?>">
                                    <?= h($estado_custos) ?>
                                </span>
                            </div>
                            <p><?= h($recomendacao_custos) ?></p>
                        </div>

                        <?php if (count($manutencoes) == 0) : ?>
                            <p class="text-muted">Não existem custos de manutenção associados.</p>
                        <?php else : ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-custom align-middle">
                                    <thead>
                                        <tr>
                                            <th>Tipo</th>
                                            <th>Responsável</th>
                                            <th>Descrição</th>
                                            <th>Custo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($manutencoes as $m) : ?>
                                            <tr>
                                                <td><?= h($m->tipo_manutencao) ?></td>
                                                <td><?= h($m->responsavel) ?></td>
                                                <td><?= !empty($m->descricao) ? h($m->descricao) : '-' ?></td>
                                                <td><?= moeda_pt($m->custo) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

            <div class="footer-update">
                <i class="fa-regular fa-clock me-2"></i>
                Ficha do equipamento atualizada automaticamente a partir da base de dados.
            </div>

        </section>

    <?php endif; ?>

</main>




<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.location.hash) {
        return;
    }

    var tabButton = document.querySelector('.nav-link[data-bs-target="' + window.location.hash + '"]');

    if (!tabButton) {
        return;
    }

    if (window.bootstrap && bootstrap.Tab) {
        bootstrap.Tab.getOrCreateInstance(tabButton).show();
    } else {
        tabButton.click();
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
