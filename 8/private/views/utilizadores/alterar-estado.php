<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

if (($_SESSION['profile'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . '/private/views/dashboard/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: lista.php');
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$estado = isset($_POST['estado']) ? (int) $_POST['estado'] : 1;
$estado = $estado === 1 ? 1 : 0;

try {
    $ligacao = new PDO(
        "mysql:host=" . MYSQL_HOST . ";port=" . MYSQL_PORT . ";dbname=" . MYSQL_DATABASE . ";charset=utf8mb4",
        MYSQL_USERNAME,
        MYSQL_PASSWORD
    );
    $ligacao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $ligacao->prepare('SELECT name FROM agents WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $utilizador = $stmt->fetch(PDO::FETCH_OBJ);

    if (!$utilizador) {
        header('Location: lista.php?msg=erro');
        exit;
    }

    if ($estado === 0 && $utilizador->name === ($_SESSION['utilizador'] ?? '')) {
        header('Location: lista.php?msg=propria-conta');
        exit;
    }

    $stmt = $ligacao->prepare('UPDATE agents SET ativo = :estado WHERE id = :id');
    $stmt->execute([
        ':estado' => $estado,
        ':id' => $id
    ]);

    registar_evento('Utilizadores', $estado === 1 ? 'Ativação' : 'Desativação', 'Utilizador', $id, $utilizador->name);

    header('Location: lista.php?msg=' . ($estado === 1 ? 'ativado' : 'desativado'));
    exit;
} catch (PDOException $err) {
    header('Location: lista.php?msg=erro');
    exit;
}

