<?php
require_once __DIR__ . '/../config/database.php';

class DestinationModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Récupérer toutes les destinations
    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM destinations ORDER BY pays ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer une destination par ID
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM destinations WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
