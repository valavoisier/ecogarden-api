<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class UserController extends AbstractController
{
    /**
     * Cette méthode permet de créer un nouveau compte utilisateur
     * *
     * Méthode : POST  
     * URL     : /api/user  
     * Accès   : Public
     *
     * Exemple de requête :
     * {
     *   "email": "user@example.com",
     *   "password": "MotDePasse1!",
     *   "city": "Paris"
     * }
     *
     * Exemple de réponse :
     * {
     *   "message": "Utilisateur créé avec succès."
     * }
     *
     * Codes de réponse :
     * - 201 : Utilisateur créé
     * - 400 : JSON invalide
     * - 422 : Erreurs de validation
     *
     * @return JsonResponse
     */    
    #[Route('/api/user', name: 'user_create', methods: ['POST'])]
    public function createUser(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Lecture et décodage du JSON envoyé par le client.
        // Le "true" force un tableau associatif.
        $data = json_decode($request->getContent(), true);
        // Vérification du JSON : si ce n’est pas un tableau → JSON invalide. 
        if (!is_array($data)) {
            return $this->json(['message' => 'JSON invalide.'], Response::HTTP_BAD_REQUEST);//400 Bad Request
        }
        // Création de l’entité User et hydratation des champs avec les données reçues.
        // Le "?? ''" évite les erreurs si une clé est absente.
        $user = new User();
        $user->setEmail($data['email'] ?? '');
        $user->setCity($data['city'] ?? '');
        $user->setRoles(['ROLE_USER']);
        //mot e passe en clair temporairement pour validation des contraintes de l'entité User
        $user->setPassword($data['password'] ?? '');

        // Validation de l'entité (vérification des contraintes définies dans l'entité User)
        $errors = $validator->validate($user);
        // Si des erreurs existent, on renvoie un tableau "champ => message" error 422.
        if (count($errors) > 0) {
            $messages = [];//tableau associatif nom du champ => message d'erreur
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json(['errors' => $messages], Response::HTTP_UNPROCESSABLE_ENTITY);//422 Unprocessable Entity
        }
        // Hashage sécurisé du mot de passe avant sauvegarde.
        $user->setPassword($passwordHasher->hashPassword($user, $data['password']));

        // Persistance en base : persist() marque l'entité pour insertion,
        // flush() exécute réellement les requêtes SQL nécessaires pour enregistrer l'utilisateur.
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json(['message' => "Utilisateur {$user->getId()} créé avec succès."], Response::HTTP_CREATED);//201 Created
    }

    /**     
     * Cette méthode permet d'authentifier un utilisateur et de lui fournir un token JWT pour les requêtes futures.
     * Attention! Cette méthode n'est jamais exécutée directement.
     * Elle est interceptée par le firewall LexikJWT qui :
     * - vérifie les identifiants,
     * - génère le token,
     * - renvoie la réponse JSON.
     *
     * Méthode : POST  
     * URL     : /api/auth  
     * Accès   : Public
     *
     * Exemple de requête :
     * {
     *   "email": "user@example.com",
     *   "password": "MotDePasse1!"
     * }
     *
     * Exemple de réponse succés :
     * {
     *   "token": "eyJhbGciOi..."
     * }
     *
     * @return never
     
     */
    #[Route('/api/auth', name: 'user_auth', methods: ['POST'])]
    public function authUser(): never
    {
        // \LogicException indique que cette méthode ne doit jamais être exécutée directement 
        // ,elle sert uniquement de point d'entrée pour le firewall qui gère l'authentification.
        throw new \LogicException('Cette route est interceptée par le firewall LexikJWT.');
    }

    /**
     * Cette méthode permet de mettre à jour un compte utilisateur existant.
     *
     * Méthode : PUT  
     * URL     : /api/user/{id}  
     * Accès   : ROLE_ADMIN
     *
     * Exemple de requête :
     * {
     *   "email": "nouveau@example.com",
     *   "password": "NouveauMdp1!",
     *   "city": "Lyon"
     * }
     *
     * Exemple de réponse :
     * {
     *   "message": "Utilisateur mis à jour avec succès."
     * }
     *
     * Codes de réponse :
     * - 200 : Mise à jour réussie
     * - 400 : JSON invalide
     * - 404 : Utilisateur non trouvé
     * - 422 : Erreurs de validation
     *
     * @param int $id
     * @return JsonResponse
     */
    #[Route('/api/user/{id}', name: 'user_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN', message: "Vous n'avez pas les droits suffisants pour mettre à jour un utilisateur")]
    public function updateUser(
        int $id,
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Recherche de l'utilisateur à mettre à jour
        $user = $userRepository->find($id);
        // Si aucun utilisateur ne correspond à l'ID → 404.
        if (!$user) {
            return $this->json(['message' => 'Utilisateur non trouvé.'], Response::HTTP_NOT_FOUND);//404 Not Found
        }
        // Lecture et décodage du JSON envoyé par le client.
        // Le second paramètre (true) force un tableau associatif.
        $data = json_decode($request->getContent(), true);
        
        // Vérification du JSON : si ce n’est pas un tableau → JSON invalide.
        if (!is_array($data)) {
            return $this->json(['message' => 'JSON invalide.'], Response::HTTP_BAD_REQUEST);//400 Bad Request
        }

        $messages = [];//tableau associatif stockage erreurs, champ => message d'erreur de validation

        // Mise à jour uniquement des champs présents dans la requête.
        // validateProperty() permet de valider un champ isolé
        // sans valider l'entité complète (et donc sans valider le hash bcrypt existant).
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
            foreach ($validator->validateProperty($user, 'email') as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }
        }
        if (isset($data['city'])) {
            $user->setCity($data['city']);
            foreach ($validator->validateProperty($user, 'city') as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }
        }
        if (isset($data['password'])) {
            // On met temporairement le mot de passe en clair dans l'entité User pour valider les contraintes.
            // On ne peut pas valider l'entité complète car le mot de passe actuel
            // est déjà hashé en bcrypt, et valider ce hash provoquerait des erreurs.
            $user->setPassword($data['password']);
            // Validation uniquement de la propriété "password".
            foreach ($validator->validateProperty($user, 'password') as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }
        }
        // Si des erreurs de validation existent → 422 Unprocessable Entity.
        if (count($messages) > 0) {
            return $this->json(['errors' => $messages], Response::HTTP_UNPROCESSABLE_ENTITY);//422 Unprocessable Entity
        }

        // Si un mot de passe est fourni et validé:
        // On remplace le mot de passe en clair par un hash sécurisé.
        // hashPassword() utilise bcrypt par défaut, qui intègre un salt unique et un coût de calcul élevé
        if (isset($data['password'])) {
            $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        }

        //sauvegarde des modifications en base
        $entityManager->flush();

        return $this->json(['message' => "Utilisateur {$user->getId()} mis à jour avec succès."], Response::HTTP_OK);
    }

    /**
     * Cette méthode permet de supprimer un compte utilisateur existant.
     * 
     * Méthode : DELETE  
     * URL     : /api/user/{id}  
     * Accès   : ROLE_ADMIN
     *
     * Exemple de réponse :
     * {
     *   "message": "Utilisateur supprimé avec succès."
     * }
     *
     * Codes de réponse :
     * - 200 : Suppression réussie
     * - 404 : Utilisateur non trouvé
     *
     * @param int $id
     * @return JsonResponse
     */
    #[Route('/api/user/{id}', name: 'user_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN', message: "Vous n'avez pas les droits suffisants pour supprimer un utilisateur")]
    public function deleteUser(
        int $id,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Recherche de l’utilisateur à supprimer.
        $user = $userRepository->find($id);
        // Si aucun utilisateur ne correspond à l’ID → 404 Not Found.
        if (!$user) {
            return $this->json(['message' => 'Utilisateur non trouvé.'], Response::HTTP_NOT_FOUND);//404 Not Found
        }

        // Suppression de l’entité User.
        // remove() prépare la suppression de l'utilisateur.
        // Comme User n'a aucune relation Doctrine, flush() exécute simplement un DELETE sécurisé.
        $entityManager->remove($user);
        $entityManager->flush();

        return $this->json(['message' => "Utilisateur supprimé avec succès."], Response::HTTP_OK);//200 OK
    }   
}