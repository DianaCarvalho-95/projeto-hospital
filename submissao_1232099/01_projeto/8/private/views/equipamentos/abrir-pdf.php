<?php

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../includes/funcoes.php';

redirect_if_not_logged();

$ficheiro = $_GET['ficheiro'] ?? '';
$ficheiro = basename($ficheiro);

if ($ficheiro === '') {
    http_response_code(404);
    exit('Ficheiro nao indicado.');
}

$pasta_documentos = realpath(__DIR__ . '/../../uploads/documentos');
$caminho_ficheiro = $pasta_documentos !== false
    ? realpath($pasta_documentos . DIRECTORY_SEPARATOR . $ficheiro)
    : false;

if (
    $pasta_documentos === false ||
    $caminho_ficheiro === false ||
    strpos($caminho_ficheiro, $pasta_documentos) !== 0 ||
    !is_file($caminho_ficheiro)
) {
    http_response_code(404);
    exit('Ficheiro nao encontrado.');
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . rawurlencode($ficheiro) . '"');
header('Content-Length: ' . filesize($caminho_ficheiro));
header('X-Content-Type-Options: nosniff');

readfile($caminho_ficheiro);
exit;
