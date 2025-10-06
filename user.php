<?php
class User
{
    // Attributs
    private $id;
    public $login;
    public $email;
    public $firstname;
    public $lastname;

    private $conn;
    private $connected = false;

    // Constructeur
    public function __construct(
        string $host = "localhost",
        string $user = "root",
        string $pass = "",
        string $db   = "classes"
    ) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $this->conn = new mysqli($host, $user, $pass, $db);
        $this->conn->set_charset('utf8mb4');
    }

    // Validation du login
    private function validateLogin(string $login): ?string {
        if (!preg_match('/^[a-zA-Z0-9._-]{3,20}$/', $login)) {
            return "Login invalide : utilisez 3-20 caractères (lettres, chiffres, . _ -).";
        }
        if (strpos($login, '@') !== false) {
            return "Login invalide : ne doit pas contenir de '@'.";
        }
        return null;
    }

    // Validation du mot de passe
    private function validatePassword(string $password): ?string {
        if (strlen($password) < 8) {
            return "Le mot de passe doit contenir au moins 8 caractères.";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return "Le mot de passe doit contenir au moins une lettre majuscule.";
        }
        if (!preg_match('/[a-z]/', $password)) {
            return "Le mot de passe doit contenir au moins une lettre minuscule.";
        }
        if (!preg_match('/[0-9]/', $password)) {
            return "Le mot de passe doit contenir au moins un chiffre.";
        }
        if (!preg_match('/[@$!%*?&.#_-]/', $password)) {
            return "Le mot de passe doit contenir au moins un caractère spécial (@$!%*?&.#_-).";
        }
        return null;
    }

    // Validation email
    private function validateEmail(string $email): ?string {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return "Adresse email invalide.";
        }
        return null;
    }

    // Inscription
    public function register(string $login, string $password, string $email, string $firstname, string $lastname)
    {
        // Normalisation
        $login = trim($login);
        $email = strtolower(trim($email));
        $firstname = trim($firstname);
        $lastname  = trim($lastname);

        // Validations
        if ($msg = $this->validateLogin($login))  return ["error" => $msg];
        if ($msg = $this->validatePassword($password)) return ["error" => $msg];
        if ($msg = $this->validateEmail($email))  return ["error" => $msg];

        // Vérifier unicité login / email
        $check = $this->conn->prepare("
            SELECT id FROM utilisateurs 
            WHERE login = ? OR email = ?
        ");
        $check->bind_param("ss", $login, $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            return ["error" => "Ce login ou cet email est déjà utilisé."];
        }

        // Insertion
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->conn->prepare("
            INSERT INTO utilisateurs (login, password, email, firstname, lastname)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssss", $login, $hashed, $email, $firstname, $lastname);
        $stmt->execute();

        // MAJ des propriétés
        $this->id = $this->conn->insert_id;
        $this->login = $login;
        $this->email = $email;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->connected = true;

        return $this->getAllInfos();
    }

    // Connexion (par login uniquement)
    public function connect(string $login, string $password): bool
    {
        $login = trim($login);

        $stmt = $this->conn->prepare("SELECT * FROM utilisateurs WHERE login = ?");
        $stmt->bind_param("s", $login);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $this->id = (int)$user['id'];
            $this->login = $user['login'];
            $this->email = $user['email'];
            $this->firstname = $user['firstname'];
            $this->lastname = $user['lastname'];
            $this->connected = true;
            return true;
        }
        return false;
    }

    // Déconnexion
    public function disconnect(): void
    {
        $this->id = null;
        $this->login = null;
        $this->email = null;
        $this->firstname = null;
        $this->lastname = null;
        $this->connected = false;
    }

    // Suppression (et déconnexion)
    public function delete(): bool
    {
        if (!$this->id) return false;
        $stmt = $this->conn->prepare("DELETE FROM utilisateurs WHERE id = ?");
        $stmt->bind_param("i", $this->id);
        $ok = $stmt->execute();
        $this->disconnect();
        return $ok;
    }

    // Mise à jour (mdp optionnel)
    public function update(string $login, ?string $password, string $email, string $firstname, string $lastname): bool
    {
        if (!$this->id) return false;

        $login = trim($login);
        $email = strtolower(trim($email));
        $firstname = trim($firstname);
        $lastname  = trim($lastname);

        // Validations
        if ($msg = $this->validateLogin($login)) return false;
        if ($msg = $this->validateEmail($email)) return false;
        if ($password !== null && $password !== '' && $msg = $this->validatePassword($password)) return false;

        if ($password !== null && $password !== '') {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $this->conn->prepare("
                UPDATE utilisateurs
                SET login = ?, password = ?, email = ?, firstname = ?, lastname = ?
                WHERE id = ?
            ");
            $stmt->bind_param("sssssi", $login, $hashed, $email, $firstname, $lastname, $this->id);
        } else {
            $stmt = $this->conn->prepare("
                UPDATE utilisateurs
                SET login = ?, email = ?, firstname = ?, lastname = ?
                WHERE id = ?
            ");
            $stmt->bind_param("ssssi", $login, $email, $firstname, $lastname, $this->id);
        }

        $ok = $stmt->execute();
        if ($ok) {
            $this->login = $login;
            $this->email = $email;
            $this->firstname = $firstname;
            $this->lastname = $lastname;
        }
        return $ok;
    }

    // État de connexion
    public function isConnected(): bool
    {
        return $this->connected;
    }

    // Getters
    public function getAllInfos(): array
    {
        return [
            "id" => $this->id,
            "login" => $this->login,
            "email" => $this->email,
            "firstname" => $this->firstname,
            "lastname" => $this->lastname
        ];
    }
    public function getId()        { return $this->id; }
    public function getLogin()     { return $this->login; }
    public function getEmail()     { return $this->email; }
    public function getFirstname() { return $this->firstname; }
    public function getLastname()  { return $this->lastname; }

    // Fermeture propre
    public function __destruct() {
        if ($this->conn instanceof mysqli) {
            $this->conn->close();
        }
    }
}
