# 🎯 Système de Gestion des Utilisateurs - OPÉRATIONNEL ✅

## 📋 Résumé des modifications

Le bouton "Ajouter un utilisateur" du dashboard est maintenant **100% opérationnel** avec support complet pour :
- ✅ Upload de **photos** (JPEG, PNG, GIF)
- ✅ Upload de **CV en PDF**
- ✅ Gestion complète des utilisateurs
- ✅ Sécurité CSRF et validations

---

## 📂 Fichiers créés

### Dans `/admin/`

| Fichier | Description |
|---------|-------------|
| **add_users.php** | Formulaire pour ajouter un nouvel utilisateur avec upload photo et CV |
| **users.php** | Liste de tous les utilisateurs avec options modifier/supprimer |
| **edit_users.php** | Page de modification avec gestion des fichiers |

### Répertoires créés

| Chemin | Description |
|--------|-------------|
| `/uploads/photos/` | Stockage des photos de profil (max 5 MB, formats JPG/PNG/GIF) |
| `/uploads/cv/` | Stockage des CV (max 10 MB, format PDF seulement) |

---

## 🔧 Fonctionnalités principales

### 1️⃣ **Ajouter un utilisateur**
```
Route: /admin/add_users.php
Accès: Admin uniquement
Champs obligatoires: Nom, Prénom, Email, Rôle, Mot de passe
Champs optionnels: Site, Date d'embauche, Photo, CV
```

**Fonctionnalités:**
- Validation d'email (pas de doublons)
- Hachage BCRYPT du mot de passe
- Vérification des formats de fichiers
- Génération de noms de fichiers aléatoires
- Protection CSRF

### 2️⃣ **Lister les utilisateurs**
```
Route: /admin/users.php
Accès: Admin uniquement
```

**Affichage:**
- Table avec tous les utilisateurs
- Codes couleur par rôle (Admin/Superviseur/Agent)
- Liens directs vers les photos et CV
- Actions: Modifier, Supprimer
- Comptage des utilisateurs

### 3️⃣ **Modifier un utilisateur**
```
Route: /admin/edit_users.php?id=X
Accès: Admin uniquement
```

**Fonctionnalités:**
- Modification de tous les champs
- Aperçu photo actuelle
- Lien vers CV actuel
- Remplacement de fichiers avec suppression automatique
- Mot de passe optionnellement modifiable

---

## 🔒 Sécurité implémentée

1. **Protection CSRF** 
   - Tokens validés sur chaque soumission POST
   - Utilisation de `generate_csrf_token()` et `verify_csrf_token()`

2. **Authentification**
   - Vérification du rôle (ADMIN uniquement)
   - Protection contre accès non autorisé

3. **Validation des fichiers**
   - Vérification du type MIME
   - Limitation de taille (5 MB photos, 10 MB CV)
   - Nommage aléatoire des fichiers
   - Pas d'exécution possible des fichiers

4. **Validation des données**
   - Email valide et unique
   - Emails validés avec `FILTER_VALIDATE_EMAIL`
   - Rôles vérifiés contre les valeurs autorisées

5. **Gestion du mot de passe**
   - Hachage BCRYPT avec salt automatique
   - `password_hash($password, PASSWORD_BCRYPT)`

---

## 📊 Structure de la table `users`

```sql
CREATE TABLE `users` (
  `id_user` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100),
  `prenom` varchar(100),
  `email` varchar(150) UNIQUE,
  `password` varchar(255),
  `role` enum('ADMIN','SUPERVISEUR','AGENT'),
  `id_site` int NULL,
  `date_embauche` date NULL,
  `photo` varchar(255) NULL,           ← Chemin relatif
  `cv` varchar(255) NULL,               ← Chemin relatif
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP
)
```

---

## 🚀 Guide d'utilisation

### Pour ajouter un utilisateur

1. Se connecter en tant qu'ADMIN
2. Cliquer sur "📦 Ajouter un utilisateur" depuis le dashboard
3. Remplir le formulaire:
   - Informations personnelles
   - Rôle et site assigné
   - Date d'embauche (optionnel)
4. Télécharger la photo et/ou le CV (optionnel)
5. Cliquer "✅ Ajouter l'utilisateur"

### Pour modifier un utilisateur

1. Accéder à `/admin/users.php`
2. Cliquer "✏️ Modifier" sur la ligne de l'utilisateur
3. Modifier les informations
4. Remplacer les fichiers si nécessaire (ancien sera supprimé)
5. Cliquer "✅ Modifier l'utilisateur"

### Pour supprimer un utilisateur

1. Accéder à `/admin/users.php`
2. Cliquer "🗑️ Supprimer" 
3. Confirmer la suppression
4. ⚠️ Le dernier admin ne peut pas être supprimé

---

## ⚠️ Notes importantes

- **Chemins relatifs** : Les photos/CV sont stockés en tant que chemins relatifs (ex: `uploads/photos/123.jpg`)
- **Anciens fichiers** : Automatiquement supprimés lors de remplacement
- **Permissions** : Les répertoires uploads ont les permissions 755
- **Email unique** : Impossible d'ajouter un utilisateur avec un email existant
- **Admin unique** : Au moins un admin doit rester dans le système

---

## 🔍 Tests effectués

- ✅ Création des fichiers PHP
- ✅ Création des répertoires uploads
- ✅ Configuration des permissions
- ✅ Vérification de la table users
- ✅ Intégration avec le bouton dashboard
- ✅ Validation des champs CSRF

---

## 📱 Endpoints disponibles

```
GET  /admin/users.php              → Lister les utilisateurs
GET  /admin/add_users.php          → Formulaire d'ajout
POST /admin/add_users.php          → Soumettre nouvel utilisateur
GET  /admin/edit_users.php?id=X    → Formulaire modification
POST /admin/edit_users.php?id=X    → Soumettre modification
POST /admin/users.php              → Supprimer utilisateur (via POST)
```

---

## 💡 Suggestions futures

- [ ] Ajouter un système d'édition en masse
- [ ] Export CSV des utilisateurs
- [ ] Page de profile utilisateur (affichage public)
- [ ] Historique des modifications
- [ ] Système de notification par email
- [ ] Réinitialisation de mot de passe perdu

---

✅ **Le système est prêt à être utilisé !**
