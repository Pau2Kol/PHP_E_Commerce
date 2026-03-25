# PHP E-Commerce

Site de vente entre particuliers developpe en PHP.

## Fonctionnalites

- Inscription et connexion avec hash bcrypt, email et username uniques
- Mise en vente d'articles avec photo et quantite en stock
- Page detail par article avec indicateur de stock en temps reel
- Panier avec verification de stock avant commande
- Validation de commande avec adresse de facturation et debit du solde
- Generation de factures consultables sur le profil
- Modification et suppression de ses propres articles
- Recherche d'articles par nom ou description
- Panel administrateur : modification et suppression de tout article ou utilisateur
- Gestion des roles (user / admin) depuis le panel admin

## Prerequis

- PHP 
- MySQL
- XAMPP, MAMP ou LAMP

## Installation

**1. Cloner le depot**

```
git clone https://github.com/Pau2Kol/PHP_E_Commerce.git
cd PHP_E_Commerce
```

**2. Importer la base de donnees**

Ouvrir phpMyAdmin, creer une base de donnees `users`, puis importer le fichier :

```
src/database/db.sql
```

**3. Configurer la connexion**

Ouvrir `src/database/db_connection.php` et verifier les parametres :

```php
$servername = "localhost";
$username   = "root";
$db_pass    = "";        // laisser vide sur XAMPP par defaut, sinon "root"
$dbname     = "users";
```

**4. Lancer le serveur**

Avec XAMPP : placer le dossier dans `htdocs/` et demarrer Apache et MySQL.

Avec le serveur integre PHP (developpement uniquement) :

```
php -S localhost:8000
```


**6. Acceder au site**

Ouvrir `http://localhost/PHP_E_Commerce` (XAMPP) ou `http://localhost:8000` (serveur PHP).

## Acces administrateur

Le role `admin` se definit directement en base de donnees.
Dans phpMyAdmin, editer la ligne de l'utilisateur dans la table `userdata` et passer le champ `role` de `user` a `admin`.
Une fois connecte avec ce compte, le lien Admin apparait dans la navigation.

## Structure du projet

```
PHP_E_Commerce/
  css/
    style.css              # Feuille de style globale
  src/
    database/
      db_connection.php    # Connexion MySQL
      db.sql               # Schema et donnees initiales
    handlers/
      home.php             # Accueil avec recherche
      login.php
      register.php
      logout.php
      profil.php           # Profil et compte (accessible via /profil et /account)
      resetpassword.php
      update_profile.php   # Modification email, mot de passe, photo, solde
      sell.php             # Mise en vente avec quantite
      cart.php             # Panier
      validate.php         # Validation commande + facturation + decrementation stock
      detail.php           # Detail article avec indicateur de stock
      edit.php             # Modification article (auteur ou admin)
      admin.php            # Panel administrateur principal
      admin_edit_user.php  # Sous-page admin : modifier un utilisateur
      admin_edit_article.php # Sous-page admin : modifier un article
  templates/
    header.php
    footer.php
  uploads/                 # Photos de profil
  index.php                # Routeur principal
  .htaccess                # Redirige toutes les requetes vers index.php
```

## Notes techniques

- Toutes les requetes SQL utilisent des requetes preparees pour eviter les injections SQL.
- Les transactions MySQL sont utilisees pour les operations critiques (vente, achat, mise a jour de stock).
- La gestion du stock utilise `ON DUPLICATE KEY UPDATE` pour eviter les doublons dans la table `stock`.
- Les images des articles sont stockees en base de donnees sous forme de blob (LONGBLOB).
