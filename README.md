[![Symfony](https://img.shields.io/badge/Symfony-000000?style=for-the-badge&logo=symfony)](https://symfony.com)
[![EasyAdmin](https://img.shields.io/badge/EasyAdmin-1A1A1A?style=for-the-badge)](https://github.com/EasyCorp/EasyAdminBundle)
[![TinyMCE](https://img.shields.io/badge/TinyMCE-f7df1e?style=for-the-badge&logo=tinymce&logoColor=white)](https://www.tiny.cloud/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com)


<div align="center">
  
#  $\textsf{\color{blue}{shared gardens occitanie}}$

<br>

### Reprise du projet ["jardins-partages-occitanie"](https://github.com/jbarn9/jardins-partages-occitanie)

👉 **[Visiter le site actuel en WordPress 🔗](https://semeursdejardins34.wordpress.com/)**

</div>

<br><br>

<strong>⚠️ Objectif :</strong> <span style="font-weight:normal;"> Finaliser la refonte du site **débutée en Symfony 7** et lancer en production.</span>

<br>

<div align="center">
<img src="public/img/logos/logo_SDJ.png" alt="Logo SDJ" width="400"/>
</div>

<br>
<br>

<p align="center">
  <a href="https://umap.openstreetmap.fr/fr/map/les-jardins-partages-de-lherault-et-composteurs-co_196132#19/43.617480/3.863076">
     <i>
    Voir la carte des jardins
  </i>
  </a>
</p>

<br>

## 🛠️ Technologies utilisées

### **Cœur du projet**

- **[Symfony 7.1.0](https://symfony.com/)** : Framework PHP full-stack utilisé pour le **backend** et le **frontend** (via Twig et Symfony UX)
- **[PHP 8.3.28](https://www.php.net/)** : Côté serveur.
- **[Composer 2.9.3](https://getcomposer.org/)** : Gestionnaire de dépendances PHP pour installer et mettre à jour les bibliothèques (Symfony, Doctrine, etc.)

### **Base de données**

- **[Doctrine ORM](https://www.doctrine-project.org/projects/orm.html)** : Gestion de la base de données (ORM, migrations, fixtures)
- **MySQL 8.4.7** : SGBDR

### **Frontend & Design**

- **[TinyMCE](https://www.tiny.cloud/)** : Éditeur de texte riche (WYSIWYG) pour la création de contenu
- **[Bootstrap 5](https://getbootstrap.com/)** : Framework CSS pour un design **responsive** et moderne
- **[FullCalendar](https://fullcalendar.io/)** : Gestion des **calendriers** et intégration avec Google Calendar

### **Sécurité & Authentification**

- **[OAuth2](https://oauth.net/2/)** + **[JWT](https://jwt.io/)** : Protocoles pour l’**authentification sécurisée** et la gestion des tokens

### **Administration**

- **[EasyAdmin Bundle](https://github.com/EasyCorp/EasyAdminBundle)** : Interface d’administration "**prête à l’emploi**" pour Symfony

<br>

### 💡 **Bonnes pratiques**

- **Fixtures** : Utiliser `Faker` pour générer des données réalistes dans les fixtures
- **Sécurité** :
  - Ne jamais stocker de mots de passe en clair (utiliser `password_hash` ou Symfony PasswordHasher).
  - Toujours valider les rôles des utilisateurs avant d’autoriser des actions sensibles
- **Performance** :
  - Utiliser le cache Symfony pour les requêtes fréquentes
- **Commandes** :
  - Éviter de forcer les commandes - prendre le temps de trouver l'origine du problème
- **Commenter** :
  - Pour les fichiers interdépendants ou similaires, indiquer le rôle ou le chemin du fichier avant le bloc de code

<br>

### 📋 **Commandes les plus utilisées**

#### **Pour gérer le back-end :**

    ➝ Créer la base de données
        php bin/console doctrine\:database\:create

    ➝ Mettre à jour le schéma (après modification des entités)
        php bin/console doctrine\:schema\:update

    ➝ Générer une migration
        php bin/console make:migration

    ➝ Exécuter les migrations
        php bin/console doctrine\:migrations\:migrate

    ➝ Vérifier l'état des migrations
        php bin/console doctrine:migrations:status

    ➝ Charger les fixtures
        php bin/console doctrine\:fixtures\:load

    ➝ Générer un CRUD avec MakerBundle
        php bin/console make\:crud

#### **Pour voir la liste des routes :**

    ➝ php bin/console debug\:router

#### **Pour vider le cache :**

    ➝ php bin/console cache\:clear

<br>

## ⚙️ Installation pour cette reprise de projet

### Désinstaller Typesense

- composer remove typesense/typesense-php

### Installer

- composer require --dev doctrine/doctrine-fixtures-bundle
- composer require symfony/form\
- composer require --dev orm-fixtures\
- composer require fakerphp/faker --dev\

<br>

## 🗄️ Back-end

    🟩 Créer la base de données
    🟩 Créer les tables
    🟩 Remplir avec les fixtures
    🟩 Forcer l'affichage du pdf contre les erreurs générées par TinyMCE, via un écouteur

<br>

## 🔐 Sécurisation

    🟩 Protéger des injections SQL
    🟩 Générer un token CSRF
    🟩 Mots de passe hachés
    🟩 Chaque rôle peut modifier son mot de passe
    🟩 Contrer les attaques XSS
    🟩 Sécuriser  les uploads de fichiers (PDFs, images)
    🟩 Sécuriser les routes selon les rôles

<br>

## 🌐 Front-end

    🟩 Résoudre les problèmes d'affichages (logo, mantra, articles, PDF) et de navigation
    🟩 Accéder/naviguer dans : l'espace administrateur / l'espace éditeur
    🟩 Résoudre les problèmes de connexion/navigation des onglets Ressources, Calendrier et Carte des jardins
    🟩 Créer la section des notifications (commentaires) : l'éditeur comme modérateur
    🟩 Créer la section de présentation gérée par l'administrateur
    🟩 Insérer une mini-carte du Réseau dans la navbar avec uMAP

<br>

## 🔧 Finaliser le projet

    🟩 Finir le design suivant les consignes
    🟩 Tester/Corriger les bugs
    🟩 Vérifier la sécurité avant de charger la base de données
    🚧 Vider proprement la base de ses fixtures
    🚧 Charger les données de l'association/Tester/Corriger
    🚧 Mettre en production : nom de domaine et hébergeur
    🚧 Tester/Réajuster en production
    🚧 Livrer : fournir outils et identifiants (site, GitHub)