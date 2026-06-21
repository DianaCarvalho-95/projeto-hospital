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
$timeline = [];
$utilizadores_por_nome = [];
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

function chave_responsavel($valor)
{
    $valor = trim((string) $valor);
    $valor = mb_strtolower($valor, 'UTF-8');
    return preg_replace('/\s+/', ' ', $valor);
}

function iniciais_responsavel($nome)
{
    $partes = preg_split('/\s+/', trim((string) $nome));
    $iniciais = '';

    foreach ($partes as $parte) {
        if ($parte !== '') {
            $iniciais .= mb_strtoupper(mb_substr($parte, 0, 1, 'UTF-8'), 'UTF-8');
        }
        if (mb_strlen($iniciais, 'UTF-8') >= 2) {
            break;
        }
    }

    return $iniciais !== '' ? $iniciais : '?';
}

function foto_responsavel($responsavel, $utilizadores_por_nome)
{
    $chave = chave_responsavel($responsavel);
    return $utilizadores_por_nome[$chave] ?? null;
}
if ($id <= 0) {
    $erro = 'Equipamento inválido.';
} else {
    try {
        $ligacao = new PDO(
            "mysql:host=" . MYSQL_HOST . ";dbname=" . MYSQL_DATABASE . ";charset=utf8mb4",
            MYSQL_USERNAME,
            MYSQL_PASSWORD
        );

        $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt_utilizadores = $ligacao->query("SELECT display_name, name, identificador, fotografia FROM agents WHERE ativo = 1");
        foreach ($stmt_utilizadores->fetchAll(PDO::FETCH_OBJ) as $utilizador) {
            $foto = !empty($utilizador->fotografia)
                ? BASE_URL . '/private/assets/img/utilizadores/' . rawurlencode($utilizador->fotografia)
                : null;

            foreach ([$utilizador->display_name, $utilizador->identificador, strstr($utilizador->name, '@', true)] as $nome_chave) {
                if (!empty($nome_chave)) {
                    $utilizadores_por_nome[chave_responsavel($nome_chave)] = [
                        'nome' => $utilizador->display_name ?: $nome_chave,
                        'foto' => $foto
                    ];
                }
            }
        }

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
            if (!empty($equipamento->data_aquisicao)) {
                $timeline[] = [
                    'data' => $equipamento->data_aquisicao,
                    'tipo' => 'Aquisição',
                    'titulo' => 'Entrada do equipamento',
                    'descricao' => trim(($equipamento->tipo_entrada ?? 'Registo inicial') . ' · ' . moeda_pt($equipamento->custo_aquisicao ?? 0)),
                    'icone' => 'fa-cart-shopping',
                    'classe' => 'timeline-aquisicao'
                ];
            }

            foreach ($documentos as $doc) {
                $timeline[] = [
                    'data' => $doc->data_documento ?: $doc->data_validade,
                    'tipo' => 'Documentação',
                    'titulo' => $doc->tipo_documento ?: 'Documento técnico',
                    'descricao' => trim(($doc->nome_documento ?? '') . (!empty($doc->data_validade) ? ' · validade até ' . data_pt($doc->data_validade) : '')),
                    'icone' => 'fa-file-lines',
                    'classe' => 'timeline-documentacao'
                ];
            }

            foreach ($manuais as $manual) {
                $timeline[] = [
                    'data' => $manual->data_upload,
                    'tipo' => 'Manual',
                    'titulo' => $manual->titulo ?: 'Manual associado',
                    'descricao' => trim(($manual->tipo_manual ?? '') . (!empty($manual->idioma) ? ' · ' . $manual->idioma : '')),
                    'icone' => 'fa-book-open',
                    'classe' => 'timeline-documentacao'
                ];
            }

            foreach ($garantias as $garantia) {
                $timeline[] = [
                    'data' => $garantia->data_inicio ?: $garantia->data_fim,
                    'tipo' => 'Garantia / Contrato',
                    'titulo' => $garantia->tipo_contrato ?: 'Garantia associada',
                    'descricao' => trim(($garantia->entidade_responsavel ?? '') . (!empty($garantia->data_fim) ? ' · termina em ' . data_pt($garantia->data_fim) : '')),
                    'icone' => 'fa-shield-halved',
                    'classe' => 'timeline-garantia'
                ];
            }

            foreach ($manutencoes as $manutencao) {
                $timeline[] = [
                    'data' => $manutencao->data_manutencao,
                    'tipo' => 'Manutenção',
                    'titulo' => $manutencao->tipo_manutencao ?: 'Intervenção técnica',
                    'descricao' => trim(($manutencao->descricao ?? '') . (!empty($manutencao->responsavel) ? ' · ' . $manutencao->responsavel : '')),
                    'icone' => 'fa-wrench',
                    'classe' => 'timeline-manutencao'
                ];
            }

            foreach ($movimentacoes as $mov) {
                $timeline[] = [
                    'data' => $mov->data_movimentacao,
                    'tipo' => 'Movimentação',
                    'titulo' => $mov->motivo ?: 'Alteração de localização',
                    'descricao' => trim(($mov->local_origem ?? '-') . ' → ' . ($mov->local_destino ?? '-') . (!empty($mov->responsavel) ? ' · ' . $mov->responsavel : '')),
                    'icone' => 'fa-right-left',
                    'classe' => 'timeline-movimentacao'
                ];
            }

            foreach ($emprestimos as $emp) {
                $timeline[] = [
                    'data' => $emp->data_emprestimo,
                    'tipo' => 'Empréstimo',
                    'titulo' => $emp->estado ?: 'Empréstimo registado',
                    'descricao' => trim(($emp->servico_origem ?? '-') . ' → ' . ($emp->servico_destino ?? '-') . (!empty($emp->data_prevista_devolucao) ? ' · devolução prevista ' . data_pt($emp->data_prevista_devolucao) : '')),
                    'icone' => 'fa-handshake',
                    'classe' => 'timeline-emprestimo'
                ];
            }

            $timeline = array_values(array_filter($timeline, function ($item) {
                return !empty($item['data']);
            }));

            usort($timeline, function ($a, $b) {
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

if (!empty($equipamento->imagem_upload)) {
    $imagem = BASE_URL . '/private/uploads/equipamentos/' . rawurlencode($equipamento->imagem_upload);
} elseif (!empty($equipamento->imagem)) {
    $imagem = BASE_URL . '/private/assets/img/equipamentos/' . rawurlencode($equipamento->imagem);
} else {
    $imagem = BASE_URL . '/private/assets/img/hospital125.png';
}

?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/nav.php'; ?>
<?php include '../../includes/sidebar.php'; ?>
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
                            <i class="fa-solid fa-right-left"></i> Movimentações
                        </button>
                    </li>

                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#historico" type="button">
                            <i class="fa-solid fa-timeline"></i> Histórico
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
                        <div class="mb-3">
                            <h5 class="inner-title mb-0">Documentação / Manuais</h5>
                        </div>


                        <?php if (count($documentos) == 0 && count($manuais) == 0) : ?>

                            <div class="alert alert-info mb-0">
                                Não existem documentos ou manuais associados a este equipamento.
                            </div>

                        <?php else : ?>

                            <?php if (count($documentos) > 0) : ?>

                                <div class="d-flex justify-content-between align-items-center gap-2 mb-3 mt-2 flex-wrap">
                                    <h6 class="mb-0">
                                        <i class="fa-regular fa-file-lines me-2"></i>
                                        Documentação Técnica
                                    </h6>
                                    <a href="novo-documento.php?equipamento_id=<?= $equipamento->id ?>&tipo=ficha" class="btn btn-sm btn-outline-primary fw-bold">
                                        <i class="fa-regular fa-file-lines me-1"></i>Nova ficha técnica
                                    </a>
                                </div>

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

                                <div class="d-flex justify-content-between align-items-center gap-2 mb-3 mt-2 flex-wrap">
                                    <h6 class="mb-0">
                                        <i class="fa-solid fa-book-open me-2"></i>
                                        Manuais do Equipamento
                                    </h6>
                                    <a href="novo-documento.php?equipamento_id=<?= $equipamento->id ?>&tipo=manual" class="btn btn-sm btn-outline-primary fw-bold">
                                        <i class="fa-solid fa-book-open me-1"></i>Novo manual
                                    </a>
                                </div>

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
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-3 flex-wrap">
                            <h5 class="inner-title mb-0">Garantias / Contratos</h5>
                            <a href="nova-garantia.php?equipamento_id=<?= $equipamento->id ?>" class="btn btn-sm btn-outline-primary fw-bold">
                                <i class="fa-solid fa-file-contract me-1"></i>Nova garantia/contrato
                            </a>
                        </div>


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
                        <h5 class="inner-title">Movimentações e Empréstimos</h5>

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
                                            <td>
                                                <?php $foto_responsavel = foto_responsavel($item['responsavel'], $utilizadores_por_nome); ?>
                                                <div class="responsavel-cell">
                                                    <span class="responsavel-avatar">
                                                        <?php if (!empty($foto_responsavel['foto'])) : ?>
                                                            <img src="<?= h($foto_responsavel['foto']) ?>" alt="<?= h($item['responsavel']) ?>">
                                                        <?php else : ?>
                                                            <?= h(iniciais_responsavel($item['responsavel'])) ?>
                                                        <?php endif; ?>
                                                    </span>
                                                    <span class="responsavel-name"><?= h($item['responsavel']) ?></span>
                                                </div>
                                            </td>
                                            <td><?= h($item['estado']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane fade" id="historico">
                        <h5 class="inner-title">Histórico do equipamento</h5>

                        <?php if (count($timeline) == 0) : ?>
                            <p class="text-muted">Não existem acontecimentos registados no histórico deste equipamento.</p>
                        <?php else : ?>
                            <div class="timeline-list">
                                <?php foreach ($timeline as $evento) : ?>
                                    <div class="timeline-item <?= h($evento['classe']) ?>">
                                        <span class="timeline-date"><?= date('d/m', strtotime($evento['data'])) ?><small><?= date('Y', strtotime($evento['data'])) ?></small></span>
                                        <span class="timeline-marker"><span class="timeline-dot"><i class="fa-solid <?= h($evento['icone']) ?>"></i></span></span>
                                        <div class="timeline-content">
                                            <div class="timeline-type"><?= h($evento['tipo']) ?></div>
                                            <div class="timeline-title"><?= h($evento['titulo']) ?></div>
                                            <?php if (!empty($evento['descricao'])) : ?>
                                                <div class="timeline-description"><?= h($evento['descricao']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
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
    var tabsKey = 'equipamento_detalhes_<?= (int) $equipamento->id ?>_aba';
    var savedTab = window.location.hash || localStorage.getItem(tabsKey);

    if (savedTab) {
        var tabButton = document.querySelector('[data-bs-target="' + savedTab + '"]');
        if (tabButton && window.bootstrap) {
            new bootstrap.Tab(tabButton).show();
        }
    }

    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (tabButton) {
        tabButton.addEventListener('shown.bs.tab', function (event) {
            var target = event.target.getAttribute('data-bs-target');
            if (!target) {
                return;
            }

            localStorage.setItem(tabsKey, target);
            if (history.replaceState) {
                history.replaceState(null, '', target);
            }
        });
    });
});
</script>
<?php include '../../includes/footer.php'; ?>






















