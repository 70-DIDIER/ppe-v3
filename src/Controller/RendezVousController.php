<?php

namespace App\Controller;

use App\Entity\Docteur;
use App\Entity\Patient;
use App\Entity\RendezVous;
use App\Entity\Notification;
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

            // Notifier le patient
            $message = "Votre demande de rendez-vous avec le Dr. " . $docteur->getNom() . " a été envoyée.";
            $notification = new Notification();
            $notification->setPatient($patient);
            $notification->setMessage($message);
            $notification->setDateHeureAt(new \DateTimeImmutable());
            $notification->setType("demande_rendezVous");
            $notification->setStatut(false);
            $em->persist($notification);
            $em->flush();


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

//    #[Route('/api/rendezVous', name:"app_rendezvous_liste", methods: ['GET'])]
//    public function indexRendezVous(RendezVousRepository $rendezVousRepository, SerializerInterface $serializer): JsonResponse
//    {
//        $rendezVous = $rendezVousRepository->findAll();
//        $jsonRendezVous = $serializer->serialize($rendezVous, 'json', ['groups' => 'getRendezVous']);

//        return new JsonResponse($jsonRendezVous, Response::HTTP_OK, [], true);
//    }

#[Route('/api/mes-rendezvous', name: 'mes_rendezvous', methods: ['GET'])]
public function mesRendezVous(EntityManagerInterface $em): JsonResponse
{
    $user = $this->getUser();

    // On récupère le Patient lié à l'utilisateur
    $patient = $em->getRepository(Patient::class)->findOneBy(['user' => $user]);

    if (!$patient) {
        return $this->json(['error' => 'Aucun patient lié à cet utilisateur.'], 404);
    }

    // Récupère les rendez-vous du patient
    $rendezVous = $em->getRepository(RendezVous::class)->findBy(['patient' => $patient]);

    return $this->json($rendezVous, 200, [], ['groups' => 'getRendezVous']);
}

    #[Route('/api/mes-rendezvous-docteur', name: 'mes_rendezvous_docteur', methods: ['GET'])]
    public function mesRendezVousDocteur(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        $docteur = $em->getRepository(Docteur::class)->findOneBy(['user' => $user]);

        if (!$docteur) {
            return $this->json(['error' => 'Aucun docteur lié à cet utilisateur.'], 404);
        }

        $rendezVous = $em->getRepository(RendezVous::class)->findBy(['docteur' => $docteur]);

        return $this->json($rendezVous, 200, [], ['groups' => 'getRendezVous']);
    }

    #[Route('/api/rendezvous/{id}/update', name: 'update_rendezvous', methods: ['PUT'])]
    public function updateRendezVous(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $rdv = $em->getRepository(RendezVous::class)->find($id);

        if (!$rdv) {
            return $this->json(['error' => 'Rendez-vous non trouvé.'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['statut'])) {
            $rdv->setStatut($data['statut']);
        }

        try {
            if (isset($data['dateConsultationAt'])) {
                try {
                    $rdv->setDateConsultationAt(new \DateTimeImmutable($data['dateConsultationAt']));
                } catch (\Exception $e) {
                    return $this->json(['error' => 'Format de date invalide.'], 400);
                }
            }
            
            if (isset($data['heureConsultation'])) {
                try {
                    $time = \DateTimeImmutable::createFromFormat('H:i', $data['heureConsultation']);
                    if (!$time) {
                        throw new \RuntimeException("Format d'heure invalide");
                    }
                    $rdv->setHeureConsultation($time);
                } catch (\Exception $e) {
                    return $this->json(['error' => 'Format d\'heure invalide.'], 400);
                }
            }

            $patient = $rdv->getPatient();
        $message = '';

        if ($data['statut'] === 'accepté') {
            $date = $rdv->getDateConsultationAt()?->format('Y-m-d');
            $heure = $rdv->getHeureConsultation()?->format('H:i');
            $message = "Votre rendez-vous a été accepté pour le {$date} à {$heure}.";
        } elseif ($data['statut'] === 'refusé') {
            $message = "Votre rendez-vous a été refusé.";
        }

        if ($message && $patient) {
            $notification = new Notification();
            $notification->setPatient($patient);
            $notification->setMessage($message);
            $notification->setDateHeureAt(new \DateTimeImmutable());
            $em->persist($notification);
        }

        $em->flush();

            $em->flush();

            return $this->json($rdv, 200, [], ['groups' => 'getRendezVous']);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la mise à jour',
                'message' => $e->getMessage()
            ], 500);
        }
    }

   

}
