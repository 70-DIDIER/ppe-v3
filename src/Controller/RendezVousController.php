<?php

namespace App\Controller;

use App\Entity\RendezVous;
use App\Service\NotificationService;
use App\Repository\DocteurRepository;
use App\Repository\PatientRepository;
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
    private $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    #[Route('/api/rendezVous', name:"app_create_rendezVous", methods: ['POST'])]
    public function createRendezVous(
        Request $request, 
        EntityManagerInterface $em, 
        DocteurRepository $docteurRepository, 
        PatientRepository $patientRepository
    ): JsonResponse 
    {
        $data = json_decode($request->getContent(), true);
    
        // Validation renforcée
        $requiredFields = ['dateRendezVous', 'heureRendezVous', 'docteur', 'patient'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return new JsonResponse(
                    ['error' => 'Le champ "' . $field . '" est requis'], 
                    Response::HTTP_BAD_REQUEST
                );
            }
        }
    
        // Conversion explicite en integer
        $docteur = $docteurRepository->find((int)$data['docteur']);
        $patient = $patientRepository->find((int)$data['patient']);
    
        if (!$docteur) {
            return new JsonResponse(
                ['error' => 'Docteur introuvable (ID: ' . $data['docteur'] . ')'], 
                Response::HTTP_NOT_FOUND
            );
        }
    
        if (!$patient) {
            return new JsonResponse(
                ['error' => 'Patient introuvable (ID: ' . $data['patient'] . ')'], 
                Response::HTTP_NOT_FOUND
            );
        }
    
        // Gestion des dates
        try {
            $dateTime = \DateTime::createFromFormat(
                'Y-m-d H:i:s', 
                $data['dateRendezVous'] . ' ' . $data['heureRendezVous']
            );
            
            if (!$dateTime) {
                throw new \Exception('Format de date/heure invalide');
            }
    
            $rendezVous = new RendezVous();
            $rendezVous->setDateConsultationAt(\DateTimeImmutable::createFromMutable($dateTime));
            $rendezVous->setHeureConsultation(\DateTimeImmutable::createFromMutable($dateTime));
            $rendezVous->setDescription($data['descriptionRendezVous'] ?? '');
            $rendezVous->setTypeConsultation($data['typeConsultation'] ?? "à l'hôpital");
            $rendezVous->setStatut("en attente");
            $rendezVous->setDocteur($docteur);
            $rendezVous->setPatient($patient);
    
            $em->persist($rendezVous);
            $em->flush();
    
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Erreur de traitement: ' . $e->getMessage()], 
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    
        return new JsonResponse(
            ['message' => 'Rendez-vous créé avec succès', 'id' => $rendezVous->getId()],
            Response::HTTP_CREATED
        );
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

   #[Route('/api/accepter-refuser-rdv', name: 'api_accepter_refuser_rdv', methods: ['POST'])]
   public function accepterOuRefuserRendezVous(
       Request $request, 
       EntityManagerInterface $entityManager, 
       RendezVousRepository $rendezVousRepository,
       SerializerInterface $serializer
   ): JsonResponse {
       try {
        $data = json_decode($request->getContent(), true);
       } catch (\Exception $e) {
           return new JsonResponse(['error' => 'Données invalides ou mal formatées'], Response::HTTP_INTERNAL_SERVER_ERROR);
       }
   
       // Vérification des données reçues
       if (!isset($data['rendezVous_id'], $data['statut'])) {
           return new JsonResponse(['error' => 'Données manquantes'], Response::HTTP_INTERNAL_SERVER_ERROR);
       }
   
       $rendezVous = $rendezVousRepository->find($data['rendezVous_id']);
   
       if (!$rendezVous) {
           return new JsonResponse(['error' => 'Rendez-vous non trouvé'], Response::HTTP_NOT_FOUND);
       }

       $patient = $rendezVous->getPatient();
       $docteur = $rendezVous->getDocteur();
   
       if ($data['statut'] === "accepté") {
           if (!isset($data['dateHeure'])) {
               return new JsonResponse(['error' => 'La date et l\'heure sont requises pour accepter un rendez-vous'], 400);
           }
           $date = new \DateTime($data['dateHeure']);
           $rendezVous->setDateConsultationAt(\DateTimeImmutable::createFromMutable($date));
           $rendezVous->setHeureConsultation(\DateTimeImmutable::createFromMutable($date));
           $rendezVous->setStatut($data['statut']);

           $message = "Votre rendez-vous avec le Dr. " . $docteur->getNom() . " est confirmé pour le " . $data['dateHeure'];
       } else {
           $message = "Votre demande de rendez-vous avec le Dr. " . $docteur->getNom() . " a été refusée, par manque de disponibilité.";
       }
   
       $entityManager->persist($rendezVous);
       $entityManager->flush();
   
       // 📢 Notifier le patient
       $this->notificationService->notifierPatient($patient, $message, "reponse_rendezVous");
   
       return new JsonResponse(['message' => "Réponse envoyée au patient."], Response::HTTP_OK);
   }


   #[Route('/api/rendezvous-en-attente', name: 'api_rendezvous_en_attente', methods: ['POST'])]
   public function rendezVousEnAttente(
       Request $request, 
       EntityManagerInterface $entityManager, 
       RendezVousRepository $rendezVousRepository,
       SerializerInterface $serializer
   ): JsonResponse {
       // 🔹 Désérialisation avec le Serializer
       try {
           $data = $serializer->deserialize($request->getContent(), 'array', 'json');
       } catch (\Exception $e) {
           return new JsonResponse(['error' => 'Données invalides ou mal formatées'], 400);
       }
   
       // Vérification des données reçues
       if (!isset($data['rendezVous_id'])) {
           return new JsonResponse(['error' => 'L\'ID du rendez-vous est requis'], 400);
       }
   
       $rendezVous = $rendezVousRepository->find($data['rendezVous_id']);
   
       if (!$rendezVous) {
           return new JsonResponse(['error' => 'Rendez-vous non trouvé'], 404);
       }
   
       $patient = $rendezVous->getPatient();
       $docteur = $rendezVous->getDocteur();
   
       // 🔹 Mettre le statut en attente
       $rendezVous->setStatut("en attente");
   
       // 🔹 Sauvegarde en base
       $entityManager->persist($rendezVous);
       $entityManager->flush();
   
       // 📢 Notifier le patient
       $message = "Votre demande de rendez-vous avec le Dr. " . $docteur->getNom() . " est en attente de confirmation.";
       $this->notificationService->notifierPatient($patient, $message, "rendezvous_en_attente");
   
       return new JsonResponse(['message' => "Le patient a été notifié que son rendez-vous est en attente."], 200);
   }
   

}
