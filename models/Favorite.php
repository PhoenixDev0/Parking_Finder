<?php
/**
 * Modèle gérant les parkings favoris des utilisateurs.
 */
class Favorite {
    private $db;
    private $table = 'favorites';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Récupère la liste des favoris d'un utilisateur triés par date décroissante.
     */
    public function getByUserId(int $userId): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Ajoute un parking aux favoris. Retourne false en cas de doublon.
     */
    public function add(int $userId, string $parkingId, string $name, float $lat, float $lng): bool {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO {$this->table} (user_id, parking_id, parking_name, latitude, longitude) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            return $stmt->execute([$userId, $parkingId, $name, $lat, $lng]);
        } catch (PDOException $e) {
            // Le code 23000 signale la violation de la contrainte d'unicité (doublon)
            if ($e->getCode() == 23000) {
                return false;
            }
            throw $e;
        }
    }

    /**
     * Supprime un favori pour un utilisateur donné.
     */
    public function remove(int $userId, string $parkingId): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->table} WHERE user_id = ? AND parking_id = ?"
        );
        return $stmt->execute([$userId, $parkingId]);
    }

    /**
     * Vérifie si un parking est déjà présent dans les favoris de l'utilisateur.
     */
    public function isFavorite(int $userId, string $parkingId): bool {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE user_id = ? AND parking_id = ?"
        );
        $stmt->execute([$userId, $parkingId]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Recherche un enregistrement de favori par son identifiant unique.
     */
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
}
