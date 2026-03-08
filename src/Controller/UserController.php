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
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['message' => 'JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $user = new User();
        //hydratation de l'entité User avec les données reçues (en utilisant l'opérateur de coalescence nulle pour éviter les erreurs si une clé est manquante)
        $user->setEmail($data['email'] ?? '');
        $user->setCity($data['city'] ?? '');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($data['password'] ?? '');

        // validation de l'entité User, si des erreurs sont présentes, on les retourne dans la réponse avec un code 422 Unprocessable Entity
        $errors = $validator->validate($user);

        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json(['errors' => $messages], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->setPassword($passwordHasher->hashPassword($user, $data['password']));

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json(['message' => 'Utilisateur créé avec succès.'], Response::HTTP_CREATED);
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
    #[Route('/api/user/{id}', name: 'user_update', methods: ['PUT'])]
    public function updateUser(
        int $id,
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            return $this->json(['message' => 'Utilisateur non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['message' => 'JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $messages = [];

        // Validation et hydratation uniquement des champs présents dans la requête
        // validateProperty évite de valider le hash bcrypt existant avec les contraintes du mot de passe en clair
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
            $user->setPassword($data['password']);
            foreach ($validator->validateProperty($user, 'password') as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }
        }

        if (count($messages) > 0) {
            return $this->json(['errors' => $messages], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (isset($data['password'])) {
            $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        }

        $entityManager->flush();

        return $this->json(['message' => 'Utilisateur mis à jour avec succès.'], Response::HTTP_OK);
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
    #[Route('/api/user/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function deleteUser(
        int $id,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            return $this->json(['message' => 'Utilisateur non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return $this->json(['message' => 'Utilisateur supprimé avec succès.'], Response::HTTP_OK);
    }
    /**
     * Cette méthode permet de récupérer la liste paginée des utilisateurs (elle est optionnelle!!!)
     *
     * Méthode : GET
     * URL     : /api/users
     * Accès   : ROLE_ADMIN
     *
     * Paramètres de requête :
     * - page  : numéro de page (défaut : 1)
     * - limit : nombre d'éléments par page (défaut : 10, max : 50)
     *
     * Codes de réponse :
     * - 200 : Succès
     * - 403 : Accès refusé
     *
     * @return JsonResponse
     */
    #[Route('/api/users', name: 'user_list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getUsers(UserRepository $userRepository, Request $request): JsonResponse
    {
        $page  = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $users = $userRepository->findAllPaginated($page, $limit);
        $total = $userRepository->countAll();

        return $this->json([
            'data'       => $users,
            'pagination' => [
                'page'  => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit),
            ],
        ]);
    }

}