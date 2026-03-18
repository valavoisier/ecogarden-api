# EcoGarden API

API REST pour l'application EcoGarden — une application de jardinage qui fournit des conseils saisonniers et des données météo en temps réel selon la localisation de l'utilisateur.

## Sommaire

- [Présentation](#présentation)
- [Technologies](#technologies)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration](#configuration)
- [Base de données & Fixtures](#base-de-données--fixtures)
- [Lancer le serveur](#lancer-le-serveur)
- [Authentification JWT](#authentification-jwt)
- [Documentation API](#documentation-api)
- [Sécurité](#sécurité)
---

## Présentation

EcoGarden API est une API REST sécurisée construite avec **Symfony 7.4**. Elle expose trois domaines fonctionnels :

- **Utilisateurs** : inscription, connexion (JWT), gestion des comptes (CRUD: création, lecture, mise à jour, suppression réservées à `ROLE_ADMIN`).
- **Conseils jardinage** : conseils saisonniers associés à une relation ManyToMany `Conseil ↔ Mois`, filtrables par mois (1–12) ou par mois en cours, CRUD réservé à `ROLE_ADMIN`.
- **Météo** : données météo en temps réel via l'API [Open-Meteo](https://open-meteo.com), basées sur la ville de l'utilisateur ou une ville passée en paramètre.


## Technologies utilisées

- PHP >= 8.2
- MariaDB 10.4.32
- Symfony 7.4
- Doctrine ORM ^3.6
- Symfony Security Bundle
- LexikJWT Authentication Bundle ^3.2
- Symfony HTTP Client 7.4
- API Météo externe : Open-Meteo (gratuite, sans clé)
- Caching : Symfony Cache (PSR-6/TagAwareCache)
  - Données météo : 1 heure par ville
  - Conseils jardinage : 24 heures, invalidation automatique sur écriture

## Prérequis

- PHP >= 8.2 
- Composer
- MariaDB 10.4.32
- OpenSSL pour la génération des clés JWT


## Installation

```bash
# 1. Cloner le dépôt
git clone <url-du-repo>
cd ecogarden-api

# 2. Installer les dépendances
composer install
```

---

## Configuration

Copiez le fichier `.env` et adaptez-le selon votre environnement :

```bash
cp .env .env.local
```

Modifiez les variables suivantes dans `.env.local` :

```dotenv
# Base de données
DATABASE_URL="mysql://user:password@127.0.0.1:3306/votre_base_ecogarden?serverVersion=10.4.32-MariaDB&charset=utf8mb4"

# Clés JWT (générées ci-dessous)
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=votre_passphrase
```

### Génération des clés JWT

```bash
php bin/console lexik:jwt:generate-keypair
```

Les clés sont générées dans `config/jwt/`.

---

## Base de données & Fixtures

```bash
# Créer la base de données
php bin/console doctrine:database:create

# Exécuter les migrations
php bin/console doctrine:migrations:migrate

# (Optionnel) Charger les données de test
php bin/console doctrine:fixtures:load
```

---

## Lancer le serveur

```bash
symfony server:start
# ou
php -S localhost:8000 -t public/
```

L'API est accessible à `http://localhost:8000/api`.

---

## Authentification JWT

L'API utilise **JSON Web Tokens (JWT)** pour sécuriser les endpoints.

### Flux d'authentification

1. Créer un compte via `POST /api/user`
2. Obtenir un token via `POST /api/auth`
3. Passer le token dans l'en-tête `Authorization` de toutes les requêtes protégées ainsi que `Content-Type` :

```
Authorization: Bearer <votre_token_jwt>
Content-Type: application/json
```

### Rôles


- `ROLE_USER`  Attribué automatiquement à tout utilisateur inscrit 
- `ROLE_ADMIN`  Accès aux opérations d'écriture (création, modification, suppression) 


## Documentation API

La documentation complète des endpoints est disponible dans [docs/api.md](docs/api.md).

Endpoints principaux :

| Domaine   | Méthode | Endpoint                       | Rôle requis  | Description                        |
|-----------|---------|--------------------------------|--------------|------------------------------------|
| Auth      | POST    | `/api/auth`                    | Public       | Obtenir un token JWT               |
| Users     | POST    | `/api/user`                    | Public       | Inscription                        |
| Users     | GET     | `/api/users`                   | ROLE_ADMIN   | Liste paginée des utilisateurs     |
| Users     | PUT     | `/api/user/{id}`               | ROLE_ADMIN   | Mise à jour d'un utilisateur       |
| Users     | DELETE  | `/api/user/{id}`               | ROLE_ADMIN   | Suppression d'un utilisateur       |
| Conseils  | GET     | `/api/conseil`                 | ROLE_USER    | Conseils du mois en cours (paginé) |
| Conseils  | GET     | `/api/conseil/{mois}`          | ROLE_USER    | Conseils filtrés par mois (paginé) |
| Conseils  | POST    | `/api/conseil`                 | ROLE_ADMIN   | Création d'un conseil              |
| Conseils  | PUT     | `/api/conseil/{id}`            | ROLE_ADMIN   | Modification d'un conseil          |
| Conseils  | DELETE  | `/api/conseil/{id}`            | ROLE_ADMIN   | Suppression d'un conseil           |
| Conseils  | DELETE  | `/api/conseil/cache`          | ROLE_ADMIN   | Vider le cache manuellement        |
| Météo     | GET     | `/api/meteo`                   | ROLE_USER    | Météo de la ville de l'utilisateur |
| Météo     | GET     | `/api/meteo/{city}`            | ROLE_USER    | Météo d'une ville spécifique       |


## Sécurité
- API 100% stateless
- Authentification via JWT
- Rôles utilisateurs (PUBLIC_ACCESS, ROLE_USER, ROLE_ADMIN)
- Routes protégées via #[IsGranted]
- Gestion centralisée des erreurs via ExceptionListener
- Données météo non stockées (appel externe + cache 1h)


## Structure du projet

```
src/
├── Controller/
│   ├── UserController.php      # Gestion des utilisateurs
│   ├── ConseilController.php   # Conseils jardinage
│   └── WeatherController.php   # Météo via Open-Meteo
├── Entity/
│   ├── User.php
│   ├── Conseil.php
│   └── Mois.php                # Entité mois (relation ManyToMany avec Conseil)
├── Repository/
│   ├── UserRepository.php
│   ├── ConseilRepository.php
│   └── MoisRepository.php
├── Service/
│   └── OpenMeteoService.php    # Client HTTP Open-Meteo avec cache
├── DataFixtures/
│   └── AppFixtures.php
└── EventListener/
    └── ExceptionListener.php
config/
├── packages/
│   ├── security.yaml
│   └── lexik_jwt_authentication.yaml
migrations/
```
