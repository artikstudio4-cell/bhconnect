<?php
/**
 * AuthModel – Version finale stable (InfinityFree OK)
 */

require_once __DIR__ . '/../config/database.php';

class AuthModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /* ============================
       AUTHENTIFICATION
    ============================ */

    public function login($email, $password)
    {
        $stmt = $this->db->prepare("
            SELECT u.id, u.email, u.mot_de_passe, u.role,
                   c.id AS client_id,
                   a.id AS agent_id
            FROM utilisateurs u
            LEFT JOIN clients c ON c.utilisateur_id = u.id
            LEFT JOIN agents a ON a.utilisateur_id = u.id
            WHERE u.email = ? AND u.actif = 1
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user['mot_de_passe'])) {
            return false;
        }

        // Mise à jour dernière connexion
        $this->db->prepare(
            "UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id = ?"
        )->execute([$user['id']]);

        return $user;
    }

    /* ============================
       SESSION & ROLES
    ============================ */

    public function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }

    public function getUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }

    public function getAgentId()
    {
        return $_SESSION['agent_id'] ?? null;
    }

    public function getClientId()
    {
        return $_SESSION['client_id'] ?? null;
    }

    public function isAdmin()
    {
        return ($_SESSION['role'] ?? '') === 'admin';
    }

    public function isAgent()
    {
        return ($_SESSION['role'] ?? '') === 'agent';
    }

    public function isClient()
    {
        return ($_SESSION['role'] ?? '') === 'client';
    }

    /* ============================
       LOGOUT
    ============================ */

    public function logout()
    {
        session_destroy();
        return true;
    }
}
