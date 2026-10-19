<a id="readme-top"></a>

[![PHP 8.3–8.4](https://img.shields.io/badge/PHP-8.3--8.4-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com/)
[![Bagisto 2.4](https://img.shields.io/badge/Bagisto-2.4-0F766E?style=for-the-badge)](https://bagisto.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg?style=for-the-badge)](LICENSE)

<div align="center">
  <h1 align="center">Bagisto — La Poste Lettre verte suivie</h1>

  <p align="center">
    Méthode d'expédition La Poste pour Bagisto, avec tarification par poids,
    conditionnement mesuré et configuration globale.
    <br />
    <a href="https://github.com/elig-45/bagisto-laposte-shipping/issues">Signaler un problème</a>
    ·
    <a href="https://github.com/elig-45/bagisto-laposte-shipping/issues">Proposer une amélioration</a>
  </p>
</div>

## Sommaire

- [À propos](#à-propos)
- [Fonctionnalités](#fonctionnalités)
- [Technologies](#technologies)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration](#configuration)
- [Utilisation](#utilisation)
- [Tests](#tests)
- [Désinstallation](#désinstallation)
- [Limites connues](#limites-connues)
- [Feuille de route](#feuille-de-route)
- [Contribution](#contribution)
- [Sécurité](#sécurité)
- [Licence](#licence)
- [Contact](#contact)
- [Remerciements](#remerciements)

## À propos

`elig45/bagisto-laposte-shipping` est un package Composer open source pour
**Bagisto 2.4.13**, **Laravel 12** et **PHP 8.3–8.4**. Il ajoute la méthode
`laposte_lettre_suivie_laposte_lettre_suivie` et applique une grille de prix
commune à tous les canaux Bagisto.

> **Statut : version candidate.** Le calcul isolé et la syntaxe PHP sont
> vérifiés. Une installation Bagisto réelle reste nécessaire pour valider les
> migrations, l'administration, les taxes, les devises et le checkout complet.

Le transporteur est désactivé par défaut et ne devient disponible qu'après
confirmation explicite du conditionnement de l'enveloppe.

### Identité du package

| Élément | Valeur |
| --- | --- |
| Package | `elig45/bagisto-laposte-shipping` |
| Namespace | `Elig45\LaPosteShipping` |
| Auteur | `elig-45` |
| Transporteur | `laposte_lettre_suivie` |
| Licence | MIT |

## Fonctionnalités

- Calcul du tarif selon le poids total et la grille applicable.
- Tarifs « classique » et « en ligne », stockés en centimes entiers.
- Conversion correcte des unités Bagisto `kgs` et `lbs`.
- Poids de l'emballage, prix d'achat et majorations configurables.
- Profils de conditionnement explicites par produit ou variante.
- Vérification de la destination, du poids, des dimensions et de l'empilage à plat.
- Grilles tarifaires datées, import/export CSV et historique immuable.
- Contrôles avant totaux du panier et avant enregistrement de la commande.
- Respect des montants déjà figés dans les commandes existantes.
- Aucun achat d'étiquette, aucune API réseau et aucun scraping.

### Règles d'éligibilité

La méthode est disponible uniquement si toutes les conditions suivantes sont
réunies :

1. Le transporteur et la confirmation du conditionnement sont activés.
2. La destination est en France métropolitaine (`FR`, codes `01000`–`95999`,
   Corse incluse).
3. Chaque produit physique possède un poids strictement positif.
4. Chaque référence dispose d'un profil de conditionnement éligible.
5. Les articles tiennent dans l'enveloppe et leur épaisseur cumulée reste
   strictement inférieure à 30 mm.
6. Le poids total ne dépasse pas 2 000 g et les dimensions postales restent
   dans les limites configurées.

Les produits virtuels et téléchargeables ne sont pas comptabilisés. Les
produits `bundle` et `grouped` sont refusés en v1 plutôt que d'approximer leur
conditionnement.

## Technologies

- PHP 8.3–8.4
- Laravel 12 / `illuminate/support`
- Bagisto 2.4.13
- PHPUnit 11
- Orchestra Testbench 10
- Composer

## Prérequis

Avant l'installation, vérifiez que le projet hôte dispose de :

- PHP `>=8.3 <8.5` ;
- Composer ;
- Bagisto 2.4.13 avec Laravel 12 ;
- une base de données configurée et une application Bagisto fonctionnelle.

## Installation

Depuis le dossier de votre application Bagisto :

```bash
composer config repositories.elig45-laposte vcs https://github.com/elig-45/bagisto-laposte-shipping.git
composer require elig45/bagisto-laposte-shipping:^1.0
php artisan optimize:clear
php artisan migrate --force
```

Laravel découvre automatiquement `LaPosteShippingServiceProvider` grâce à
`composer.json`. Aucune modification de `packages/Webkul`,
`bootstrap/providers.php` ou du noyau Bagisto n'est nécessaire.

Pour tester une copie locale avant publication :

```bash
composer config repositories.elig45-laposte path packages/Elig45/LaPosteShipping
composer require elig45/bagisto-laposte-shipping:@dev
```

## Configuration

1. Ouvrez **Administration → Configuration → Ventes → Méthodes d'expédition →
   La Poste – Lettre verte suivie**.
2. Sélectionnez le tarif « classique » ou « en ligne ».
3. Renseignez le poids réel de l'enveloppe et ses dimensions.
4. Configurez si nécessaire le prix de l'enveloppe, les majorations, le seuil
   de gratuité et la politique de poids manquant.
5. Ouvrez **Ventes → La Poste : tarifs et conditionnement**.
6. Créez un profil pour chaque produit ou variante expédiable, avec les
   dimensions de l'article déjà prêt à être conditionné.
7. Activez **J'ai mesuré et vérifié…**, puis **Activer ce transporteur**.
8. Testez une commande non payée sur chaque canal avant la mise en production.

Le paramétrage du transporteur est global. L'unité de poids native de chaque
canal peut rester en `kgs` ou `lbs` : la conversion est effectuée par le
package.

### Gestion des grilles

Les permissions suivantes contrôlent l'administration :

- `sales.laposte` : consultation ;
- `sales.laposte.manage` : modification.

Une grille future peut être créée, importée ou exportée avec les colonnes :

```text
max_weight_g,classic_cents,online_cents,enabled
```

Les grilles passées sont immuables. Une grille doit toujours comporter une
dernière tranche à 2 000 g. Une tranche désactivée rend la méthode indisponible
pour le poids concerné ; elle ne déclenche pas silencieusement une tranche plus
coûteuse.

### Grille initiale

Applicabilité : **1er janvier 2026**. Source :
[tarif Lettre suivie](https://www.laposte.fr/tarif-lettre-suivie).

| Poids maximal inclus | Classique | En ligne |
| ---: | ---: | ---: |
| 20 g | 2,02 € | 1,99 € |
| 100 g | 3,60 € | 3,47 € |
| 250 g | 5,74 € | 5,68 € |
| 500 g | 7,91 € | 7,71 € |
| 1 000 g | 9,79 € | 9,64 € |
| 2 000 g | 11,64 € | 11,56 € |

Le suivi est déjà inclus dans ces montants. Les frais de l'enveloppe et les
majorations sont ajoutés séparément.

## Utilisation

Le package ajoute la méthode d'expédition aux transporteurs Bagisto sans
remplacer les méthodes existantes. Le prix est calculé en devise de base, puis
converti pour l'affichage avec l'API native `core()->convertPrice()`.

Deux contrôles protègent le checkout :

- `checkout.cart.collect.totals.before` actualise le tarif avant les totaux ;
- `checkout.order.save.before` vérifie le montant avant l'enregistrement.

Un montant obsolète provoque une réponse HTTP 409. Si la destination ou le
conditionnement n'est plus éligible, la méthode est retirée du panier.

Le suivi est manuel dans **Ventes → Expéditions**, via le champ natif
`shipment[track_number]`. Le package n'achète aucun affranchissement et
n'interroge pas l'API de suivi La Poste.

## Tests

Exécuter le smoke test sans dépendances :

```bash
php tests/smoke.php
```

Installer les dépendances de développement puis exécuter la suite :

```bash
composer install
vendor/bin/phpunit
```

Vérifier la syntaxe PHP :

```bash
find src tests -name '*.php' -print0 | xargs -0 -n1 php -l
```

Les tests couvrent notamment les seuils de poids, les tarifs, les conversions,
les dimensions invalides, les produits virtuels, la gratuité, les majorations,
les arrondis, les grilles datées et la configuration globale. Ils ne remplacent
pas un test fonctionnel dans une installation Bagisto réelle.

## Désinstallation

1. Désactivez le transporteur et vérifiez qu'aucune commande en cours ne le
   sélectionne.
2. Sauvegardez les grilles historiques et les profils produits.
3. Supprimez le package :

   ```bash
   composer remove elig45/bagisto-laposte-shipping
   php artisan optimize:clear
   ```

4. Pour supprimer les tables, créez une migration applicative dédiée ou
   utilisez `migrate:rollback` uniquement après avoir vérifié l'ordre des
   migrations et effectué une sauvegarde.

Les commandes historiques et leurs totaux restent dans les tables natives
Bagisto.

## Limites connues

- France métropolitaine uniquement : Monaco, Andorre, DOM-COM et international
  sont désactivés.
- Une commande correspond à un seul pli ; aucun découpage automatique au-delà
  de 2 kg.
- Le conditionnement `flat_stack` est volontairement conservateur : aucun
  pliage, rotation oblique, tassement ou placement côte à côte automatique.
- Les dimensions globales de l'enveloppe sont saisies manuellement.
- Aucun achat d'étiquette, dépôt officiel ou suivi automatique.
- Les règles du package ne remplacent pas les conditions générales La Poste en
  vigueur le jour du dépôt.

## Feuille de route

- [ ] Exécuter la validation complète dans une installation Bagisto réelle.
- [ ] Ajouter des tests HTTP d'administration et de checkout.
- [ ] Évaluer la prise en charge future d'autres destinations et formats.
- [ ] Étudier une intégration de suivi uniquement si une API officielle et
      stable est disponible.

Les suggestions et demandes de fonctionnalité sont les bienvenues dans les
[issues GitHub](https://github.com/elig-45/bagisto-laposte-shipping/issues).

## Contribution

Les contributions sont les bienvenues. Pour proposer une modification :

1. Forkez le projet.
2. Créez une branche (`git checkout -b feature/ma-fonctionnalite`).
3. Ajoutez ou mettez à jour les tests concernés.
4. Vérifiez la syntaxe et la suite PHPUnit.
5. Créez une pull request avec une description du comportement attendu.

Merci de limiter chaque pull request à un changement cohérent et de ne jamais
ajouter de secrets, données personnelles ou informations de paiement dans le
code et les logs.

## Sécurité

Pour signaler une vulnérabilité, consultez [SECURITY.md](SECURITY.md) et
n'utilisez pas une issue publique pour transmettre des informations sensibles.

Les routes d'administration utilisent l'authentification `admin` et des
permissions dédiées. Les imports CSV sont validés et les données utilisateur
sont échappées dans les vues Blade.

## Licence

Distribué sous licence MIT. Voir [LICENSE](LICENSE).

Ce projet est indépendant et n'est pas affilié à La Poste.

## Contact

**elig-45**

- GitHub : [@elig-45](https://github.com/elig-45)
- Projet : [elig-45/bagisto-laposte-shipping](https://github.com/elig-45/bagisto-laposte-shipping)

## Remerciements

- [Bagisto](https://bagisto.com/) pour la plateforme e-commerce.
- [Laravel](https://laravel.com/) pour le framework et ses composants.
- [La Poste](https://www.laposte.fr/) pour la grille tarifaire de référence.
- [Best-README-Template](https://github.com/othneildrew/Best-README-Template) pour
  la structure documentaire.

<p align="right">(<a href="#readme-top">retour en haut</a>)</p>
