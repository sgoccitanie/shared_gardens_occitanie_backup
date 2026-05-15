<?php
// tests/bootstrap.php
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;

// Charger les variables d'environnement
if (file_exists(dirname(__DIR__) . '/config/bootstrap.php')) {
    require dirname(__DIR__) . '/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
}

// Charger l'autoloader de Composer
require dirname(__DIR__) . '/vendor/autoload.php';

// Initialiser le noyau Symfony pour les tests
if (file_exists(dirname(__DIR__) . '/tests/TestKernel.php')) {
    require dirname(__DIR__) . '/tests/TestKernel.php';
}
