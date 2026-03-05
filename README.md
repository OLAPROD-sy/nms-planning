# NMS Planning

Plateforme web de gestion multi-sites pour le suivi des agents: pointage, planning, stock, alertes et rapports.

## Vue d'ensemble

NMS Planning permet de centraliser les opérations quotidiennes d'exploitation:
- suivi des présences (arrivées, départs, urgences/absences),
- planification des agents par site,
- gestion et traçabilité des stocks,
- visualisation des alertes et des rapports d'inventaire,
- administration des utilisateurs et des sites.

## Fonctionnalités principales

### 1) Pointage et présence
- Pointage d'arrivée et de sortie.
- Signalement d'urgence/absence (courte ou longue).
- Historique des pointages récents.
- Notifications aux responsables (admin/superviseur).
- Contrôle de présence avec géolocalisation côté agent/superviseur.

### 2) Planning des agents
- Création de semaines de travail.
- Création de postes par site.
- Affectation des agents par date, poste et plage horaire.
- Vue planning pour superviseur et vue planning personnel pour agent.

### 3) Gestion de stock
- Gestion des produits et des mouvements (entrée/sortie).
- Suivi des seuils d'alerte.
- Consultation de l'état actuel du stock.
- Historique détaillé des flux.

### 4) Rapports et exports
- Rapport d'inventaire (filtres période/site/type).
- Export des pointages en Excel.
- Exports stock (état courant, inventaire, historique).

### 5) Administration
- Gestion des utilisateurs (création, modification, suppression, activation).
- Gestion des sites.
- Gestion globale des pointages.
- Consultation des notifications.

## Rôles et droits d'accès

| Rôle | Accès principal |
|---|---|
| `ADMIN` | Gestion complète: sites, utilisateurs, pointages globaux, stock global, alertes, rapports |
| `SUPERVISEUR` | Gestion opérationnelle de son site: planning, présence, stock du site |
| `AGENT` | Pointage personnel et consultation de son planning |

## Prérequis techniques

- PHP (avec extensions `pdo_mysql`, `gd`, `mbstring`)
- MySQL
- Composer
- Serveur web (Apache/Nginx) ou serveur PHP local

Les dépendances PHP sont définies dans `composer.json` (dont `dompdf/dompdf` pour les exports PDF/rapports).

## Installation locale (pas à pas)

### 1) Récupérer le projet

```bash
git clone <votre-repo>
cd nms-planning
```

### 2) Installer les dépendances

```bash
composer install
```

### 3) Créer la base de données

Créer une base MySQL, par exemple:

```sql
CREATE DATABASE nms_planning CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

### 4) Importer le schéma/données

Option recommandée (structure + données de référence):

```bash
mysql -u <user> -p nms_planning < nms_structure.sql
```

Option alternative (sauvegarde complète):

```bash
mysql -u <user> -p nms_planning < backup_nms.sql
```

### 5) Configurer la connexion DB

Le projet lit d'abord les variables d'environnement suivantes:
- `MYSQLHOST`
- `MYSQLPORT`
- `MYSQLDATABASE`
- `MYSQLUSER`
- `MYSQLPASSWORD`

Si elles ne sont pas définies, des valeurs de repli sont utilisées dans `config/database.php`.

Exemple local:

```bash
export MYSQLHOST=localhost
export MYSQLPORT=3306
export MYSQLDATABASE=nms_planning
export MYSQLUSER=nms_user
export MYSQLPASSWORD=VotreMotDePasse
```

### 6) Démarrer l'application

Exemple avec serveur PHP local:

```bash
php -S 0.0.0.0:8000
```

Puis ouvrir:
- `http://localhost:8000/auth/login.php`

## Comptes de démonstration (base de démo)

Le formulaire de connexion accepte **username ou email**.

Comptes présents dans le dump de référence (`nms_structure.sql`):
- Admin: `admin` ou `admin@nms.com`
- Superviseur: `supervisor` ou `supervisor@nms.com`
- Agent: `agent` ou `agent@nms.com`

Mot de passe de démo utilisé dans les données d'exemple:
- `password`

### Avertissement sécurité (important)

Ces comptes sont fournis pour un usage **local/de test uniquement**.
En environnement réel:
- remplacez immédiatement tous les mots de passe,
- évitez d'exposer des identifiants de démonstration,
- limitez les accès réseau et activez HTTPS.

## Arborescence utile du projet

```text
admin/      Pages d'administration (utilisateurs, sites, pointages, notifications)
planning/   Planning superviseur + planning agent
stock/      Gestion stock, alertes, exports stock
reports/    Rapports d'inventaire
auth/       Authentification (login)
config/     Connexion DB et configuration
includes/   Header/footer, vérification session, CSRF
assets/     CSS, JS, images
uploads/    Fichiers uploadés (photos, CV)
```

## Flux d'utilisation recommandé

1. Se connecter.
2. Paramétrer les bases (sites, postes, utilisateurs).
3. Créer les semaines et programmer les agents.
4. Réaliser les pointages quotidiens (arrivée/départ/urgence).
5. Suivre les stocks et traiter les alertes.
6. Générer les rapports et exports.

## Exports et rapports disponibles

- Pointages (admin):
  - `/admin/export_pointages_excel.php`
- Stock:
  - `/stock/export_stock.php`
  - `/stock/export_inventaire.php`
  - `/stock/export_current_history.php`
- Rapports inventaire:
  - `/reports/inventory.php`
  - `/reports/export_inventory.php`

## Sécurité et bonnes pratiques

Mécanismes déjà présents:
- Contrôle d'accès basé sur le rôle en session.
- Vérifications d'authentification centralisées.
- Protection CSRF sur les formulaires sensibles.
- Hashage des mots de passe (BCRYPT).

Recommandations production:
- utiliser uniquement des variables d'environnement pour les secrets DB,
- forcer HTTPS,
- appliquer une politique de rotation des mots de passe,
- retirer ou anonymiser les données de démonstration.

## Dépannage rapide

### Erreur de connexion base de données
- Vérifier les variables `MYSQL*`.
- Vérifier que MySQL est démarré.
- Vérifier que la base existe et que l'utilisateur a les droits.

### Données manquantes (utilisateurs/sites)
- Vérifier l'import SQL (`nms_structure.sql` ou `backup_nms.sql`).

### Erreur d'accès / redirection inattendue
- Vérifier le rôle du compte connecté (`ADMIN`, `SUPERVISEUR`, `AGENT`).
- Vérifier la session (déconnexion/reconnexion).

### Problèmes d'upload (photo/CV)
- Vérifier que `uploads/photos` et `uploads/cv` existent.
- Vérifier les permissions d'écriture du serveur web.

## Roadmap courte (suggestions)

- Harmoniser la nomenclature des rôles sur toutes les pages (`SUPERVISEUR`/`SUPERVISOR`).
- Ajouter des tests automatisés (authentification, permissions, flux critiques).
- Ajouter une politique de logs/audit plus détaillée.
- Industrialiser le déploiement (variables d'environnement strictes + CI).
