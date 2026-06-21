<?php

// ====================================================================
// CONFIGURAÇÕES GERAIS DA APLICAÇÃO
// ====================================================================

define('BASE_URL', '/sibdas/1232099/medtech-solutions');
define('APP_NAME', 'MedTech Solutions');
define('APP_VERSION', '1.0.0');
define('APP_COPYRIGHT', '© 2026 MedTech Solutions – Gestão Hospitalar');

// ====================================================================
// CONFIGURAÇÕES DA BASE DE DADOS MYSQL/MARIADB
// ====================================================================

define('MYSQL_HOST', 'vsgate-s1.dei.isep.ipp.pt');
define('MYSQL_PORT', '10464');
define('MYSQL_DATABASE', 'db1232099');
define('MYSQL_USERNAME', '1232099');
define('MYSQL_PASSWORD', 'carvalho_099');
define('MYSQL_AES_KEY', 'carvalho_099');

// ====================================================================
// SEGURANÇA – ENCRIPTAÇÃO OPENSSL
// ====================================================================

define('OPENSSL_METHOD', 'AES-256-CBC');
define('OPENSSL_KEY', 'H0SDRQzIGqclX2kbYBk9xspdn9U5f3Wa');
define('OPENSSL_IV', 'BzKAbjuREsHgnw56');
