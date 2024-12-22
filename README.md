# Système de Gestion de Formation en Ligne

## Description
Plateforme de gestion de formations en ligne permettant aux administrateurs, formateurs et étudiants d'interagir et de suivre des parcours pédagogiques.

## Prérequis
- PHP 7.4+
- MySQL 5.7+
- Apache 2.4+
- Composer
- Git

## Installation

### 1. Clonage du Repository
```bash
git clone https://github.com/votre-username/khawla_boukniter-manager.git
cd khawla_boukniter-manager
```

### 2. Configuration de la Base de Données
1. Créez une base de données MySQL
2. Importez le script `database/database.sql`
3. Configurez les paramètres de connexion dans `connexion.php`

### 3. Installation des Dépendances
```bash
composer install
```

### 4. Démarrage du Serveur
- Utilisez XAMPP, WAMP ou le serveur PHP intégré
```bash
php -S localhost:8000
```

## Connexion Initiale
- **Admin** : admin@formation.com
- **Mot de passe** : admin123

## Structure du Projet
- `auth/` : Authentification
- `views/` : Interfaces utilisateur
- `includes/` : Fonctions et utilitaires
- `assets/` : Ressources statiques
- `database/` : Scripts SQL
- `docs/` : Diagrammes
- `models/` : Modèles
- `connexion.php` : Configuration de la connexion à la base de données
- `index.php` : Point d'entrée de l'application
- `README.md` : Documentation du projet

## Fonctionnalités
- Gestion des utilisateurs
- Création de cours
- Suivi de progression
- Système de rôles

## Sécurité
- Authentification sécurisée
- Protection CSRF
- Validation des entrées
- Gestion des rôles
