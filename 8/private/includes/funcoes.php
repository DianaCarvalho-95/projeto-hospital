<?php

function start_session()
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

function check_session()
{
    return isset($_SESSION['utilizador']);
}

function redirect_if_not_logged()
{
    start_session();

    if (!check_session()) {
        header('Location: /PROJETO-HOSPITAL/8/public/login.php');
        exit;
    }
}

function registar_evento($modulo, $acao, $entidade = null, $entidade_id = null, $descricao = null)
{
    try {
        if (!defined('MYSQL_HOST') || !defined('MYSQL_DATABASE')) {
            return;
        }

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $ligacao_logs = new PDO(
            "mysql:host=" . MYSQL_HOST . ";port=" . MYSQL_PORT . ";dbname=" . MYSQL_DATABASE . ";charset=utf8mb4",
            MYSQL_USERNAME,
            MYSQL_PASSWORD
        );
        $ligacao_logs->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt_log = $ligacao_logs->prepare(
            "INSERT INTO eventos_logs
                (utilizador, perfil, modulo, acao, entidade, entidade_id, descricao, ip, user_agent)
             VALUES
                (:utilizador, :perfil, :modulo, :acao, :entidade, :entidade_id, :descricao, :ip, :user_agent)"
        );

        $stmt_log->execute([
            ':utilizador' => $_SESSION['utilizador'] ?? null,
            ':perfil' => $_SESSION['profile'] ?? null,
            ':modulo' => $modulo,
            ':acao' => $acao,
            ':entidade' => $entidade,
            ':entidade_id' => $entidade_id,
            ':descricao' => $descricao,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null
        ]);
    } catch (Throwable $erro_log) {
        // O registo de logs nunca deve bloquear a utilização da plataforma.
    }
}

function guardar_upload_imagem_equipamento($campo, &$erros)
{
    if (empty($_FILES[$campo]) || $_FILES[$campo]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES[$campo]['error'] !== UPLOAD_ERR_OK) {
        $erros[] = 'Não foi possível carregar a imagem do equipamento.';
        return null;
    }

    if ($_FILES[$campo]['size'] > 5 * 1024 * 1024) {
        $erros[] = 'A imagem do equipamento não pode ter mais de 5 MB.';
        return null;
    }

    $extensao = strtolower(pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION));
    $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    if (!in_array($extensao, $extensoes_permitidas, true)) {
        $erros[] = 'A imagem deve ser JPG, PNG, WebP ou GIF.';
        return null;
    }

    $info_imagem = @getimagesize($_FILES[$campo]['tmp_name']);
    if ($info_imagem === false) {
        $erros[] = 'O ficheiro enviado não é uma imagem válida.';
        return null;
    }

    $pasta_destino = __DIR__ . '/../uploads/equipamentos';
    if (!is_dir($pasta_destino) && !mkdir($pasta_destino, 0775, true)) {
        $erros[] = 'A pasta de imagens dos equipamentos não está disponível.';
        return null;
    }

    $nome_ficheiro = 'equipamento_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extensao;
    $destino = $pasta_destino . DIRECTORY_SEPARATOR . $nome_ficheiro;

    if (!move_uploaded_file($_FILES[$campo]['tmp_name'], $destino)) {
        $erros[] = 'Não foi possível guardar a imagem do equipamento.';
        return null;
    }

    return $nome_ficheiro;
}

