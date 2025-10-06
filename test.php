<?php
// test.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/user.php';

try {
    $u = new User();

    // On fixe le login pour garder toujours le même utilisateur
    $login = 'coco6119';
    $password = 'Password@123'; // respecte les règles (Maj, min, chiffre, spécial)
    $email = "$login@test.com";

    echo "<pre>";

    // === Vérifie si l'utilisateur existe déjà ===
    $check = $u->connect($login, $password);
    if (!$check) {
        echo "== REGISTER (création initiale) ==\n";
        $res = $u->register($login, $password, $email, "Nicole", "Castillo");
        if (isset($res['error'])) {
            echo "Erreur à l'inscription : " . $res['error'] . "\n";
            exit;
        }
        print_r($u->getAllInfos());
    } else {
        echo "== CONNECT (utilisateur déjà existant) ==\n";
        print_r($u->getAllInfos());
    }

    echo "\n== UPDATE (modif email sans mdp) ==\n";
    $u->update($login, null, "$login-new@test.com", "Nicole", "Castillo");
    print_r($u->getAllInfos());

    echo "\n== UPDATE (avec changement de mot de passe) ==\n";
    $u->update($login, "Newpass@456", $u->getEmail(), $u->getFirstname(), $u->getLastname());

    echo "Test reconnexion avec ancien mot de passe : ";
    $u->disconnect();
    var_dump($u->connect($login, $password)); // false attendu

    echo "Test reconnexion avec nouveau mot de passe : ";
    var_dump($u->connect($login, "Newpass@456")); // true attendu

    // PAS DE DELETE ICI
    // echo "\n== DELETE ==\n";
    // var_dump($u->delete());
    // var_dump($u->isConnected());

    echo "\nTests terminés sans suppression.";
} catch (Throwable $e) {
    echo "ERREUR: " . $e->getMessage();
}
