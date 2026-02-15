<?php

class Constants {
    // Roles
    const ROLE_ADMIN = 'admin';
    const ROLE_AGENT = 'agent';
    const ROLE_CLIENT = 'client';

    // Dossier Statuses
    const DOSSIER_NOUVEAU = 'nouveau';
    const DOSSIER_ANALYSE_PRELIMINAIRE = 'Analyse préliminaire';
    const DOSSIER_CONSTITUTION = 'Constitution du dossier';
    const DOSSIER_ATTENTE_RDV = 'Attente RDV';
    const DOSSIER_DEPOT_EFFECTUE = 'Dépôt effectué';
    const DOSSIER_TRAITEMENT = 'Traitement en cours';
    const DOSSIER_VISA_ACCORDE = 'Visa accordé';
    const DOSSIER_VISA_REFUSE = 'Visa refusé';
    const DOSSIER_CLOTURE = 'Clôturé';

    // Rendez-Vous Statuses
    const RDV_PLANIFIE = 'planifie';
    const RDV_CONFIRME = 'confirme';
    const RDV_EFFECTUE = 'effectue';
    const RDV_ANNULE = 'annule';

    // Rendez-Vous Types (Non-exhaustive, but common)
    const RDV_TYPE_PREMIER_CONTACT = 'Premier contact';
    const RDV_TYPE_CONSULTATION = 'Consultation';
    const RDV_TYPE_DEPOT = 'Dépôt de dossier';
    const RDV_TYPE_VISA = 'Remise de visa';

    public static function getDossierStatuses() {
        return [
            self::DOSSIER_NOUVEAU,
            self::DOSSIER_ANALYSE_PRELIMINAIRE,
            self::DOSSIER_CONSTITUTION,
            self::DOSSIER_ATTENTE_RDV,
            self::DOSSIER_DEPOT_EFFECTUE,
            self::DOSSIER_TRAITEMENT,
            self::DOSSIER_VISA_ACCORDE,
            self::DOSSIER_VISA_REFUSE,
            self::DOSSIER_CLOTURE
        ];
    }

    public static function getRdvStatuses() {
        return [
            self::RDV_PLANIFIE,
            self::RDV_CONFIRME,
            self::RDV_EFFECTUE,
            self::RDV_ANNULE
        ];
    }

    public static function getDossierStatusColor($status) {
        return match($status) {
            self::DOSSIER_NOUVEAU => 'info',
            self::DOSSIER_ANALYSE_PRELIMINAIRE => 'primary',
            self::DOSSIER_CONSTITUTION => 'warning',
            self::DOSSIER_ATTENTE_RDV => 'secondary',
            self::DOSSIER_DEPOT_EFFECTUE => 'primary',
            self::DOSSIER_TRAITEMENT => 'info',
            self::DOSSIER_VISA_ACCORDE => 'success',
            self::DOSSIER_VISA_REFUSE => 'danger',
            self::DOSSIER_CLOTURE => 'dark',
            default => 'secondary'
        };
    }

    public static function getRdvStatusColor($status) {
        return match($status) {
            self::RDV_PLANIFIE => 'primary',
            self::RDV_CONFIRME => 'success',
            self::RDV_EFFECTUE => 'info',
            self::RDV_ANNULE => 'danger',
            default => 'secondary'
        };
    }

    public static function getDossierStatusLabel($status) {
        return match($status) {
            self::DOSSIER_NOUVEAU => 'Nouveau',
            default => $status
        };
    }

    public static function getRdvStatusLabel($status) {
        return match($status) {
            self::RDV_PLANIFIE => 'Planifié',
            self::RDV_CONFIRME => 'Confirmé',
            self::RDV_EFFECTUE => 'Effectué',
            self::RDV_ANNULE => 'Annulé',
            default => ucfirst($status)
        };
    }

    /**
     * Retourner le mapping des statuts de dossier pour affichage
     * Utilisé dans les vues pour afficher STATUTS_DOSSIER[$status]
     */
    public static function getDossierStatusesMapping() {
        return [
            self::DOSSIER_NOUVEAU => 'Nouveau',
            self::DOSSIER_ANALYSE_PRELIMINAIRE => 'Analyse préliminaire',
            self::DOSSIER_CONSTITUTION => 'Constitution du dossier',
            self::DOSSIER_ATTENTE_RDV => 'Attente RDV',
            self::DOSSIER_DEPOT_EFFECTUE => 'Dépôt effectué',
            self::DOSSIER_TRAITEMENT => 'Traitement en cours',
            self::DOSSIER_VISA_ACCORDE => 'Visa accordé',
            self::DOSSIER_VISA_REFUSE => 'Visa refusé',
            self::DOSSIER_CLOTURE => 'Clôturé'
        ];
    }

    /**
     * Retourner le mapping des statuts de RDV pour affichage
     */
    public static function getRdvStatusesMapping() {
        return [
            self::RDV_PLANIFIE => 'Planifié',
            self::RDV_CONFIRME => 'Confirmé',
            self::RDV_EFFECTUE => 'Effectué',
            self::RDV_ANNULE => 'Annulé'
        ];
    }
