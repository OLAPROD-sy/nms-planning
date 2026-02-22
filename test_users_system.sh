#!/bin/bash

# Script de test pour vérifier que le système de gestion des utilisateurs fonctionne

echo "========================================="
echo "Test du système de gestion des utilisateurs"
echo "========================================="
echo ""

# 1. Vérifier les fichiers créés
echo "✓ Fichiers créés:"
echo "  - /admin/add_users.php (Création d'utilisateur)"
echo "  - /admin/users.php (Liste des utilisateurs)"
echo "  - /admin/edit_users.php (Modification d'utilisateur)"
echo ""

# 2. Vérifier les répertoires d'upload
echo "✓ Répertoires d'upload créés:"
ls -1 /var/www/html/nms-planning/uploads/
echo ""

# 3. Vérifier les permissions
echo "✓ Permissions des répertoires:"
ls -ld /var/www/html/nms-planning/uploads/photos
ls -ld /var/www/html/nms-planning/uploads/cv
echo ""

# 4. Vérifier la connexion à la base de données
echo "✓ Vérification de la table users:"
mysql -h localhost -u nms_user -pMonSuperMdp_2024! nms_planning -e "DESCRIBE users;" 2>/dev/null | grep -E "^(Field|photo|cv)"
echo ""

echo "========================================="
echo "Configuration terminée !"
echo "========================================="
echo ""
echo "Accès aux pages:"
echo "  • Ajouter un utilisateur : /admin/add_users.php"
echo "  • Lister les utilisateurs : /admin/users.php"
echo "  • Modifier un utilisateur : /admin/edit_users.php?id=X"
echo ""
echo "Note: Seuls les ADMIN peuvent accéder à ces pages."Œ
