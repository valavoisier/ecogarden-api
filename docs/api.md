# Documentation API — EcoGarden

Base URL : `http://localhost:8000`

---

## Sommaire

- [Authentification](#authentification)
  - [Créer un compte](#créer-un-compte)
  - [Se connecter](#se-connecter)
- [Utilisateurs](#utilisateurs)
  - [Modifier un utilisateur](#modifier-un-utilisateur)
  - [Supprimer un utilisateur](#supprimer-un-utilisateur)
- [Conseils jardin](#conseils-jardin)
  - [Lister tous les conseils](#lister-tous-les-conseils)
  - [Conseils par mois](#conseils-par-mois)
  - [Conseils du mois en cours](#conseils-du-mois-en-cours)
  - [Créer un conseil](#créer-un-conseil)
  - [Modifier un conseil](#modifier-un-conseil)
  - [Supprimer un conseil](#supprimer-un-conseil)
- [Météo](#météo)
  - [Météo de la ville de l'utilisateur connecté](#météo-de-la-ville-de-lutilisateur-connecté)
  - [Météo d'une ville spécifique](#météo-dune-ville-spécifique)
- [Codes de réponse HTTP](#codes-de-réponse-http)
- [En‑têtes requis](#en-têtes-requis)

---

## Authentification

L'API utilise des **JSON Web Tokens (JWT)**. Une fois le token obtenu, il doit être transmis dans chaque requête protégée via l'en-tête HTTP :

```
En‑têtes requis
Authorization: Bearer <token>     (routes protégées)
Accept: application/json
Content-Type: application/json     (uniquement pour POST/PUT)
```

---

### Créer un compte

Crée un nouvel utilisateur. Cette route est publique.

```
POST /api/user
```

**Corps de la requête :**

```json
{
  "email": "utilisateur@exemple.fr",
  "password": "MotDePasse1!",
  "city": "Paris"
}
```

**Contraintes de validation :**

 Champ & Règle

- `email`  Obligatoire, format email valide, unique 
- `password`  Obligatoire, min. 8 caractères, au moins 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial 
- `city`  Obligatoire, entre 2 et 255 caractères 

**Réponse — 201 Created :**

```json
{
  "message": "Utilisateur créé avec succès."
}
```

**Réponse — 422 Unprocessable Entity (erreurs de validation - exemple) :**

```json
{
  "errors": {
    "email": "L'email n'est pas valide.",
    "password": "Le mot de passe doit contenir au moins 8 caractères."
  }
}
```

---

### Se connecter

Authentifie un utilisateur et retourne un token JWT.

```
POST /api/auth
```

**Corps de la requête :**

```json
{
  "email": "utilisateur@exemple.fr",
  "password": "MotDePasse1!"
}
```

**Réponse — 200 OK :**

```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

**Réponse — 401 Unauthorized :**

```json
{
  "code": 401,
  "message": "Invalid credentials."
}
```

---

## Utilisateurs

### Modifier un utilisateur

Met à jour un utilisateur existant. Seuls les champs fournis sont mis à jour.

> **Authentification requise** : `ROLE_ADMIN`

```
PUT /api/user/{id}
```

**Paramètres d'URL :**

 `id`  integer - Identifiant de l'utilisateur 

**Corps de la requête :**

```json
{
  "email": "nouveau@exemple.fr",
  "password": "NouveauMdp1!",
  "city": "Lyon"
}
```

> Tous les champs sont optionnels. Seuls les champs présents sont mis à jour.

**Réponse — 200 OK :**

```json
{
  "message": "Utilisateur mis à jour avec succès."
}
```

**Réponse — 404 Not Found :**

```json
{
  "message": "Utilisateur non trouvé."
}
```

---

### Supprimer un utilisateur

Supprime un utilisateur.

> **Authentification requise** : `ROLE_ADMIN`

```
DELETE /api/user/{id}
```

**Paramètres d'URL :**

 `id`  integer - Identifiant de l'utilisateur 

**Réponse — 200 OK :**

```json
{
  "message": "Utilisateur supprimé avec succès."
}
```

**Réponse — 404 Not Found :**

```json
{
  "message": "Utilisateur non trouvé."
}
```

---

## Conseils jardin
Les conseils jardin sont des recommandations saisonnières associées à un ou plusieurs mois de l’année. Chaque conseil contient un contenu textuel et une liste de mois (1–12) indiquant les périodes de l’année où le conseil est pertinent.
Note : les endpoints utilisent /api/conseils pour les collections et /api/conseil pour les ressources individuelles.

### Lister tous les conseils

Retourne l'ensemble des conseils jardin.

> **Authentification requise** : `ROLE_USER`

```
GET /api/conseils
```

**Réponse — 200 OK :**

```json
[
  {
    "id": 1,
    "contenu": "Plantez vos tomates après les saints de glace.",
    "mois": [5, 6]
  },
  {
    "id": 2,
    "contenu": "Taillez les rosiers avant les premières gelées.",
    "mois": [10, 11]
  }
]
```

---

### Conseils par mois

Retourne les conseils associés à un mois donné.

> **Authentification requise** : `ROLE_USER`

```
GET /api/conseil/{mois}
```

**Paramètres d'URL :**

 `mois`  integer - Numéro du mois (1–12) 

**Exemple :**

```
GET /api/conseil/5
```

**Réponse — 200 OK :**

```json
[
  {
    "id": 1,
    "contenu": "Plantez vos tomates après les saints de glace.",
    "mois": [5, 6]
  }
]
```

> Si aucun conseil n'est trouvé pour ce mois, un tableau vide `[]` est retourné.

---

### Conseils du mois en cours

Retourne les conseils correspondant au mois actuel du calendrier.

> **Authentification requise** : `ROLE_USER`

```
GET /api/conseil/current
```

**Réponse — 200 OK :**

```json
[
  {
    "id": 3,
    "contenu": "Arrosez le matin pour limiter l'évaporation.",
    "mois": [6, 7, 8]
  }
]
```

---

### Créer un conseil

Crée un nouveau conseil jardin.

> **Authentification requise** : `ROLE_ADMIN`

```
POST /api/conseil
```

**Corps de la requête :**

```json
{
  "contenu": "Semez des radis dès le mois de mars.",
  "mois": [3, 4]
}
```

**Contraintes de validation :**

champ & règle
- `contenu`  Obligatoire, non vide 
- `mois`  Tableau d'entiers non vide, chaque valeur entre 1 et 12 

**Réponse — 201 Created :**

```json
{
  "id": 5,
  "contenu": "Semez des radis dès le mois de mars.",
  "mois": [3, 4]
}
```

**Réponse — 422 Unprocessable Entity :**

```json
{
  "errors": {
    "contenu": "Le contenu du conseil est obligatoire.",
    "mois": "Un conseil doit être associé à au moins un mois."
  }
}
```

---

### Modifier un conseil

Met à jour un conseil existant.

> **Authentification requise** : `ROLE_ADMIN`

```
PUT /api/conseil/{id}
```

**Paramètres d'URL :**

 `id`  integer - Identifiant du conseil 

**Corps de la requête :**

```json
{
  "contenu": "Contenu mis à jour.",
  "mois": [4, 5]
}
```

> Les champs non fournis conservent leur valeur actuelle.

**Réponse — 204 No Content** (succès, aucun corps de réponse)

**Réponse — 404 Not Found :**

```json
{
  "message": "Conseil non trouvé."
}
```

---

### Supprimer un conseil

Supprime un conseil existant.

> **Authentification requise** : `ROLE_ADMIN`

```
DELETE /api/conseil/{id}
```

**Paramètres d'URL :**

 `id`  integer - Identifiant du conseil 

**Réponse — 204 No Content** (succès, aucun corps de réponse)

**Réponse — 404 Not Found :**

```json
{
  "message": "Conseil non trouvé."
}
```

---

## Météo

Les données météo sont fournies par l'API [Open-Meteo](https://open-meteo.com) (gratuite, sans clé API). Les résultats sont mis en cache **1 heure** par ville.

### Météo de la ville de l'utilisateur connecté

Retourne la météo actuelle de la ville enregistrée dans le profil de l'utilisateur connecté.

> **Authentification requise** : `ROLE_USER`

```
GET /api/meteo
```

**Réponse — 200 OK :**

```json
{
  "temp": 18.5,
  "humidity": 62,
  "precipitation": 0.0,
  "wind": 12.3,
  "sunrise": "06:47",
  "sunset": "21:12",
  "label": "soleil",
  "city": "Paris"
}
```

**Champs de la réponse :**

Champ              Type      Description 

- `temp`           float     Température actuelle en °C 
- `humidity`       integer   Humidité relative (%) 
- `precipitation`  float     Précipitations en mm 
- `wind`           float     Vitesse du vent en km/h 
- `sunrise`        string    Heure de lever du soleil (HH:mm) 
- `sunset`         string    Heure de coucher du soleil (HH:mm) 
- `label`          string    Condition météo (`soleil`, `nuageux`, `couvert`, `pluie`, `verglas`, `neige`) 
- `city`           string    Nom de la ville 

**Réponse — 400 Bad Request** (aucune ville définie sur le compte) :

```json
{
  "error": "Aucune ville définie pour cet utilisateur."
}
```

---

### Météo d'une ville spécifique

Retourne la météo actuelle pour une ville passée en paramètre d'URL.

> **Authentification requise** : `ROLE_USER`

```
GET /api/meteo/{city}
```

**Paramètres d'URL :**

- `city`  string  Nom de la ville (ex: `Lyon`, `Bordeaux`) 

**Exemple :**

```
GET /api/meteo/Bordeaux
```

**Réponse — 200 OK :**

```json
{
  "temp": 22.1,
  "humidity": 55,
  "precipitation": 0.0,
  "wind": 8.7,
  "sunrise": "06:52",
  "sunset": "21:18",
  "label": "soleil",
  "city": "Bordeaux"
}
```

**Réponse — 404 Not Found** (ville introuvable) :

```json
{
  "error": "Ville introuvable : VilleInconnue"
}
```

**Réponse — 400 Bad Request** (ville vide ou invalide) :

```json
{
  "error": "La ville est obligatoire."
}
```

---

## Codes de réponse HTTP
   Code                         Signification 

- `200 OK`                    Requête réussie 
- `201 Created`               Ressource créée avec succès 
- `204 No Content`            Succès sans corps de réponse 
- `400 Bad Request`           Corps JSON invalide ou paramètre manquant 
- `401 Unauthorized`          Token JWT absent ou expiré 
- `403 Forbidden`             Droits insuffisants (rôle requis non possédé) 
- `404 Not Found`             Ressource introuvable 
- `422 Unprocessable Entity`  Données invalides (erreurs de validation) 
- `500 Internal Server Error` Erreur serveur interne 
