CREATE TABLE roles (
	id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(50) NOT NULL,
    description VARCHAR(255) NOT NULL
);
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT true,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);
CREATE TABLE cours (
	id INT PRIMARY KEY AUTO_INCREMENT,
    formateur_id INT NOT NULL,
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    niveau ENUM('debutant', 'intermediaire', 'avance') NOT NULL DEFAULT 'debutant',
    is_active BOOLEAN DEFAULT true,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (formateur_id) REFERENCES users(id)
);

CREATE TABLE modules (
	id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    ordre INT NOT NULL DEFAULT 0,
    FOREIGN KEY (course_id) REFERENCES cours(id)
);

CREATE TABLE inscriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    progression INT DEFAULT 0,
    status ENUM('en_attente', 'accepte', 'refuse') DEFAULT 'en_attente',
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (course_id) REFERENCES cours(id),
    UNIQUE KEY unique_inscription (user_id, course_id)
);

-- Insertion des rôles
INSERT INTO roles (nom, description) VALUES
('admin', 'Administrateur du système'),
('formateur', 'Formateur des cours'),
('etudiant', 'Étudiant inscrit aux cours');

-- Insertion des utilisateurs
INSERT INTO users (role_id, nom, prenom, email, password) VALUES
(1, 'Admin', 'System', 'admin@formation.com', 'admin123'),
(2, 'formateur1', 'Jean', 'formateur1@formation.com', 'formateur123'),
(2, 'formateur2', 'Marie', 'formateur2@formation.com', 'formateur456'),
(3, 'etudiant1', 'Pierre', 'etudiant1@formation.com', 'etudiant123'),
(3, 'etudiant2', 'Sophie', 'etudiant2@formation.com', 'etudiant456');

-- Insertion des cours
INSERT INTO cours (formateur_id, titre, description, niveau) VALUES
(2, 'Introduction à PHP', 'Cours de base pour débutants en PHP', 'debutant'),
(2, 'JavaScript Avancé', 'Maîtrisez les concepts avancés de JavaScript', 'avance'),
(3, 'HTML/CSS pour débutants', 'Apprenez les bases du développement web', 'debutant'),
(3, 'React Intermédiaire', "Développement d'applications avec React", 'intermediaire');

-- Insertion des modules
INSERT INTO modules (course_id, titre, description, ordre) VALUES
(1, 'Variables et Types', 'Introduction aux variables PHP', 1),
(1, 'Structures de contrôle', 'If, else, switch, boucles', 2),
(2, 'Promises', 'Gestion asynchrone en JavaScript', 1),
(2, 'ES6+', 'Fonctionnalités modernes de JavaScript', 2),
(3, 'Structure HTML', 'Bases du HTML5', 1),
(3, 'Styling CSS', 'Mise en forme avec CSS3', 2);

-- Insertion des inscriptions
INSERT INTO inscriptions (user_id, course_id, progression, status) VALUES
(4, 1, 30, 'accepte'),
(4, 2, 0, 'en_attente'),
(5, 1, 50, 'accepte'),
(5, 3, 20, 'accepte');