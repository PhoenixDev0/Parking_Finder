<?php
/**
 * Modèle gérant les utilisateurs.
 */
class User {
    private $db;
    private $table = 'users';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Recherche un utilisateur par son identifiant.
     */
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT id, email, first_name, last_name, created_at FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Recherche un utilisateur par son adresse email.
     */
    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ?");
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Crée un nouvel utilisateur avec mot de passe haché (BCRYPT).
     */
    public function create(string $email, string $password, string $firstName = '', string $lastName = ''): int {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (email, password, first_name, last_name) VALUES (?, ?, ?, ?)");
        $stmt->execute([$email, $hashedPassword, $firstName, $lastName]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Met à jour l'adresse email d'un utilisateur.
     */
    public function updateEmail(int $id, string $email): bool {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET email = ? WHERE id = ?");
        return $stmt->execute([$email, $id]);
    }

    /**
     * Met à jour le mot de passe d'un utilisateur.
     */
    public function updatePassword(int $id, string $password): bool {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE {$this->table} SET password = ? WHERE id = ?");
        return $stmt->execute([$hashedPassword, $id]);
    }

    /**
     * Vérifie la concordance entre un mot de passe en clair et son empreinte hachée.
     */
    public function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    /**
     * Vérifie si une adresse email est déjà enregistrée.
     */
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
