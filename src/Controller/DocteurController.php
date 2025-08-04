<?php

namespace App\Controller;

use App\Entity\Docteur;
use App\Repository\DocteurRepository;
use App\Entity\RendezVous;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class DocteurController extends AbstractController
{
    #[Route('/api/docteurs', name: 'app_docteur', methods: ['GET'])]
    public function ListeDocteur(DocteurRepository $docteurRepository, SerializerInterface $serializer): JsonResponse
    {

        $docteurs = $docteurRepository->findAll();
        $jsonDocteur = $serializer->serialize($docteurs, 'json', ['groups' => 'getDocteur']);
        return new JsonResponse($jsonDocteur, Response::HTTP_OK, [], true);
    }

    #[Route('/api/docteur/{id}', name: 'app_docteur_show', methods: ['GET'])]
    public function show(Docteur $docteur, SerializerInterface $serializer): JsonResponse
    {
        $jsonDocteur = $serializer->serialize($docteur, 'json', ['groups' => 'getDocteur']);
        return new JsonResponse($jsonDocteur, Response::HTTP_OK, [], true);
    }

    #[Route('/api/docteur', name: 'app_docteur_create', methods: ['POST'])]
    public function create(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        $docteur = $serializer->deserialize($request->getContent(), Docteur::class, 'json');
        $em->persist($docteur);
        $em->flush();

        $location = $urlGenerator->generate('app_docteur_show', ['id' => $docteur->getId()]);

        $jsonDocteur = $serializer->serialize($docteur, 'json', ['groups' => 'getDocteur']);
        return new JsonResponse($jsonDocteur, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/api/docteur/{id}', name: 'app_docteur_delete', methods: ['DELETE'])]
    public function delete(Docteur $docteur, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($docteur);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
    #[Route('/api/docteur/response/rendezVous', name: 'app_docteur_response_rendezVous', methods: ['POST'])]
    public function postDocteurResponseRendezVous(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        $docteur = $serializer->deserialize($request->getContent(), Docteur::class, 'json');
        $em->persist($docteur);
        $em->flush();

        $location = $urlGenerator->generate('app_docteur_show', ['id' => $docteur->getId()]);

        $jsonDocteur = $serializer->serialize($docteur, 'json', ['groups' => 'getRendezvous']);
        return new JsonResponse($jsonDocteur, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/api/docteur/response/rendezVous', name: 'app_docteur_response_rendezVous', methods: ['GET'])]
    public function getDocteurResponseRendezVous(DocteurRepository $docteurRepository, SerializerInterface $serializer): JsonResponse
    {
        $docteurs = $docteurRepository->findAll();
        $jsonDocteur = $serializer->serialize($docteurs, 'json', ['groups' => 'getRendezvous']);
        return new JsonResponse($jsonDocteur, Response::HTTP_OK, [], true);
    }

    #[Route('/api/docteur/response/rendezVous/{id}', name: 'app_docteur_response_rendezVous_show', methods: ['GET'])]
    public function showDocteurResponseRendezVous(Docteur $docteur, SerializerInterface $serializer): JsonResponse
    {
        $jsonDocteur = $serializer->serialize($docteur, 'json', ['groups' => 'getRendezvous']);
        return new JsonResponse($jsonDocteur, Response::HTTP_OK, [], true);
    }

    #[Route('/api/docteur/{id}/accept', name: 'doctor_rdv-_accept', methods: ['PATCH'])]
    public function accept(RendezVous $rendezVous, EntityManagerInterface $em): JsonResponse
    {
        $rendezVous->setStatut('confirmé');
        $em->flush();

        return new JsonResponse(['message' => 'Rendez-vous accepté.']);
    }

    #[Route('/api/docteur/{id}/refuse', name: 'doctor_rdv_refuse', methods: ['PATCH'])]
    public function refuse(RendezVous $rendezVous, EntityManagerInterface $em): JsonResponse
    {
        $rendezVous->setStatut('annulé');
        $em->flush();

        return new JsonResponse(['message' => 'Rendez-vous refusé.']);
    }

    #[Route('/api/docteur/{id}/reschedule', name: 'doctor_rvd_reschedule', methods: ['PATCH'])]
    public function reschedule(
        Request $request,
        RendezVous $rendezVous,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['date']) || !isset($data['heure'])) {
            return new JsonResponse(['error' => 'Date et heure requises.'], 400);
        }

        try {
            $date = new \DateTimeImmutable($data['date']);
            $heure = new \DateTimeImmutable($data['heure']);  // <-- conversion obligatoire

            $rendezVous->setDateConsultationAt($date);
            $rendezVous->setHeureConsultation($heure);

            $em->flush();

            return new JsonResponse(['message' => 'Rendez-vous reprogrammé.'], 200);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Données invalides.'], 400);
        }
    }
}
