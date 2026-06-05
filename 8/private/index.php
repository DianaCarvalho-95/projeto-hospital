<?php

require_once __DIR__ . '/../config/config.php';

session_start();

// --------------------------------------------------------------------
// SEGURANÇA
// --------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

// --------------------------------------------------------------------
// RECOLHA DOS DADOS
// --------------------------------------------------------------------
$username = isset($_POST['text_username'])
    ? trim($_POST['text_username'])
    : '';

$password = isset($_POST['text_password'])
    ? trim($_POST['text_password'])
    : '';

// --------------------------------------------------------------------
// VALIDAÇÕES
// --------------------------------------------------------------------
$validation_errors = [];

if (empty($username)) {
    $validation_errors[] = 'O utilizador é obrigatório.';
}

if (empty($password)) {
    $validation_errors[] = 'A password é obrigatória.';
}

if (!empty($username) && !filter_var($username, FILTER_VALIDATE_EMAIL)) {
    $validation_errors[] = 'Introduza um email válido.';
}

if (!empty($password) && (strlen($password) < 6 || strlen($password) > 20)) {
    $validation_errors[] = 'A password deve ter entre 6 e 20 caracteres.';
}

if (!empty($validation_errors)) {
    $_SESSION['validation_errors'] = $validation_errors;
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

// --------------------------------------------------------------------
// VERIFICAÇÃO REAL NA BASE DE DADOS
// --------------------------------------------------------------------
try {

    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST .
            ";dbname=" . MYSQL_DATABASE .
            ";charset=utf8",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );

    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $ligacao->prepare(
        "SELECT * FROM agents
         WHERE name = :username
         AND passwrd = :password"
    );

    $stmt->execute([
        ':username' => $username,
        ':password' => $password
    ]);

    $agente = $stmt->fetch(PDO::FETCH_OBJ);

    if (!$agente) {
        $_SESSION['server_error'] = 'Login inválido.';
        header('Location: ' . BASE_URL . '/public/login.php');
        exit;
    }

    $stmt = $ligacao->prepare(
        "UPDATE agents
         SET last_login = NOW()
         WHERE id = :id"
    );

    $stmt->execute([
        ':id' => $agente->id
    ]);

    $_SESSION['utilizador'] = $agente->name;
    $_SESSION['profile'] = $agente->profile;

} catch (PDOException $err) {

    $_SESSION['server_error'] = 'Erro ao ligar à base de dados.';
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

$ligacao = null;

/*
    Login efetuado com sucesso.
    Redireciona para a Dashboard.
*/
header('Location: ' . BASE_URL . '/private/views/dashboard/dashboard.php');
exit;