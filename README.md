[![](https://img.shields.io/badge/Symfony-black?style=for-the-badge)](https://github.com/hamzamohdzubair/redant)
[![](https://img.shields.io/badge/PHP-blue?style=for-the-badge)](https://hamzamohdzubair.github.io/redant/)
[![](https://img.shields.io/badge/Bootstrap-blueviolet?style=for-the-badge)](https://hamzamohdzubair.github.io/redant/)

<div align="center">
  
#  $\textsf{\color{blue}{shared gardens occitanie}}$
### Reprise du projet ["jardins-partages-occitanie"](https://github.com/jbarn9/jardins-partages-occitanie)
<br/>
</div>

<div align="center">

<img src="public/img/logos/logo_SDJ.png" alt="Logo SDJ" width="400"/>
</div>

<br>
<br>

<p align="center">
  <a href="https://umap.openstreetmap.fr/fr/map/les-jardins-partages-de-lherault-et-composteurs-co_196132#19/43.617480/3.863076">
     <i>
    Voir la carte des jardins en plein écran
  </i>
  </a>
</p>


## ⚙️ Installation

### > Vérifier

- composer 2.9.3
- symfony 7.1.0
- php 8.3.28
- MySQL 8.4.7

### > Désinstaller Typesense

- composer remove typesense/typesense-php

### > Installer

- composer require --dev doctrine/doctrine-fixtures-bundle
- composer require symfony/form\
- composer require --dev orm-fixtures\
- composer require fakerphp/faker --dev\

<br>

## 🗄️ Back-end

- 🟩 **Créer la base de données**
- 🟩 **Créer les tables**
- 🟩 **Remplir avec les fixtures**
- 🟩 **Forcer l'affichage du pdf contre les erreurs générées par TinyMCE, via un écouteur**

<br>

## 🌐 Front-end

- 🟩 Résoudre les problèmes d'affichages (logo, mantra, articles, PDF) et de navigation
- 🟩 Accéder/naviguer dans : l'espace administrateur / l'espace éditeur
- 🟩 Résoudre les problèmes de connexion/navigation des onglets Ressources, Calendrier et Carte des jardins
- 🟩 Créer la section des notifications (commentaires) : l'éditeur comme modérateur 
- 🟩 Créer la section de présentation gérée par l'administrateur
- 🟩 Insérer une mini-carte du Réseau dans la navbar avec uMAP

<br>

## 🔧 Finaliser le projet

- 🟩 Finir le design suivant les consignes
- 🚧 Tester/Corriger les bugs
- 🚧 Charger/Tester/Corriger la base
- 🚧 Mettre en production/Tester : nom de domaine et hébergeur


