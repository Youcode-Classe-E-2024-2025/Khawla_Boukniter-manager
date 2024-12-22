<?php
class Progression {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function startModule($user_id, $module_id, $course_id) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT IGNORE INTO module_progression 
                (user_id, module_id, course_id) 
                VALUES (?, ?, ?)
            ");
            return $stmt->execute([$user_id, $module_id, $course_id]);
        } catch (PDOException $e) {
            logError("Erreur au démarrage du module : " . $e->getMessage());
            return false;
        }
    }

    public function updateModuleProgression($user_id, $module_id, $progression_percentage) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE module_progression 
                SET progression_percentage = ?, 
                    is_completed = CASE WHEN progression_percentage >= 100 THEN TRUE ELSE FALSE END,
                    completed_at = CASE WHEN progression_percentage >= 100 THEN NOW() ELSE NULL END
                WHERE user_id = ? AND module_id = ?
            ");
            return $stmt->execute([$progression_percentage, $user_id, $module_id]);
        } catch (PDOException $e) {
            logError("Erreur de mise à jour de progression : " . $e->getMessage());
            return false;
        }
    }

    public function getCourseProgression($user_id, $course_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    AVG(progression_percentage) as avg_progression,
                    COUNT(DISTINCT module_id) as total_modules,
                    SUM(CASE WHEN is_completed THEN 1 ELSE 0 END) as completed_modules
                FROM module_progression
                WHERE user_id = ? AND course_id = ?
            ");
            $stmt->execute([$user_id, $course_id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            logError("Erreur de récupération de progression : " . $e->getMessage());
            return null;
        }
    }

    public function getModulesProgression($user_id, $course_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    m.id as module_id,
                    m.titre as module_titre,
                    COALESCE(mp.progression_percentage, 0) as progression_percentage,
                    COALESCE(mp.is_completed, FALSE) as is_completed
                FROM modules m
                LEFT JOIN module_progression mp ON m.id = mp.module_id AND mp.user_id = ?
                WHERE m.course_id = ?
                ORDER BY m.ordre
            ");
            $stmt->execute([$user_id, $course_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            logError("Erreur de récupération des modules : " . $e->getMessage());
            return [];
        }
    }
}
?>
