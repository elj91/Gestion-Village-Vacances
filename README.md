# 🌟 Projet VVA - Gestion de Village Vacances 🏖️

## 📋 Présentation

Bienvenue sur le projet VVA, une application web moderne pour la gestion d'un village vacances ! Cette plateforme permet aux vacanciers de consulter et s'inscrire aux animations et activités proposées, tandis que le personnel peut gérer l'ensemble des offres.

## ✨ Fonctionnalités principales

- 🏄‍♂️ **Catalogue d'animations** : Découvrez toutes les animations disponibles
- 📅 **Gestion des activités** : Consultez les détails et horaires des activités
- 📝 **Système d'inscription** : Inscrivez-vous aux activités en quelques clics
- 👮‍♂️ **Interface administrateur** : Gérez l'ensemble des animations, activités et utilisateurs
- 📱 **Design responsive** : Utilisable sur tous les appareils (ordinateur, tablette, mobile)

## 👤 Types d'utilisateurs et leurs rôles

### 👑 Administrateur (`ad`)
- Accès complet à toutes les fonctionnalités du système
- Gestion des comptes utilisateurs et consultation des identifiants
- Ajout, modification et suppression des animations et activités
- Visualisation de tous les participants aux activités
- Supervision globale de la plateforme
- Accès aux statistiques et données d'utilisation

### 👨‍💼 Encadrant (`en`)
- Gestion des animations et activités (ajout, modification, suppression)
- Visualisation des listes de participants aux activités
- Possibilité de modifier les détails d'une activité (horaires, prix, responsable)
- Accès au mode consultation pour voir l'interface vacancier
- Impossible de s'inscrire aux activités (mode consultation uniquement)

### 🏄‍♂️ Vacancier (`va`)
- Consultation du catalogue d'animations
- Visualisation des activités disponibles par animation
- Inscription aux activités (avec vérifications des conditions)
- Accès limité à leur propre compte et inscriptions
- Interface simplifiée centrée sur la découverte et réservation d'activités

## 🔐 Comptes de test

Vous pouvez tester l'application avec les identifiants suivants :

| Type d'utilisateur | Identifiant | Mot de passe | Description |
|-------------------|-------------|--------------|-------------|
| 👑 Administrateur | `ej` | `elyeselyes` | Accès complet au système |
| 👨‍💼 Encadrant | `mh` | `azerty` | Gestion des activités et animations |
| 🏄‍♂️ Vacancier | `aa` | `aa` | Consultation et inscription aux activités |

## 🖥️ Technologies utilisées

- **Frontend** : HTML5, CSS3, JavaScript
- **Backend** : PHP
- **Base de données** : MySQL
- **Librairies** : Font Awesome (icônes)

## 🔧 Installation locale

1. Clonez ce dépôt sur votre serveur web
   ```
   git clone https://github.com/elj91/gestion-village-vacances.git
   ```
2. Importez la base de données depuis le fichier `database.sql`
3. Configurez la connexion à la base de données dans `config/db.php`
4. Accédez à l'application via votre navigateur

## 🌐 Démonstration en ligne

Une version de démonstration est disponible à l'adresse suivante :
**[http://elj.wuaze.com](http://elj.wuaze.com)**

## 👥 Auteur

Projet développé par JAFFEL Elyes

---

© 2025 VVA - Tous droits réservés
