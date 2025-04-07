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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class RendezVousController extends AbstractController
{
    private $notificationService;
    private $rendezVousRepository;

    public function __construct(NotificationService $notificationService, RendezVousRepository $rendezVousRepository)
    {
        $this->notificationService = $notificationService;
        $this->rendezVousRepository = $rendezVousRepository;
    }
    #[Route('/api/rendezVous', name:"app_create_rendezVous", methods: ['POST'])]
    public function createRendezVous(
        Request $request, 
        EntityManagerInterface $em, 
        DocteurRepository $docteurRepository,
        SerializerInterface $serializer
    ): JsonResponse 
    {
        $user = $this->getUser();

        if (!$user instanceof \App\Entity\User) {
            return new JsonResponse(['error' => "L'utilisateur n'est pas reconnu comme un patient"], Response::HTTP_FORBIDDEN);
        }
        
        $patient = $user->getPatient();
    
        if (!$patient) {
            return new JsonResponse(['error' => "Aucun profil patient lié à cet utilisateur"], Response::HTTP_FORBIDDEN);
        }
    
        // Récupération des données JSON
        $data = json_decode($request->getContent(), true);
    
        // Validation des champs requis
        $requiredFields = ['dateRendezVous', 'heureRendezVous', 'docteur'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return new JsonResponse(['error' => 'Le champ "' . $field . '" est requis'], Response::HTTP_BAD_REQUEST);
            }
        }
    
        $docteur = $docteurRepository->find((int)$data['docteur']);
        if (!$docteur) {
            return new JsonResponse(['error' => 'Docteur introuvable (ID: ' . $data['docteur'] . ')'], Response::HTTP_NOT_FOUND);
        }
    
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
            $rendezVous->setDescription($data['descriptionRendezVous'] ?? "Pas de description");
            $rendezVous->setTypeConsultation($data['typeConsultation'] ?? "à l'hôpital");
            $rendezVous->setStatut("en attente");
            $rendezVous->setDocteur($docteur);
            $rendezVous->setPatient($patient);
    
            $em->persist($rendezVous);
            $em->flush();
    
            $this->notificationService->notifierDocteur($docteur, "Un patient veut prendre un rendez-vous avec vous.");

            $jsonRendezVous = $serializer->serialize(
                $rendezVous,
                'json',
                ['groups' => ['getRendezVous']]
            );
    
            return new JsonResponse(['message' => 'Rendez-vous créé avec succès'], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur de traitement: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
       $jsonRendezVous = $serializer->serialize($rendezVous, 'json', ['groups' => 'getRendezVous']);

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
        EntityManagerInterface $em
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Données invalides ou mal formatées'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Vérification des données reçues
        if (!isset($data['rendezVous_id'], $data['statut'])) {
            return new JsonResponse(['error' => 'Données manquantes'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Récupérer le rendez-vous
        $rendezVous = $this->rendezVousRepository->find($data['rendezVous_id']);

        if (!$rendezVous) {
            return new JsonResponse(['error' => 'Rendez-vous non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Récupérer les informations liées au rendez-vous
        $patient = $rendezVous->getPatient();
        $docteur = $rendezVous->getDocteur();

        if ($data['statut'] === "accepté") {
            // Si le statut est accepté, vérifier la date et l'heure
            if (!isset($data['dateHeure'])) {
                return new JsonResponse(['error' => 'La date et l\'heure sont requises pour accepter un rendez-vous'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $date = new \DateTime($data['dateHeure']);
            $rendezVous->setDateConsultationAt(\DateTimeImmutable::createFromMutable($date));
            $rendezVous->setHeureConsultation(\DateTimeImmutable::createFromMutable($date));
            $rendezVous->setStatut($data['statut']);

            // Message pour le patient après l'acceptation
            $message = "Votre rendez-vous avec le Dr. " . $docteur->getNom() . " est confirmé pour le " . $data['dateHeure'];
        } else {
            // Si le statut est refusé
            $rendezVous->setStatut($data['statut']);
            $message = "Votre demande de rendez-vous avec le Dr. " . $docteur->getNom() . " a été refusée, par manque de disponibilité.";
        }

        $em->persist($rendezVous);
        $em->flush();

        // Notifier le patient
        $this->notificationService->notifierPatient($patient, $message, "reponse_rendezVous");

        return new JsonResponse(['message' => "Réponse envoyée au patient."], JsonResponse::HTTP_OK);
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
