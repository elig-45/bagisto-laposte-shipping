# Bagisto — La Poste Lettre verte suivie

Package Composer open source pour **Bagisto 2.4.13 / Laravel 12 / PHP 8.3–8.4**, avec configuration globale commune à tous les canaux Bagisto.

**Statut : version candidate, non certifiée en production.** Le calcul isolé et la syntaxe PHP ont été vérifiés ; les tests PHPUnit/Testbench et le checkout complet nécessitent encore une installation Bagisto avec ses dépendances. Le transporteur reste **désactivé par défaut** et exige la confirmation du conditionnement pour l’installation.

- Package : `elig45/bagisto-laposte-shipping`
- Namespace : `Elig45\LaPosteShipping`
- Carrier : `laposte_lettre_suivie`
- Method : `laposte_lettre_suivie_laposte_lettre_suivie`
- Licence : MIT
- Aucune API La Poste, aucun achat d'étiquette automatique et aucune dépendance payante.

## Comportement

Pour un panier contenant des produits expédiables, le module lit le poids natif des lignes Bagisto (configurable : poids de la variante sélectionnée), convertit les kg/lb en **milligrammes entiers**, multiplie par les quantités, ajoute le poids de l'emballage global, puis prend la première tranche dont le plafond est **supérieur ou égal** au poids total. Les produits virtuels et téléchargeables ne sont pas comptabilisés. En v1, les articles composés non pris en charge, notamment `bundle`/`grouped`, **désactivent** la méthode au lieu d'approximer un poids ou une épaisseur.

L'éligibilité exige **simultanément** :

1. Le transporteur est activé globalement **et** les paramètres physiques de l’enveloppe ont été confirmés.
2. L'adresse est `FR`, avec un code postal métropolitain `01000`–`95999` valide selon les préfixes (Corse incluse, Outre-mer/Monaco/Andorre exclus).
3. Chaque produit physique possède un poids strictement positif, sauf si un remplacement positif est expressément configuré (un poids malformé n'est jamais remplacé).
4. Chaque référence a un **profil de conditionnement** explicite et éligible dans l'interface d'administration.
5. L'ensemble des objets peut être empilé à plat, **après conditionnement mesuré**, dans l'enveloppe configurée : la longueur et la largeur de chaque article doivent tenir dans l'enveloppe, et la somme des épaisseurs emballage + unités reste strictement inférieure à 30 mm. Aucun pliage, rotation oblique, tassement ni rangement côte à côte n'est supposé automatiquement.
6. Le poids total ne dépasse pas 2 000 g ; la longueur externe reste ≤60 cm et la somme L+l+h ≤90 cm (volontairement conservateur face à des indications La Poste divergentes entre 90 cm et 100 cm).

Les grilles ne contiennent que les **frais postaux**. Les frais de l'enveloppe et les majorations sont ajoutés séparément. Gratuité sur le sous-total des lignes diminué des réductions, exprimé dans la devise de base (EUR). Arrondi en centimes ; conversion d'affichage par l'API native `core()->convertPrice()`.

## Grille initiale (source officielle, France métropolitaine)

Applicabilité : **1er janvier 2026**. Source : https://www.laposte.fr/tarif-lettre-suivie

| Poids maximal inclus | Classique | En ligne |
|---:|---:|---:|
| 20 g | 2,02 € | 1,99 € |
| 100 g | 3,60 € | 3,47 € |
| 250 g | 5,74 € | 5,68 € |
| 500 g | 7,91 € | 7,71 € |
| 1 000 g | 9,79 € | 9,64 € |
| 2 000 g | 11,64 € | 11,56 € |

Le **suivi est déjà inclus**. Les tarifs sont stockés **en centimes entiers**, par grilles datées avec source. Une seule grille est applicable à tous les canaux. Les grilles passées ne peuvent plus être éditées dans l'interface. Les prix déjà figés dans les commandes Bagisto ne sont jamais réécrits.

## Installation depuis le dépôt GitHub public

À effectuer **après** création du dépôt `https://github.com/elig-45/bagisto-laposte-shipping` et publication d'un tag `v1.0.0` :

```bash
cd /chemin/vers/bagisto
composer config repositories.elig45-laposte vcs https://github.com/elig-45/bagisto-laposte-shipping.git
composer require elig45/bagisto-laposte-shipping:^1.0
php artisan optimize:clear
php artisan migrate --force
```

Laravel découvre automatiquement le `LaPosteShippingServiceProvider` grâce à `composer.json` ; **aucune modification de `packages/Webkul`**, de `bootstrap/providers.php` ou du noyau Bagisto n'est requise. Le transporteur est enregistré par `Config/carriers.php`, ses paramètres par `Config/system.php`.

Pour tester le package sans dépôt GitHub, copiez-le dans `packages/Elig45/LaPosteShipping` et ajoutez temporairement la source de type `path` dans le `composer.json` du projet hôte. Puis utilisez `composer require elig45/bagisto-laposte-shipping:@dev`. Les migrations sont identiques dans les deux modes.

## Configuration globale (toutes les boutiques Bagisto)

1. Dans **Administration → Configuration → Ventes → Méthodes d'expédition → La Poste – Lettre verte suivie**, configurez les réglages **globaux**. Le module ignore la sélection de canal pour ses propres paramètres.
2. Sélectionnez le tarif « classique » ou « en ligne » ; saisissez le **poids réel en grammes** de l'enveloppe (+ protection), ses dimensions, éventuellement son prix d'achat et une majoration. Ces valeurs ne sont jamais supposées connues.
3. Réglez le seuil de gratuité, la politique de poids manquant et, si nécessaire, les titres/descriptions traduits par locale. Les traductions ne varient pas selon le canal.
4. Dans **Ventes → La Poste : tarifs et conditionnement** (`/admin/laposte-shipping` si le préfixe admin n'a pas été modifié), créez un profil pour **chaque produit ou variante expédiable** ; enregistrez les dimensions de l'article déjà prêt à être conditionné. Pour un lot de 20 autocollants vendu comme une référence, les dimensions/poids décrivent **l'ensemble du lot**.
5. Une fois le conditionnement mesuré, activez « J’ai mesuré et vérifié… », puis « Activer ce transporteur » ; enregistrez.
6. Testez avec une commande non payée sur chacun des canaux avant mise en production, puis avec changement de quantité, destination et produits.

**Remarque :** seul le transporteur utilise un paramétrage global. L'unité de poids native Bagisto (`kgs`/`lbs`) peut rester propre au canal ; les poids sont convertis correctement.

### Gestion des grilles

Dans l'administration, les opérations sont limitées aux comptes disposant des permissions `sales.laposte` (consultation) et `sales.laposte.manage` (modification). Vous pouvez créer une grille future, importer/exporter un CSV avec colonnes `max_weight_g,classic_cents,online_cents,enabled`, modifier/supprimer une grille **future**, et prévisualiser un montant postal pour un poids donné. Une tranche désactivée ne fait pas tomber silencieusement vers une tranche plus coûteuse : la méthode est indisponible pour ce poids. La dernière tranche d'une grille doit être à 2 kg.

L'import est limité à un petit fichier CSV, validé côté serveur. Les dates d'application du passé sont immuables, et les grilles antérieures restent disponibles pour audit.

### Suivi des envois

La v1 **n'achète aucun affranchissement**. Dans **Ventes → Expéditions**, saisissez manuellement le numéro de suivi dans le champ natif Bagisto `shipment[track_number]`. Bagisto affiche ce numéro sur la commande dans l'espace client. Pour le consulter : https://www.laposte.fr/outils/suivre-vos-envois. La classe `ShippingApi` est une interface d'extension future, sans implémentation réseau ni scraping.

## Événements checkout et prix figés

- `checkout.cart.collect.totals.before` : revérifie et actualise le tarif sélectionné avant les totaux/fiscalité. Un montant obsolète au moment de la validation donne une réponse HTTP 409 ; si les dimensions ou la destination rendent le service impossible, le tarif est supprimé et Bagisto ne peut pas valider la commande avec cette méthode.
- `checkout.order.save.before` : deuxième contrôle de cohérence du montant présent dans les données de commande, sans mutation d'une commande passée.
- La fiscalité du transport est traitée **uniquement par Bagisto**, sans TVA inventée ni exonération automatique.
- Les autres transporteurs de l'installation sont conservés : notre provider **ajoute** une méthode au tableau `carriers`.

**Important :** certains parcours asynchrones, prestataires de paiement et plugins tiers personnalisent le checkout. Leur intégration, le rendu du prix et le comportement des réponses HTTP 409 doivent être validés **sur le projet hôte réel**, notamment avec HelloAsso.

## Tests

Sans dépendances, exécution du cœur :

```bash
php tests/smoke.php
```

Avec les dépendances de développement :

```bash
composer install
vendor/bin/phpunit
find src tests -name '*.php' -print0 | xargs -0 -n1 php -l
```

Tests fournis : seuils exacts, prix classiques/en ligne, conversions kg/lb, panier de stickers, poids et dimensions invalides, virtuel, DOM-COM, politiques de poids manquant, gratuité, majorations et arrondis, configuration globale, remplacement daté des grilles et immutabilité conceptuelle des commandes. Les tests SQLite utilisent Orchestra Testbench ; ils ne remplacent pas un **test fonctionnel Bagisto en conditions réelles**. Le pipeline GitHub Actions se déclenche à chaque push/PR.

**Non exécutés dans l'environnement de génération :** PHPUnit et Testbench (Composer et `vendor/` absents, extension PDO SQLite non installée), migrations sur MySQL, tests HTTP d'administration, taxes/devise dans un Bagisto réel, commande et checkout complets, non-régression des autres transporteurs.

## Suppression

1. Désactivez le transporteur globalement ; confirmez qu'aucune commande en cours ne le sélectionne.
2. Sauvegardez les grilles historiques et les profils produits.
3. Désinstallez : `composer remove elig45/bagisto-laposte-shipping && php artisan optimize:clear`.
4. Pour supprimer **définitivement** les tables de données, créez une migration applicative dédiée ou faites un `migrate:rollback` **seulement** si le dernier lot n'inclut aucune migration d'un autre package. Ne supprimez jamais les tables sans sauvegarde.

Les commandes historiques et leurs totaux restent dans les tables natives Bagisto, même après la suppression du package.

## Limites volontaires de la v1

- France métropolitaine uniquement, y compris Corse. **Monaco, Andorre, DOM-COM et international désactivés** : aucune extrapolation de tarif.
- Une commande = un pli, **pas de découpage automatique** au-delà de 2 kg.
- Conditionnement `flat_stack` conservateur, sans moteur général de pliage ou de placement d'objets. Les produits `bundle` / `grouped` sont refusés en v1.
- Les profils de conditionnement sont dans une table dédiée, administrables par identifiant produit, plutôt que dans le formulaire produit natif Bagisto. Cela évite de modifier des templates Webkul.
- Aucun achat d'étiquette, aucune confirmation officielle de dépôt, aucune API de suivi automatique.
- Valeurs d'enveloppe globales renseignées manuellement ; des dimensions mal mesurées ou un emballage incompatible invalident les résultats.
- Le contrôle postal ne remplace pas les conditions générales La Poste en vigueur le jour du dépôt.

## Contribution et sécurité

N'introduisez pas d'informations de paiement, d'adresses complètes ou de secrets dans les logs. Le module ne journalise que le type de produit non pris en charge et le constat d'un poids invalide ; aucune donnée nominative n'est requise. Les routes d'administration utilisent l'authentification `admin` et des permissions métier séparées. Les grilles CSV sont validées et les données utilisateur sont échappées dans Blade.

Licence MIT. Projet indépendant, **non affilié à La Poste**.
