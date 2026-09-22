# Back-office du Collectif Hydres

Le site garde son fonctionnement statique, mais l’agenda est alimenté par une API PHP. Le tableau de bord est accessible à l’adresse `https://votre-domaine/admin/`.

## Première installation sur OVH

Cette étape est indispensable : le mot de passe ne doit **jamais** être ajouté au dépôt Git, à un fichier HTML ou à un fichier dans `www`.

1. Connectez-vous au FTP/SSH OVH et créez un dossier `private` au même niveau que le dossier `www`.
2. Dans ce dossier `private`, créez `admin-config.php` avec les permissions les plus restrictives que permet OVH (idéalement `600`). Son contenu est :

```php
<?php
return [
    'username' => 'identifiant-du-collectif',
    'password_hash' => 'COLLER_ICI_LE_HASH',
];
```

3. Générez le hash sur votre ordinateur, sans l’ajouter dans un fichier du projet :

```sh
php -r 'echo password_hash("VOTRE_MOT_DE_PASSE", PASSWORD_DEFAULT), PHP_EOL;'
```

Copiez uniquement le résultat à la place de `COLLER_ICI_LE_HASH`.

4. Vérifiez que la version de PHP sélectionnée pour le domaine est PHP 8.1 ou plus récente, puis déployez le site comme d’habitude.

Au premier accès, l’API crée `private/content.json`. Ce fichier contient les dates et reste hors du répertoire web. Les photos sont conservées dans `www/uploads/`; elles sont publiques pour pouvoir être affichées sur le site, mais seuls les membres connectés peuvent en déposer.

## Utilisation

- Ouvrir `/admin/`, saisir l’identifiant et le mot de passe.
- Ajouter ou modifier une date. Cocher « Afficher dans Prochaines dates » pour l’afficher dans le premier bloc de l’agenda.
- Les champs français alimentent le site français. Les champs anglais, s’ils sont laissés vides, reprennent le français.
- La section Photos sert de médiathèque : elle fournit l’URL sûre d’une image téléversée. Les emplacements d’images déjà intégrés aux pages restent, pour l’instant, des images éditées dans le code.
- Dans « Créations », ajouter le titre, les textes, la photo principale et les photos de la fiche. La création est alors ajoutée automatiquement à l’accueil, au répertoire et à tous les sous-menus « Créations » ; chaque fiche possède sa propre URL.
- Dans « Contenus partagés », modifier les photos de « Un collectif tout terrain » (Accueil et Le collectif) et les lignes de « Ils nous ont accueillis » (Médiation).
- L’ensemble des lignes affichées dans l’Agenda et dans « Prochaines dates » est géré dans le premier bloc du back-office.

## Sécurité

- Le mot de passe est vérifié avec `password_verify()` : seul son hash est conservé hors de `www`.
- La session est régénérée après connexion et son cookie est `HttpOnly`, `SameSite=Strict` et `Secure` en HTTPS.
- Les requêtes d’édition exigent un jeton CSRF ; les tentatives de connexion sont limitées après cinq échecs.
- L’upload n’accepte que JPG, PNG et WebP (5 Mo maximum), avec un nom de fichier aléatoire. Le dossier empêche l’exécution de scripts.

Ne créez pas de lien public vers `/admin/` dans la navigation : l’absence de lien ne remplace pas la connexion, mais réduit les accès accidentels.
