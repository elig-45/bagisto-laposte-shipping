# Publication sous `elig-45`

Le module est préparé comme dépôt Git autonome ; il n'est pas publié tant que
le dépôt distant n'a pas été créé et que l'authentification GitHub ne donne pas
l'accès en écriture au compte `elig-45`.

1. Créer un dépôt public **vide** `elig-45/bagisto-laposte-shipping` :
   https://github.com/new?name=bagisto-laposte-shipping&visibility=public
   Ne pas précréer `README`, `.gitignore` ou `LICENSE` (ces fichiers existent déjà).
2. Installer/autoriser l'application GitHub utilisée par ChatGPT pour le compte
   personnel `elig-45` avec accès à ce nouveau dépôt ; l'installation sur une
   organisation différente n'accorde pas accès au dépôt personnel.
3. Pour une publication en ligne de commande, à partir du dossier source :

   ```bash
   git remote add origin https://github.com/elig-45/bagisto-laposte-shipping.git
   git push -u origin main
   ```

   Si seuls le fichier `.bundle` et Git sont disponibles :

   ```bash
   git clone bagisto-laposte-shipping-global.bundle bagisto-laposte-shipping
   cd bagisto-laposte-shipping
   git remote add origin https://github.com/elig-45/bagisto-laposte-shipping.git
   git push -u origin main
   ```

Ne pas annoncer une version de production tant que les tests PHPUnit,
les migrations et le checkout Bagisto réel n'ont pas été exécutés.
