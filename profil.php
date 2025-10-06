<?php
// profil.php (version avec affichage des identifiants de test + couleurs pastel)
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/user.php';

$u = new User();
$msg = "";

// Identifiants de test (affichés pour faciliter les tests)
$testLogin = 'coco6119';
$testPass  = 'password123';

//reconnecter l'utilisateur courant depuis la session
function reconnectFromSession(User $u): bool {
    if (!empty($_SESSION['login']) && !empty($_SESSION['pwd'])) {
        return $u->connect($_SESSION['login'], $_SESSION['pwd']);
    }
    return false;
}

$action = $_POST['action'] ?? null;

try {
    if ($action === 'login') {
        $login = $_POST['login'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($u->connect($login, $password)) {
            // On garde login + mdp en session pour les actions suivantes (démo simple)
            $_SESSION['login'] = $login;
            $_SESSION['pwd'] = $password;
            $msg = "Connecté avec succès.";
        } else {
            $msg = "Login ou mot de passe incorrect.";
        }
    } elseif ($action === 'update') {
        if (!reconnectFromSession($u)) {
            $msg = "Session expirée, reconnecte-toi.";
        } else {
            $newLogin  = $_POST['login'] ?? $u->getLogin();
            $newEmail  = $_POST['email'] ?? $u->getEmail();
            $newFirst  = $_POST['firstname'] ?? $u->getFirstname();
            $newLast   = $_POST['lastname'] ?? $u->getLastname();
            $newPass   = $_POST['password'] ?? ''; // si vide -> pas de changement

            // null si on ne veut pas modifier le mot de passe
            $pwdParam = ($newPass === '') ? null : $newPass;
            $ok = $u->update($newLogin, $pwdParam, $newEmail, $newFirst, $newLast);

            if ($ok) {
                $msg = "Profil mis à jour.";
                // si on a changé le login ou le mot de passe, la session doit suivre
                $_SESSION['login'] = $u->getLogin();
                if ($newPass !== '') {
                    $_SESSION['pwd'] = $newPass;
                }
            } else {
                $msg = "Mise à jour impossible (vérifie les champs).";
            }
        }
    } elseif ($action === 'delete') {
        if (!reconnectFromSession($u)) {
            $msg = "Session expirée, reconnecte-toi.";
        } else {
            if ($u->delete()) {
                $msg = "Compte supprimé.";
            } else {
                $msg = "Suppression impossible.";
            }
            // Dans tous les cas, on nettoie la session
            session_unset();
            session_destroy();
        }
    } elseif ($action === 'logout') {
        // Déconnexion volontaire
        session_unset();
        session_destroy();
        $msg = "Déconnecté.";
    } else {
        // si déjà connecté, on reconstitue l'état
        reconnectFromSession($u);
    }
} catch (Throwable $e) {
    $msg = "Erreur : " . $e->getMessage();
}

// Helper bool pour affichage
$connected = $u->isConnected();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Profil utilisateur</title>
<style>
    /* Palette pastel douce */
    :root{
        --bg: #fbf9ff;
        --card: #ffffff;
        --muted: #6b7280;
        --primary: #7daaff;     /* pastel blue */
        --primary-contrast: #0f172a;
        --secondary: #f3f0ff;   /* pastel lilac */
        --danger: #ff9b9b;      /* pastel red */
        --accent: #ffd8b5;      /* pastel peach */
        --border: #eef2ff;
    }

    body { font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; margin: 2rem; background: var(--bg); color: var(--primary-contrast); }
    .container { max-width: 780px; margin: 0 auto; background: var(--card); border-radius: 14px; padding: 24px; box-shadow: 0 8px 24px rgba(15,23,42,0.06); }
    h1 { margin-top: 0; color: var(--primary-contrast); }
    .msg { padding: 12px 16px; border-radius: 12px; background: var(--secondary); color: var(--primary-contrast); margin-bottom: 16px; }
    form { display: grid; gap: 12px; }
    label { font-weight: 600; color: var(--primary-contrast); }
    input { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 10px; background: #fff; }
    .row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 8px; }
    button { padding: 10px 14px; border: 0; border-radius: 10px; cursor: pointer; font-weight: 600; }
    .primary { background: var(--primary); color: #fff; box-shadow: 0 6px 18px rgba(125,170,255,0.18); }
    .secondary { background: var(--secondary); color: var(--primary-contrast); }
    .danger { background: var(--danger); color: #7a1a1a; }
    .card { background: #fff; padding: 16px; border-radius: 12px; border: 1px solid var(--border); }
    .divider { height: 1px; background: var(--border); margin: 16px 0; }
    .right { text-align: right; }
    .test-credentials { background: var(--accent); padding: 10px 12px; border-radius: 10px; margin-top: 12px; color: var(--primary-contrast); font-weight:600; }
    .small { font-size: 13px; color: var(--muted); }
</style>
</head>
<body>
<div class="container">
    <h1>Profil utilisateur</h1>

    <?php if(!empty($msg)): ?>
        <div class="msg"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!$connected): ?>
        <!-- FORMULAIRE DE CONNEXION -->
        <div class="card">
            <h2>Se connecter</h2>
            <form method="post">
                <label for="login">Login</label>
                <input type="text" id="login" name="login" required placeholder="Votre login (pas l'email)">

                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required placeholder="Votre mot de passe">

                <div class="actions right">
                    <button class="primary" type="submit" name="action" value="login">Connexion</button>
                </div>
            </form>

            <!-- Affichage des identifiants de test fournis -->
            <div class="test-credentials">
                <div>Login de test : <strong><?= htmlspecialchars($testLogin, ENT_QUOTES, 'UTF-8') ?></strong></div>
                <div>Mot de passe : <strong><?= htmlspecialchars($testPass, ENT_QUOTES, 'UTF-8') ?></strong></div>
                <div class="small">identifiants pour tester.</div>
            </div>
        </div>

    <?php else: ?>
        <!-- FORMULAIRE DE PROFIL -->
        <div class="card">
            <h2>Mes informations</h2>
            <form method="post">
                <label for="login">Login (non email)</label>
                <input type="text" id="login" name="login" value="<?= htmlspecialchars($u->getLogin(), ENT_QUOTES, 'UTF-8') ?>" required>

                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($u->getEmail(), ENT_QUOTES, 'UTF-8') ?>" required>

                <div class="row">
                    <div>
                        <label for="firstname">Prénom</label>
                        <input type="text" id="firstname" name="firstname" value="<?= htmlspecialchars($u->getFirstname(), ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div>
                        <label for="lastname">Nom</label>
                        <input type="text" id="lastname" name="lastname" value="<?= htmlspecialchars($u->getLastname(), ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                </div>

                <div class="divider"></div>

                <label for="password">Nouveau mot de passe (optionnel)</label>
                <input type="password" id="password" name="password" placeholder="Laisse vide pour ne pas changer">
                <small class="small">Règles : min 8 caractères, 1 maj, 1 min, 1 chiffre, 1 spécial (@$!%*?&.#_-)</small>

                <div class="actions">
                    <button class="primary" type="submit" name="action" value="update">Mettre à jour</button>
                    <button class="secondary" type="submit" name="action" value="logout">Se déconnecter</button>
                    <button class="danger" type="submit" name="action" value="delete" onclick="return confirm('Supprimer définitivement votre compte ?');">Supprimer mon compte</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
