<?php
class User {
    private $db; //Connexion à la base de données (PDO)
    private $table = 'users'; //Nom de table cible

    //Utilisation du pattern "Singleton" pour la connexion à la base de données
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    //Rechercher un utilisateur par son ID
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT id, email, first_name, last_name, created_at FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    //Rechercher un utilisateur par son email
    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ?");
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    //Créer un nouvel utilisateur
    public function create(string $email, string $password, string $firstName = '', string $lastName = ''): int {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (email, password, first_name, last_name) VALUES (?, ?, ?, ?)");
        $stmt->execute([$email, $hashedPassword, $firstName, $lastName]); //hash du mot de passe
        return (int) $this->db->lastInsertId();
    }

    //Mettre à jour l'email d'un utilisateur
    public function updateEmail(int $id, string $email): bool {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET email = ? WHERE id = ?");
        return $stmt->execute([$email, $id]);
    }

    //Mettre à jour le mot de passe d'un utilisateur
    public function updatePassword(int $id, string $password): bool {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE {$this->table} SET password = ? WHERE id = ?");
        return $stmt->execute([$hashedPassword, $id]);
    }

    //Vérifier le mot de passe d'un utilisateur
    public function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    //Vérifier si un email existe déjà
    public function emailExists(string $email, ?int $excludeId = null): bool {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE email = ?";
        $params = [$email];
        
        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }
}
