<?php
require_once __DIR__ . '/../config/database.php';

class QuizModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Vérifier si l'utilisateur a déjà participé
    public function hasParticipated($userId) {
        $stmt = $this->db->prepare("SELECT id, score, statut FROM quiz_participations WHERE utilisateur_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Commencer une participation
    public function startQuiz($userId) {
        // Vérifier d'abord s'il existe déjà une participation
        $existing = $this->hasParticipated($userId);
        if ($existing) {
            return $existing['id'];
        }

        $stmt = $this->db->prepare("INSERT INTO quiz_participations (utilisateur_id, date_debut, statut) VALUES (?, NOW(), 'en_cours')");
        $stmt->execute([$userId]);
        return $this->db->lastInsertId();
    }

    // Récupérer les 60 questions (ou un sous-ensemble aléatoire si besoin)
    public function getQuestions($limit = 60) {
        $stmt = $this->db->prepare("SELECT id, question, option_a, option_b, option_c, option_d, categorie FROM quiz_questions ORDER BY RAND() LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer les bonnes réponses pour le calcul du score
    public function getCorrectAnswers($questionIds) {
        if (empty($questionIds)) return [];
        
        $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
        $stmt = $this->db->prepare("SELECT id, reponse_correcte FROM quiz_questions WHERE id IN ($placeholders)");
        $stmt->execute($questionIds);
        
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['id']] = $row['reponse_correcte'];
        }
        return $result;
    }

    // Sauvegarder les réponses et calculer le score
    public function submitQuiz($userId, $answers) {
        try {
            $this->db->beginTransaction();

            $participation = $this->hasParticipated($userId);
            if (!$participation) {
                throw new Exception("Participation non trouvée.");
            }
            $participationId = $participation['id'];

            // Si déjà terminé, on ne refait pas
            if ($participation['statut'] === 'termine') {
                $this->db->rollBack();
                return $participation['score'];
            }

            // Récupérer les bonnes réponses
            $questionIds = array_keys($answers);
            $correctAnswers = $this->getCorrectAnswers($questionIds);
            
            $score = 0;
            $stmtRep = $this->db->prepare("INSERT INTO quiz_reponses (participation_id, question_id, reponse_donnee, est_correcte) VALUES (?, ?, ?, ?)");

            foreach ($answers as $qId => $rep) {
                // S'assurer que la question existe
                if (!isset($correctAnswers[$qId])) continue;

                $correct = strtoupper(trim($correctAnswers[$qId]));
                $given = strtoupper(trim($rep));

                $isCorrect = ($correct === $given);
                
                if ($isCorrect) {
                    $score++;
                }

                $stmtRep->execute([$participationId, $qId, $rep, $isCorrect ? 1 : 0]);
            }

            // Mettre à jour la participation
            $stmtUpdate = $this->db->prepare("UPDATE quiz_participations SET score = ?, date_fin = NOW(), statut = 'termine' WHERE id = ?");
            $stmtUpdate->execute([$score, $participationId]);

            $this->db->commit();
            return $score;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    // --- ADMIN METHODS ---

    public function getAllQuestions() {
        $stmt = $this->db->query("SELECT * FROM quiz_questions ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getQuestionById($id) {
        $stmt = $this->db->prepare("SELECT * FROM quiz_questions WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function addQuestion($data) {
        $stmt = $this->db->prepare("INSERT INTO quiz_questions (question, option_a, option_b, option_c, option_d, reponse_correcte, categorie) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['question'],
            $data['option_a'],
            $data['option_b'],
            $data['option_c'],
            $data['option_d'],
            $data['reponse_correcte'],
            $data['categorie']
        ]);
        return $this->db->lastInsertId();
    }

    public function updateQuestion($id, $data) {
        $stmt = $this->db->prepare("UPDATE quiz_questions SET question = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, reponse_correcte = ?, categorie = ? WHERE id = ?");
        return $stmt->execute([
            $data['question'],
            $data['option_a'],
            $data['option_b'],
            $data['option_c'],
            $data['option_d'],
            $data['reponse_correcte'],
            $data['categorie'],
            $id
        ]);
    }

    public function deleteQuestion($id) {
        $stmt = $this->db->prepare("DELETE FROM quiz_questions WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getAllParticipations() {
        $sql = "SELECT qp.*, u.email, c.nom, c.prenom, c.telephone 
                FROM quiz_participations qp
                JOIN utilisateurs u ON qp.utilisateur_id = u.id
                LEFT JOIN clients c ON u.id = c.utilisateur_id
                ORDER BY qp.date_fin DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
