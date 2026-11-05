# Tournelion

Site vitrine et boutique de Tournelion. Symfony 7.4 LTS, MySQL 8.0, paiement Stripe Checkout, administration EasyAdmin.
Hébergé sur l'offre mutualisée **OVH Perso** (FTP uniquement, pas de SSH).

## Développement local

Tout tourne dans Docker : rien à installer à part Docker.

```bash
docker compose up -d
docker compose exec php composer install
docker compose exec php php bin/console doctrine:migrations:migrate -n
docker compose exec php php bin/console app:create-admin moi@exemple.fr
```

| URL | Quoi |
|---|---|
| http://localhost:8088 | le site |
| http://localhost:8088/admin | l'administration (produits, commandes) |
| http://localhost:8025 | Mailpit : les e-mails envoyés en local arrivent ici |

Commandes utiles :

```bash
docker compose exec php php bin/phpunit                                 # tests
docker compose exec php php bin/console make:migration                  # après modification d'une entité
docker compose exec php php bin/console doctrine:migrations:migrate -n
```

Pour tester un paiement en local, mettre les clés Stripe **de test** dans `.env.local` puis relayer les webhooks avec la
[CLI Stripe](https://docs.stripe.com/stripe-cli) :
`stripe listen --forward-to localhost:8088/stripe/webhook` (elle affiche le `whsec_…` à mettre dans `STRIPE_WEBHOOK_SECRET`).
Carte de test : `4242 4242 4242 4242`, date future, n'importe quel code.

## Fonctionnement de la boutique

1. Le client remplit son panier (stocké en session, pas de compte client).
2. « Payer » crée une commande *En attente* et redirige vers la page de paiement Stripe
   (adresse de livraison et e-mail sont saisis chez Stripe).
3. Stripe appelle `/stripe/webhook` : la commande passe *Payée*, le stock est décrémenté,
   un e-mail part au client et un autre à `SHOP_EMAIL`.
4. Dans l'admin, on passe la commande en *Expédiée* une fois le colis envoyé.

Les prix sont stockés en **centimes** (`2500` = 25,00 €). Un stock vide signifie « illimité ».

## Mise en ligne sur OVH (une seule fois)

### 1. Hébergement

1. Supprimer l'ancien WordPress : vider `www/` en FTP et supprimer sa base (manager → *Bases de données*).
2. Créer une base MySQL (manager → *Bases de données*) et noter hôte, nom, utilisateur, mot de passe.
3. Envoyer `deploy/ovh/.ovhconfig` à la racine FTP de l'hébergement (à côté de `www/`) : il fixe PHP 8.4.
4. Manager → *Multisite* → modifier `tournelion.com` **et** `www.tournelion.com` : dossier racine = **`www/public`**, SSL activé.
5. Créer en FTP le fichier `www/.env.local` :

   ```
   APP_ENV=prod
   APP_DEBUG=0
   APP_SECRET=<32 caractères aléatoires>
   DEFAULT_URI=https://tournelion.com
   DATABASE_URL="mysql://UTILISATEUR:MOTDEPASSE@HOTE.mysql.db:3306/NOMBASE?serverVersion=8.0.32&charset=utf8mb4"
   MAILER_DSN=smtp://contact%40tournelion.com:MOTDEPASSE@ssl0.ovh.net:465
   SHOP_EMAIL=contact@tournelion.com
   STRIPE_SECRET_KEY=sk_live_...
   STRIPE_WEBHOOK_SECRET=whsec_...
   DEPLOY_TOKEN=<longue chaîne aléatoire>
   ```

   (dans `MAILER_DSN`, le `@` de l'adresse s'écrit `%40` ; caractères spéciaux du mot de passe à encoder de la même façon)

### 2. GitHub

Dépôt → *Settings → Secrets and variables → Actions* → ajouter :

| Secret | Valeur |
|---|---|
| `FTP_USER` / `FTP_PASSWORD` | identifiants FTP OVH |
| `DEPLOY_TOKEN` | la même valeur que dans `.env.local` |
| `SITE_URL` | `https://tournelion.com` |

Le serveur FTP (`ftp.cluster100.hosting.ovh.net`) est écrit directement dans le workflow.

Chaque push sur `main` lance `.github/workflows/deploy.yml` : `composer install`, compilation des assets,
envoi FTP, vidage du cache, puis appel de `/_deploy/migrate` qui joue les migrations (pas de SSH sur l'offre Perso).
Le dossier `public/uploads/` (images envoyées depuis l'admin) n'est jamais écrasé par le déploiement.

### 3. Stripe

Dashboard Stripe → *Développeurs → Webhooks* → ajouter un endpoint `https://tournelion.com/stripe/webhook` avec les
événements `checkout.session.completed`, `checkout.session.async_payment_succeeded`, `checkout.session.expired`.
Copier son secret de signature dans `STRIPE_WEBHOOK_SECRET`.

### 4. Premier administrateur

Pas de SSH, donc pas de `bin/console` sur le serveur. Créer le compte en local, puis copier la ligne dans la base de
prod via phpMyAdmin :

```bash
docker compose exec php php bin/console app:create-admin moi@exemple.fr
docker compose exec database mysqldump -uapp -papp --no-create-info tournelion user
```

## Reste à faire

- Frais de port (`shipping_options` dans `src/Service/StripeCheckout.php`, à créer dans le dashboard Stripe).
- Pages légales obligatoires pour une boutique : mentions légales, CGV, politique de confidentialité.
- Le design (`assets/styles/app.css`, `templates/`), dont les images mimosa en fond.
