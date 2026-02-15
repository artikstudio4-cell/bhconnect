<?php
class FactureModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Récupérer toutes les factures avec filtres
    public function getAll($type = null, $limit = null, $offset = 0) {
        // CORRECTION: 'users' -> 'utilisateurs' et correction des jointures
        // On récupère le nom/prénom depuis la table 'clients' si possible, sinon 'utilisateurs' n'a pas ces champs selon AuthModel
        // AuthModel montre: JOIN clients c ON c.utilisateur_id = u.id
        // Donc on doit joindre 'clients' pour avoir le nom/prénom
        
        $sql = "SELECT f.*, c.nom as client_nom, c.prenom as client_prenom, d.numero_dossier 
                FROM factures f 
                LEFT JOIN utilisateurs u ON f.client_id = u.id 
                LEFT JOIN clients c ON c.utilisateur_id = u.id
                LEFT JOIN dossiers d ON f.dossier_id = d.id";
        
        $params = [];
        if ($type) {
            $sql .= " WHERE f.type = :type";
            $params[':type'] = $type;
        }
        
        $sql .= " ORDER BY f.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer une facture par ID avec ses lignes
    public function getById($id) {
        $sql = "SELECT f.*, c.nom as client_nom, c.prenom as client_prenom, u.email as client_email, 
                       c.telephone as client_telephone, c.adresse as client_adresse,
                       d.numero_dossier, d.type_dossier
                FROM factures f 
                LEFT JOIN utilisateurs u ON f.client_id = u.id 
                LEFT JOIN clients c ON c.utilisateur_id = u.id
                LEFT JOIN dossiers d ON f.dossier_id = d.id 
                WHERE f.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $facture = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($facture) {
            $sqlLignes = "SELECT * FROM facture_lignes WHERE facture_id = :id";
            $stmtLignes = $this->db->prepare($sqlLignes);
            $stmtLignes->execute([':id' => $id]);
            $facture['lignes'] = $stmtLignes->fetchAll(PDO::FETCH_ASSOC);
        }

        return $facture;
    }

    // Créer une facture ou proforma
    public function create($data, $lignes) {
        try {
            $this->db->beginTransaction();

            $numero = $this->generateNumber($data['type']);
            
            $sql = "INSERT INTO factures (numero_facture, type, dossier_id, client_id, date_emission, date_echeance, statut, montant_ht, tva_taux, montant_ttc, remarque) 
                    VALUES (:numero, :type, :dossier_id, :client_id, :date_emission, :date_echeance, :statut, :montant_ht, :tva, :montant_ttc, :remarque)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':numero' => $numero,
                ':type' => $data['type'],
                ':dossier_id' => $data['dossier_id'],
                ':client_id' => $data['client_id'],
                ':date_emission' => $data['date_emission'],
                ':date_echeance' => $data['date_echeance'] ?? null,
                ':statut' => $data['statut'] ?? 'brouillon',
                ':montant_ht' => $data['montant_ht'],
                ':tva' => $data['tva_taux'] ?? 20.00,
                ':montant_ttc' => $data['montant_ttc'],
                ':remarque' => $data['remarque'] ?? null
            ]);
            
            $factureId = $this->db->lastInsertId();

            $sqlLigne = "INSERT INTO facture_lignes (facture_id, description, quantite, prix_unitaire, total_ligne) 
                         VALUES (:facture_id, :desc, :qty, :prix, :total)";
            $stmtLigne = $this->db->prepare($sqlLigne);

            foreach ($lignes as $ligne) {
                $stmtLigne->execute([
                    ':facture_id' => $factureId,
                    ':desc' => $ligne['description'],
                    ':qty' => $ligne['quantite'],
                    ':prix' => $ligne['prix_unitaire'],
                    ':total' => $ligne['total_ligne']
                ]);
            }

            $this->db->commit();
            return $factureId;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // Transformer Proforma en Facture
    public function transformProformaToInvoice($id) {
        $facture = $this->getById($id);
        if (!$facture || $facture['type'] !== 'proforma') {
            return false;
        }

        try {
            $this->db->beginTransaction();
            
            $nouveauNumero = $this->generateNumber('facture');
            
            // On met à jour le type, le numéro et on reset le statut en brouillon
            $sql = "UPDATE factures SET type = 'facture', numero_facture = :new_num, statut = 'brouillon', updated_at = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':new_num' => $nouveauNumero, ':id' => $id]);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function updateStatus($id, $statut) {
        $sql = "UPDATE factures SET statut = :statut WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':statut' => $statut, ':id' => $id]);
    }

    // Générer un numéro de facture (Format: FAC-YYYY-001)
    private function generateNumber($type) {
        $prefix = ($type === 'facture') ? 'FAC' : 'PRO';
        $year = date('Y');
        
        $sql = "SELECT numero_facture FROM factures WHERE numero_facture LIKE :pattern ORDER BY id DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pattern' => "$prefix-$year-%"]);
        $last = $stmt->fetchColumn();

        if ($last) {
            $parts = explode('-', $last);
            $sequence = intval(end($parts)) + 1;
            // Gérer le cas où le dernier numéro n'a pas 3 parties
            if (count($parts) < 3) $sequence = 1;
        } else {
            $sequence = 1;
        }

        return sprintf("%s-%s-%03d", $prefix, $year, $sequence);
    }
}
?>
