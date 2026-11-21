<?php
class Favorite {
    private $db; //Connexion à la base de données (PDO)
    private $table = 'favorites'; //Nom de table cible

    //Utilisation du pattern "Singleton" pour la connexion à la base de données
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    //Récupère tous les favoris d'un utilisateur
    public function getByUserId(int $userId): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    //Ajoute un favori
    public function add(int $userId, string $parkingId, string $name, float $lat, float $lng): bool {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO {$this->table} (user_id, parking_id, parking_name, latitude, longitude) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            return $stmt->execute([$userId, $parkingId, $name, $lat, $lng]);
        } catch (PDOException $e) {
            //Code SQLSTATE 23000 signifie que la clé est déjà utilisée (duplication)
            if ($e->getCode() == 23000) {
                return false;
            }
            throw $e;
        }
    }

    //Supprime un favori
    public function remove(int $userId, string $parkingId): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->table} WHERE user_id = ? AND parking_id = ?"
        );
        return $stmt->execute([$userId, $parkingId]);
    }

    //Vérifie si un parking est en favori
    public function isFavorite(int $userId, string $parkingId): bool {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE user_id = ? AND parking_id = ?"
        );
        $stmt->execute([$userId, $parkingId]);
        return $stmt->fetchColumn() > 0;
    }

    //Rechercher un favori par son ID
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
}
