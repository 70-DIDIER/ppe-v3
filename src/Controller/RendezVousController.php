<?php

namespace App\Controller;

use App\Entity\RendezVous;
use App\Repository\RendezVousRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class RendezVousController extends AbstractController
{
    #[Route('/api/rendezVous', name:"app_create_rendezVous", methods: ['POST'])]
    public function createRendezVous(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator): JsonResponse 
    {

        $rendezVous = $serializer->deserialize($request->getContent(), RendezVous::class, 'json', ['groups' => 'getRendezVous']);
        $em->persist($rendezVous);
        $em->flush();

        $jsonRendezVous = $serializer->serialize($rendezVous, 'json');
        
        $location = $urlGenerator->generate('app_rendezvous_show', ['id' => $rendezVous->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonRendezVous, Response::HTTP_CREATED, ["Location" => $location], true);
   }

   #[Route('/api/rendezVous/{id}', name:"app_rendezvous_show", methods: ['GET'])]
   public function showRendezVous(RendezVous $rendezVous, SerializerInterface $serializer): JsonResponse
   {
       $jsonRendezVous = $serializer->serialize($rendezVous, 'json', ['group' => 'getRendezVous']);

       return new JsonResponse($jsonRendezVous, Response::HTTP_OK, [], true);
   }

   #[Route('/api/rendezVous', name:"app_rendezvous_liste", methods: ['GET'])]
   public function indexRendezVous(RendezVousRepository $rendezVousRepository, SerializerInterface $serializer): JsonResponse
   {
       $rendezVous = $rendezVousRepository->findAll();
       $jsonRendezVous = $serializer->serialize($rendezVous, 'json', ['group' => 'getRendezVous']);

       return new JsonResponse($jsonRendezVous, Response::HTTP_OK, [], true);
   }

   #[Route('/api/rendezVous/{id}', name:"app_rendezvous_update", methods: ['PUT'])]
   public function updateRendezVous(RendezVous $rendezVous, Request $request, SerializerInterface $serializer, EntityManagerInterface $em): JsonResponse
   {
       $data = json_decode($request->getContent(), true);

       $rendezVous->setDescription($data['description'])
               ->setDateConsultationAt($data['date'])
               ->setHeureConsultation($data['heure'])
               ->setPatient($data['patient'])
               ->setDocteur($data['docteur']);

       $em->flush();

       $jsonRendezVous = $serializer->serialize($rendezVous, 'json', ['group' => 'getRendezVous']);

       return new JsonResponse($jsonRendezVous, Response::HTTP_OK, [], true);
   }

   #[Route('/api/rendezVous/{id}', name:"app_rendezvous_delete", methods: ['DELETE'])]
   public function deleteRendezVous(RendezVous $rendezVous, EntityManagerInterface $em): JsonResponse
   {
       $em->remove($rendezVous);
       $em->flush();

       return new JsonResponse(null, Response::HTTP_NO_CONTENT);
   }

   #[Route('/api/rendezvous/{id}/accepter', methods: ['POST'])]
    public function accepterRendezVous($id, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse 
    {
        $rendezVous = $em->getRepository(RendezVous::class)->find($id);

        if (!$rendezVous) {
            return new JsonResponse(["error" => "Rendez-vous non trouvé"], 404);
        }

        // Modifier le statut du rendez-vous
        $rendezVous->setStatut("accepté");
        $rendezVous->setDateConsultationAt(new \DateTimeImmutable());

        // Créer une notification pour le patient
        // $notification = new Notification();
        // $notification->setUser($rendezVous->getPatient());
        // $notification->setMessage("Votre rendez-vous a été accepté");
        // $notification->setType("confirmation");
        // $notification->setStatut("envoyé");

        // // Sauvegarder les changements
        // $em->persist($notification);
        // $em->flush();

        // Sérialiser l'objet rendez-vous avec les groupes définis
        $data = $serializer->serialize(
            $rendezVous, 
            'json', 
            ['groups' => ['getRendezVous']]
        );

        return new JsonResponse($data, 200, [], true);
    }

   #[Route('/api/rendezVous/accepte', name:"app_rendezvous_accepte", methods: ['GET'])]
public function accepteRendezVous(RendezVousRepository $rendezVousRepository, SerializerInterface $serializer): JsonResponse
{
    $rendezVous = $rendezVousRepository->findBy(['statut' => 'Accepté']);

    $jsonRendezVous = $serializer->serialize($rendezVous, 'json', ['groups' => 'getRendezVous']);

    return new JsonResponse($jsonRendezVous, Response::HTTP_OK, [], true);
}


#[Route('/api/rendezVous/refuse', name:"app_rendezvous_refuse", methods: ['GET'])]
public function refuseRendezVous(RendezVousRepository $rendezVousRepository, SerializerInterface $serializer): JsonResponse
{
    $rendezVous = $rendezVousRepository->findBy(['statut' => 'Refusé']);

    $jsonRendezVous = $serializer->serialize($rendezVous, 'json', ['groups' => 'getRendezVous']);

    return new JsonResponse($jsonRendezVous, Response::HTTP_OK, [], true);
}


#[Route('/api/rendezVous/en_attente', name:"app_rendezvous_en_attente", methods: ['GET'])]
public function enAttenteRendezVous(RendezVousRepository $rendezVousRepository, SerializerInterface $serializer): JsonResponse
{
    $rendezVous = $rendezVousRepository->findBy(['statut' => 'En attente']);

    if (!$rendezVous) {
        return new JsonResponse(["message" => "Aucun rendez-vous en attente trouvé"], Response::HTTP_NOT_FOUND);
    }

    $jsonRendezVous = $serializer->serialize($rendezVous, 'json', ['groups' => 'getRendezVous']);

    return new JsonResponse($jsonRendezVous, Response::HTTP_OK, [], true);
}

}
