<?php
class User
{
    private $id;
    public $login;
    public $email;
    public $firstname;
    public $lastname;

    private $conn;

    // Constructeur
    public function __construct()
    {
        $this->conn = new mysqli("localhost", "root", "", "classes");
        if ($this->conn->connect_error) {
            die("Erreur de connexion: " . $this->conn->connect_error);
        }
    }
}
