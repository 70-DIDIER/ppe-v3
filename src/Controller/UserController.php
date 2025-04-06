<?php

namespace App\Controller;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserController extends AbstractController
{
    // Route pour récupérer les informations de l'utilisateur connecté
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(Security $security): JsonResponse
    {
        // Récupérer l'utilisateur connecté
        $user = $security->getUser();

        // Si aucun utilisateur n'est connecté
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], 401);
        }

        // Retourner les informations de l'utilisateur
        return new JsonResponse([
            'id' => $user->getUserIdentifier(),
            'email' => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
        ]);
    }
}
