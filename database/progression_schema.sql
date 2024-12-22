-- Table pour suivre la progression des modules par étudiant
CREATE TABLE IF NOT EXISTS module_progression (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    module_id INT NOT NULL,
    course_id INT NOT NULL,
    is_completed BOOLEAN DEFAULT FALSE,
    progression_percentage DECIMAL(5,2) DEFAULT 0.00,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES cours(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_module (user_id, module_id)
);

-- Trigger pour mettre à jour la progression globale du cours
DELIMITER //
CREATE TRIGGER update_course_progression 
AFTER UPDATE ON module_progression
FOR EACH ROW
BEGIN
    -- Calculer la progression globale du cours pour cet étudiant
    UPDATE inscriptions i
    SET i.progression = (
        SELECT AVG(progression_percentage)
        FROM module_progression mp
        WHERE mp.user_id = i.user_id AND mp.course_id = i.course_id
    )
    WHERE i.user_id = NEW.user_id AND i.course_id = NEW.course_id;
END;//
DELIMITER ;

-- Vue pour faciliter la récupération des progressions
CREATE VIEW vue_progression_cours AS
SELECT 
    mp.user_id,
    mp.course_id,
    c.titre AS course_titre,
    m.titre AS module_titre,
    mp.progression_percentage,
    mp.is_completed,
    mp.started_at,
    mp.completed_at
FROM 
    module_progression mp
JOIN modules m ON mp.module_id = m.id
JOIN cours c ON mp.course_id = c.id;
