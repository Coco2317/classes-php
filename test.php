<?php
// test.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/user.php';

try {
    $u = new User();

    // change login/email each run to avoid UNIQUE constraint errors
    $login = 'coco' . rand(1000, 9999);

    echo "<pre>";

    echo "== register ==\n";
    $u->register($login, "password123", "$login@test.com", "Nicole", "Castillo");
    print_r($u->getAllInfos());

    echo "\n== disconnect ==\n";
    $u->disconnect();
    var_dump($u->isConnected());

    echo "\n== connect ==\n";
    $ok = $u->connect($login, "password123");
    var_dump($ok, $u->getAllInfos());

    echo "\n== update (no password change) ==\n";
    $u->update($login, null, "$login-2@test.com", "Nicole", "Castillo");
    print_r($u->getAllInfos());

    echo "\n== update (with password) ==\n";
    $u->update($u->getLogin(), "newpass456", $u->getEmail(), $u->getFirstname(), $u->getLastname());

    echo "Test de reconnexion avec ancien mot de passe : ";
    $u->disconnect();
    var_dump($u->connect($login, "password123"));
    echo "Test de reconnexion avec nouveau mot de passe : ";
    var_dump($u->connect($login, "newpass456"));

    echo "\n== delete ==\n";
    var_dump($u->delete());
    var_dump($u->isConnected());

    // Vérifie que l'utilisateur a bien disparu :
    echo "\n== tentative de reconnexion après suppression ==\n";
    var_dump($u->connect($login, "newpass456"));
} catch (Throwable $e) {
    echo "ERREUR: " . $e->getMessage();
}
