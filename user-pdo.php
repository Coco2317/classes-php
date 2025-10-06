<?php
class Userpdo
{
    // Attributs
    private $id;
    public $login;
    public $email;
    public $firstname;
    public $lastname;

    private ?PDO $pdo = null;
    private bool $connected = false;

    // Constructeur
    public function __construct(
        string $dsn  = "mysql:host=localhost;dbname=classes;charset=utf8mb4",
        string $user = "root",
        string $pass = ""
    ) {
        $this->pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    // --- Validations ---
    private function validateLogin(string $login): ?string {
        if (!preg_match('/^[a-zA-Z0-9._-]{3,20}$/', $login)) {
            return "Login invalide : utilisez 3–20 caractères (lettres, chiffres, . _ -).";
        }
        if (strpos($login, '@') !== false) {
            return "Login invalide : ne doit pas contenir de '@'.";
        }
        return null;
    }
    private function validatePassword(string $password): ?string {
        if (strlen($password) < 8) return "Le mot de passe doit contenir au moins 8 caractères.";
        if (!preg_match('/[A-Z]/', $password)) return "Le mot de passe doit contenir au moins une lettre majuscule.";
        if (!preg_match('/[a-z]/', $password)) return "Le mot de passe doit contenir au moins une lettre minuscule.";
        if (!preg_match('/[0-9]/', $password)) return "Le mot de passe doit contenir au moins un chiffre.";
        if (!preg_match('/[@$!%*?&.#_-]/', $password)) return "Le mot de passe doit contenir au moins un caractère spécial (@$!%*?&.#_-).";
        return null;
    }
    private function validateEmail(string $email): ?string {
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? null : "Adresse email invalide.";
    }


    public function register(string $login, string $password, string $email, string $firstname, string $lastname)
    {
       
        $login = trim($login);
        $email = strtolower(trim($email));
        $firstname = trim($firstname);
        $lastname  = trim($lastname);

       
        if ($m = $this->validateLogin($login))      return ["error" => $m];
        if ($m = $this->validatePassword($password)) return ["error" => $m];
        if ($m = $this->validateEmail($email))      return ["error" => $m];

       
        $stmt = $this->pdo->prepare("SELECT id FROM utilisateurs WHERE login = :login OR email = :email");
        $stmt->execute([':login' => $login, ':email' => $email]);
        if ($stmt->fetch()) return ["error" => "Ce login ou cet email est déjà utilisé."];

      
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->pdo->prepare("
            INSERT INTO utilisateurs (login, password, email, firstname, lastname)
            VALUES (:login, :password, :email, :firstname, :lastname)
        ");
        $stmt->execute([
            ':login'     => $login,
            ':password'  => $hashed,
            ':email'     => $email,
            ':firstname' => $firstname,
            ':lastname'  => $lastname,
        ]);

       
        $this->id        = (int)$this->pdo->lastInsertId();
        $this->login     = $login;
        $this->email     = $email;
        $this->firstname = $firstname;
        $this->lastname  = $lastname;
        $this->connected = true;

        return $this->getAllInfos();
    }

    // Connexion (par login uniquement)
    public function connect(string $login, string $password): bool
    {
        $login = trim($login);

        $stmt = $this->pdo->prepare("SELECT * FROM utilisateurs WHERE login = :login");
        $stmt->execute([':login' => $login]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $this->id        = (int)$user['id'];
            $this->login     = $user['login'];
            $this->email     = $user['email'];
            $this->firstname = $user['firstname'];
            $this->lastname  = $user['lastname'];
            $this->connected = true;
            return true;
        }
        return false;
    }

    public function disconnect(): void
    {
        $this->id = null;
        $this->login = null;
        $this->email = null;
        $this->firstname = null;
        $this->lastname = null;
        $this->connected = false;
    }

    public function delete(): bool
    {
        if (!$this->id) return false;
        $ok = $this->pdo->prepare("DELETE FROM utilisateurs WHERE id = :id")
                        ->execute([':id' => $this->id]);
        $this->disconnect();
        return $ok;
    }

    public function update(string $login, ?string $password, string $email, string $firstname, string $lastname): bool
    {
        if (!$this->id) return false;

       
        $login = trim($login);
        $email = strtolower(trim($email));
        $firstname = trim($firstname);
        $lastname  = trim($lastname);

        if ($this->validateLogin($login)) return false;
        if ($this->validateEmail($email)) return false;
        if ($password !== null && $password !== '' && $this->validatePassword($password)) return false;

        if ($password !== null && $password !== '') {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $sql = "UPDATE utilisateurs
                    SET login = :login, password = :password, email = :email, firstname = :firstname, lastname = :lastname
                    WHERE id = :id";
            $params = [
                ':login' => $login, ':password' => $hashed, ':email' => $email,
                ':firstname' => $firstname, ':lastname' => $lastname, ':id' => $this->id
            ];
        } else {
            $sql = "UPDATE utilisateurs
                    SET login = :login, email = :email, firstname = :firstname, lastname = :lastname
                    WHERE id = :id";
            $params = [
                ':login' => $login, ':email' => $email,
                ':firstname' => $firstname, ':lastname' => $lastname, ':id' => $this->id
            ];
        }

        $ok = $this->pdo->prepare($sql)->execute($params);
        if ($ok) {
            $this->login = $login;
            $this->email = $email;
            $this->firstname = $firstname;
            $this->lastname = $lastname;
        }
        return $ok;
    }


    public function isConnected(): bool { return $this->connected; }
    public function getAllInfos(): array {
        return [
            "id" => $this->id, "login" => $this->login, "email" => $this->email,
            "firstname" => $this->firstname, "lastname" => $this->lastname
        ];
    }
    public function getId(){ return $this->id; }
    public function getLogin(){ return $this->login; }
    public function getEmail(){ return $this->email; }
    public function getFirstname(){ return $this->firstname; }
    public function getLastname(){ return $this->lastname; }

   public function __destruct()
{
    $this->pdo = null;
}
}
