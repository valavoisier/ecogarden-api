<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class UserController extends AbstractController
{
    /**
     * Cette méthode permet de créer un nouveau compte utilisateur
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
     * L'authentification est gérée par le firewall LexikJWT(gère vérification du mot de passe, génération du token, réponse JSON) — cette route ne sera jamais exécutée directement.
     * Corps de la requête attendu : { "email": "...", "password": "..." }
     * Réponse en cas de succès : { "token": "..." }
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
     * Corps de la requête attendu : { "email": "...", "password": "...", "city": "..." }
     * Réponse en cas de succès : { "message": "Utilisateur mis à jour avec succès." }
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
     * Réponse en cas de succès : { "message": "Utilisateur supprimé avec succès." }
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
}