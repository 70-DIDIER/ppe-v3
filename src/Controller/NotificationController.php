<?php

namespace App\Controller;

use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api')]
class NotificationController extends AbstractController
{

    #[Route('/notifications/{docteurId}', name: 'api_notifications', methods: ['GET'])]
    public function getNotifications(int $docteurId, NotificationRepository $notificationRepository, SerializerInterface $serializer): JsonResponse
    {
        $notifications = $notificationRepository->findBy(['docteur' => $docteurId, 'isRead' => false]);

        $jsonData = $serializer->serialize($notifications, 'json', ['groups' => 'notification:read']);

        return new JsonResponse($jsonData, 200, [], true);
    }
}
