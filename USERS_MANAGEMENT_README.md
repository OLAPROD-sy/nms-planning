# Documentation - Gestion des Utilisateurs

## Fichiers créés

### 1. `/admin/add_users.php`
Page pour ajouter un nouvel utilisateur avec :
- Formulaire de création (nom, prénom, email, rôle, site, date d'embauche)
- **Upload de photo** : JPEG, PNG, GIF (max 5 MB)
- **Upload de CV** : PDF (max 10 MB)
- Hachage sécurisé du mot de passe avec BCRYPT
- Vérifications CSRF et validations complètes

### 2. `/admin/users.php`
Page de gestion des utilisateurs :
- Liste de tous les utilisateurs
- Affichage du rôle avec couleurs distinctes
- Accès direct aux photos et CV
- Boutons pour modifier ou supprimer
- Protection : impossible de supprimer le dernier admin

### 3. `/admin/edit_users.php`
Page de modification d'un utilisateur :
- Modification de tous les paramètres
- Remplacement de photo/CV avec suppression de l'ancienne version
- Mot de passe modifiable optionnellement
- Aperçu de la photo actuelle et lien vers le CV

## Répertoires créés

### `/uploads/photos/`
- Stockage des photos de profil
- Formats acceptés : JPEG, PNG, GIF
- Taille max : 5 MB
- Permissions : 755

### `/uploads/cv/`
- Stockage des fichiers CV
- Format accepté : PDF
- Taille max : 10 MB
- Permissions : 755

## Table users (structure existante)

Les colonnes suivantes sont utilisées :
```sql
- id_user (INT, PRIMARY KEY)
- nom (VARCHAR 100)
- prenom (VARCHAR 100)
- email (VARCHAR 150, UNIQUE)
- password (VARCHAR 255, BCRYPT)
- role (ENUM: ADMIN, SUPERVISEUR, AGENT)
- id_site (INT, NULLABLE)
- date_embauche (DATE, NULLABLE)
- photo (VARCHAR 255, path relatif)
- cv (VARCHAR 255, path relatif)
- created_at (TIMESTAMP)
```

## Fonctionnalités de sécurité

1. **CSRF Protection** : Tokens CSRF validés sur chaque POST
2. **Authentification** : Seuls les ADMIN peuvent ajouter/modifier les utilisateurs
3. **Validation des fichiers** :
   - Vérification du type MIME
   - Limitation de la taille
   - Nommage aléatoire des fichiers
4. **Hachage mot de passe** : BCRYPT avec salt
5. **Validation email** : Pas de doublons, format valide

## Utilisation

### Ajouter un utilisateur
1. Aller sur `/admin/add_users.php` (bouton depuis le dashboard)
2. Remplir le formulaire
3. Télécharger photo et/ou CV (optionnel)
4. Soumettre

### Modifier un utilisateur
1. Aller sur `/admin/users.php`
2. Cliquer sur "Modifier"
3. Éditer les informations
4. Changer les fichiers si nécessaire
5. Soumettre

### Supprimer un utilisateur
1. Aller sur `/admin/users.php`
2. Cliquer sur "Supprimer"
3. Confirmer la suppression

## Notes importantes

- Les anciens fichiers (photo/CV) sont automatiquement supprimés lors de la mise à jour
- Les fichiers sont stockés en dehors du webroot pour plus de sécurité
- Les chemins sont relatives à `/uploads/` pour faciliter les migrations
- L'email est unique dans la table users
- Le dernier admin ne peut pas être supprimé
