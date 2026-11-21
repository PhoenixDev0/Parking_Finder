<?php
/**
 * Modèle gérant la persistance des sessions utilisateurs en base de données.
 */
class Session {
    private $db;
    private $table = 'sessions';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Crée une nouvelle session persistante avec jeton aléatoire sécurisé.
     */
    public function create(int $userId = null, bool $isGuest = false): string {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table} (user_id, token, is_guest, ip_address, user_agent, expires_at) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$userId, $token, $isGuest ? 1 : 0, $ipAddress, $userAgent, $expiresAt]);
        
        return $token;
    }

    /**
     * Recherche une session active et non expirée via son jeton.
     */
    public function findByToken(string $token): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE token = ? AND expires_at > NOW()"
        );
        $stmt->execute([$token]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Supprime une session spécifique.
     */
    public function delete(string $token): bool {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE token = ?");
        return $stmt->execute([$token]);
    }

    /**
     * Supprime toutes les sessions associées à un utilisateur.
     */
    public function deleteByUserId(int $userId): bool {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }

    /**
     * Nettoie les sessions expirées de la base de données.
     */
    public function cleanExpired(): int {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE expires_at < NOW()");
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Prolonge la validité de la session (Sliding Expiration).
     */
    public function extend(string $token): bool {
        $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
        $stmt = $this->db->prepare("UPDATE {$this->table} SET expires_at = ? WHERE token = ?");
        return $stmt->execute([$expiresAt, $token]);
    }
}
