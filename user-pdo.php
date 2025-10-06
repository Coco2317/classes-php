<?php
class Userpdo
{
    private $id;
    public $login;
    public $email;
    public $firstname;
    public $lastname;

    private PDO $pdo;
    private bool $connected = false;

    public function __construct(
        string $dsn  = "mysql:host=localhost;dbname=classes;charset=utf8mb4",
        string $user = "root",
        string $pass = ""
    ) {
        $this->pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    public function register(string $login, string $password, string $email, string $firstname, string $lastname)
    {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $sql = "INSERT INTO utilisateurs (login, password, email, firstname, lastname)
                VALUES (:login, :password, :email, :firstname, :lastname)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':login' => $login,
            ':password' => $hashed,
            ':email' => $email,
            ':firstname' => $firstname,
            ':lastname' => $lastname
        ]);

        $this->id = (int)$this->pdo->lastInsertId();
        $this->login = $login;
        $this->email = $email;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->connected = true;

        return $this->getAllInfos();
    }

    public function connect(string $login, string $password): bool
    {
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateurs WHERE login = :login");
        $stmt->execute([':login' => $login]);
        $user = $stmt->fetch();

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
        $stmt = $this->pdo->prepare("DELETE FROM utilisateurs WHERE id = :id");
        $ok = $stmt->execute([':id' => $this->id]);
        $this->disconnect();
        return $ok;
    }

    public function update(string $login, ?string $password, string $email, string $firstname, string $lastname): bool
    {
        if (!$this->id) return false;

        if ($password !== null && $password !== '') {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $sql = "UPDATE utilisateurs
                    SET login = :login, password = :password, email = :email, firstname = :firstname, lastname = :lastname
                    WHERE id = :id";
            $params = [
                ':login' => $login,
                ':password' => $hashed,
                ':email' => $email,
                ':firstname' => $firstname,
                ':lastname' => $lastname,
                ':id' => $this->id
            ];
        } else {
            $sql = "UPDATE utilisateurs
                    SET login = :login, email = :email, firstname = :firstname, lastname = :lastname
                    WHERE id = :id";
            $params = [
                ':login' => $login,
                ':email' => $email,
                ':firstname' => $firstname,
                ':lastname' => $lastname,
                ':id' => $this->id
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
    public function getLogin()     { return $this->login; }
    public function getEmail()     { return $this->email; }
    public function getFirstname() { return $this->firstname; }
    public function getLastname()  { return $this->lastname; }
}
