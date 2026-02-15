<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/RendezVousModel.php';
require_once __DIR__ . '/DossierModel.php';

class WorkflowService {
    private $rdvModel;
    private $dossierModel;

    public function __construct() {
        $this->rdvModel = new RendezVousModel();
        $this->dossierModel = new DossierModel();
    }

    /**
     * Traite la complétion d'un rendez-vous et met à jour le dossier associé
     */
    public function processRendezVousCompletion($rdvId, $userId) {
        $rdv = $this->rdvModel->getRendezVousById($rdvId);
        
        if (!$rdv || !$rdv['dossier_id']) {
            return; // Rien à faire si pas de dossier lié
        }

        $dossierId = $rdv['dossier_id'];
        $typeRdv = $rdv['type_rendez_vous'];
        $currentDossier = $this->dossierModel->getDossierById($dossierId);
        
        if (!$currentDossier) {
            return;
        }

        $nouveauStatut = null;
        $commentaire = "Statut mis à jour suite au RDV du " . date('d/m/Y', strtotime($rdv['date_heure']));

        // Logique métier : quel RDV déclenche quel statut ?
        switch ($typeRdv) {
            case Constants::RDV_TYPE_PREMIER_CONTACT:
            case Constants::RDV_TYPE_CONSULTATION:
                // Si le dossier est encore "nouveau", on passe à "Analyse préliminaire"
                if ($currentDossier['statut'] === Constants::DOSSIER_NOUVEAU) {
                    $nouveauStatut = Constants::DOSSIER_ANALYSE_PRELIMINAIRE;
                }
                break;

            case Constants::RDV_TYPE_DEPOT:
            case 'Dépôt': // Alias
                // Si le RDV de dépôt est terminé, le dossier est "Dépôt effectué"
                if ($currentDossier['statut'] !== Constants::DOSSIER_DEPOT_EFFECTUE && $currentDossier['statut'] !== Constants::DOSSIER_CLOTURE) {
                    $nouveauStatut = Constants::DOSSIER_DEPOT_EFFECTUE;
                }
                break;

            case 'Entretien':
                // Après un entretien, souvent "Traitement en cours"
                if ($currentDossier['statut'] === Constants::DOSSIER_DEPOT_EFFECTUE) {
                    $nouveauStatut = Constants::DOSSIER_TRAITEMENT;
                }
                break;
                
            case Constants::RDV_TYPE_VISA:
            case 'Retrait':
                if ($currentDossier['statut'] !== Constants::DOSSIER_CLOTURE) {
                    $nouveauStatut = Constants::DOSSIER_VISA_ACCORDE;
                }
                break;
        }

        // Appliquer le changement de statut si pertinent
        if ($nouveauStatut && $nouveauStatut !== $currentDossier['statut']) {
            $this->dossierModel->updateStatut($dossierId, $nouveauStatut, $commentaire, $userId);
        }
    }
}
