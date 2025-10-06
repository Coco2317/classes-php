<?php
// test-pdo.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/user-pdo.php';

try {
    // === Création d'une instance Userpdo ===
    $u = new Userpdo(); // Connexion à la BDD via PDO

    // === Login unique pour éviter les doublons ===
    $login = 'coco' . rand(1000, 9999);

    echo "<pre>";

    echo "== REGISTER ==\n";
    $res = $u->register($login, "Password@123", "$login@test.com", "Nicole", "Castillo");
    if (isset($res['error'])) {
        echo "Erreur à l'inscription : " . $res['error'] . "\n";
        exit;
    }
    print_r($u->getAllInfos());

    echo "\n== DISCONNECT ==\n";
    $u->disconnect();
    var_dump($u->isConnected()); // Doit afficher false

    echo "\n== CONNECT ==\n";
    $ok = $u->connect($login, "Password@123");
    echo $ok ? " Connexion réussie\n" : "Échec de la connexion\n";
    print_r($u->getAllInfos());

    echo "\n== UPDATE (sans changer le mot de passe) ==\n";
    $u->update($login, null, "$login-2@test.com", "Nicole", "Castillo");
    print_r($u->getAllInfos());

    echo "\n== UPDATE (avec nouveau mot de passe) ==\n";
    $u->update($u->getLogin(), "Newpass@456", $u->getEmail(), $u->getFirstname(), $u->getLastname());

    echo "Test de reconnexion avec l'ancien mot de passe : ";
    $u->disconnect();
    var_dump($u->connect($login, "Password@123")); // false attendu

    echo "Test de reconnexion avec le nouveau mot de passe : ";
    var_dump($u->connect($login, "Newpass@456")); // true attendu

   // echo "\n== DELETE (optionnel) ==\n";
  // var_dump($u->delete()); // supprime l'utilisateur
  // var_dump($u->isConnected()); // false attendu

    echo "\n== Tentative de reconnexion après suppression ==\n";
    var_dump($u->connect($login, "Newpass@456")); // false attendu

    echo "\n Tous les tests PDO ont été effectués avec succès.";
} catch (Throwable $e) {
    echo "ERREUR: " . $e->getMessage();
}
