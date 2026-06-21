<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/includes/funcoes.php';

start_session();
registar_evento('Autenticação', 'Logout', 'Utilizador', null, 'Sessão terminada.');
$_SESSION = [];
session_destroy();

header('Location: ' . BASE_URL . '/public/login.php');
exit;
