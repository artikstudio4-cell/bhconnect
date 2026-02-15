<?php
require_once __DIR__ . '/../models/CreneauModel.php';
require_once __DIR__ . '/../models/AgentModel.php';
require_once __DIR__ . '/../models/ClientModel.php';
require_once __DIR__ . '/../models/EmailService.php';
require_once __DIR__ . '/../config/CSRFToken.php';

class CreneauController {
    private $creneauModel;
    private $agentModel;
    private $clientModel;

    public function __construct() {
        $this->creneauModel = new CreneauModel();
        $this->agentModel = new AgentModel();
        $this->clientModel = new ClientModel();
    }

    public function handleRequest() {
        $message = '';
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CSRFToken::verify($_POST['csrf_token'] ?? '')) {
                $error = 'Jeton de sécurité invalide';
            } elseif (isset($_POST['action'])) {
                // ... logic extracted from gestion-creneaux.php ...
                $action = $_POST['action'];
                switch ($action) {
                    case 'add':
                        $result = $this->handleAdd();
                        if ($result['success']) $message = $result['message'];
                        else $error = $result['error'];
                        break;
                    case 'update':
                        $result = $this->handleUpdate();
                        if ($result['success']) $message = $result['message'];
                        else $error = $result['error'];
                        break;
                    case 'delete':
                        $id = $_POST['id'];
                        if ($this->creneauModel->deleteCreneau($id)) {
                            $message = 'Créneau supprimé avec succès';
                        } else {
                            $error = 'Erreur lors de la suppression du créneau';
                        }
                        break;
                    case 'delete_all':
                        if ($this->creneauModel->deleteAllCreneaux()) {
                            $message = 'Tous les créneaux ont été supprimés avec succès';
                        } else {
                            $error = 'Erreur lors de la suppression de tous les créneaux';
                        }
                        break;
                }
            }
        }

        return ['message' => $message, 'error' => $error];
    }

    private function handleAdd() {
        $date_debut = $_POST['date_debut'] ?? null;
        $heure_fin = $_POST['heure_fin'] ?? null;
        $agent_id = $_POST['agent_id'] ?? null;
        $note = $_POST['note'] ?? null;
        $disponible = isset($_POST['disponible']) ? 1 : 0;

        if ($date_debut && $heure_fin && $agent_id) {
            $start = strtotime($date_debut);
            $end = strtotime($heure_fin);
            
            if ($end > $start) {
                $duree = ($end - $start) / 60;
                
                // Vérification anti-doublon
                $exists = false;
                foreach ($this->creneauModel->getAllCreneaux() as $c) {
                    $c_start = strtotime($c['date_creneau'] . ' ' . $c['heure_debut']);
                    $c_end = strtotime($c['date_creneau'] . ' ' . $c['heure_fin']);

                    if (
                        $c['agent_id'] == $agent_id &&
                        (($start >= $c_start && $start < $c_end) || 
                        ($end > $c_start && $end <= $c_end) || 
                        ($start <= $c_start && $end >= $c_end))
                    ) {
                        $exists = true;
                        break;
                    }
                }

                if ($exists) {
                    return ['success' => false, 'error' => 'Un créneau existe déjà ou chevauche un créneau existant.'];
                }

                $date_creneau = date('Y-m-d', $start);
                $heure_debut_time = date('H:i:s', $start);
                $heure_fin_time = date('H:i:s', $end);
                
                if ($this->creneauModel->addCreneau(null, $agent_id, $date_creneau, $heure_debut_time, $heure_fin_time, $duree, $note, $disponible)) {
                    $this->notifyNewCreneau($agent_id, $start, $end);
                    return ['success' => true, 'message' => 'Créneau ajouté avec succès.'];
                } else {
                    return ['success' => false, 'error' => 'Erreur lors de l\'ajout du créneau.'];
                }
            } else {
                return ['success' => false, 'error' => 'La date de fin doit être postérieure au début.'];
            }
        }
        return ['success' => false, 'error' => 'Veuillez remplir tous les champs obligatoires.'];
    }

    private function handleUpdate() {
        $id = $_POST['id'];
        $note = $_POST['note'] ?? '';
        $duree = $_POST['duree'] ?? 30; // Duree not actually used in update logic in original file? Original code: updateCreneau($id, $note, $duree, $disponible)
        // Original code: $duree = $_POST['duree'] ?? 30;
        // Logic: updateCreneau($id, $note, $duree, $disponible)
        // It seems `duree` was passed.
        $disponible = isset($_POST['disponible']) ? 1 : 0;
        if ($this->creneauModel->updateCreneau($id, $note, $duree, $disponible)) {
            return ['success' => true, 'message' => 'Créneau mis à jour avec succès'];
        }
        return ['success' => false, 'error' => 'Erreur lors de la mise à jour'];
    }

    private function notifyNewCreneau($agent_id, $start, $end) {
        // Notifier l'agent
        if ($agent_id) {
            $agent = $this->agentModel->getAgentById($agent_id);
            if ($agent && !empty($agent['email'])) {
                $sujet = 'Nouveau créneau disponible';
                $contenu = 'Un nouveau créneau a été ajouté à votre planning pour le ' . htmlspecialchars(date('d/m/Y H:i', $start)) . ' à ' . htmlspecialchars(date('H:i', $end)) . '.';
                (new EmailService())->send($agent['email'], $sujet, $contenu);
            }
        }
        // Notifier tous les clients
        $clients = $this->clientModel->getAllClients();
        foreach ($clients as $client) {
            if (!empty($client['email'])) {
                $sujet = 'Nouveau créneau disponible';
                $contenu = 'Un nouveau créneau est disponible le ' . htmlspecialchars(date('d/m/Y H:i', $start)) . ' à ' . htmlspecialchars(date('H:i', $end)) . '. Connectez-vous pour réserver.';
                (new EmailService())->send($client['email'], $sujet, $contenu);
            }
        }
    }
}
