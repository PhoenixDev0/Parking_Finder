<?php
class Session {
    private $db; //Connexion à la base de données (PDO)
    private $table = 'sessions'; //Nom de table cible

    //Utilisation du pattern "Singleton" pour la connexion à la base de données
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    //Créer une nouvelle session
    public function create(int $userId = null, bool $isGuest = false): string {
        $token = bin2hex(random_bytes(32)); //Génération d'un token sécurisé
        $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME); //Expiration de la session
        //Stockage de l'IP pour valider que la requête provient du même utilisateur
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table} (user_id, token, is_guest, ip_address, user_agent, expires_at) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$userId, $token, $isGuest ? 1 : 0, $ipAddress, $userAgent, $expiresAt]);
        
        return $token;
    }

    //Authentifie une session par son token
    public function findByToken(string $token): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE token = ? AND expires_at > NOW()"
        );
        $stmt->execute([$token]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    //Supprime une session par son token
    public function delete(string $token): bool {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE token = ?");
        return $stmt->execute([$token]);
    }

    //Supprime toutes les sessions d'un utilisateur (dans le cas où il change de mot de passe, signale un vol de compte)
    public function deleteByUserId(int $userId): bool {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }

    //Nettoie les sessions expirées
    public function cleanExpired(): int {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE expires_at < NOW()");
        $stmt->execute();
        return $stmt->rowCount();
    }

    //Utilise le pattern "Sliding Expiration" pour prolonger l'expiration d'une session tant que l'utilisateur est connecté
    public function extend(string $token): bool {
        $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
        $stmt = $this->db->prepare("UPDATE {$this->table} SET expires_at = ? WHERE token = ?");
        return $stmt->execute([$expiresAt, $token]);
    }
}
