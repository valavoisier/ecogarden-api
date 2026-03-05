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
}
