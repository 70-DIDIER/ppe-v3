<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Docteur;
use App\Entity\Patient;
use App\Entity\RendezVous;
use App\Entity\Specialite;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $userPasswordHasher;
    private $batchSize = 10; // Réduire la taille des lots

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $this->loadUsersAndPatients($manager);
        $this->loadDoctors($manager);
        $this->loadAppointments($manager);
    }

    private function loadUsersAndPatients(ObjectManager $manager): void
    {
        $noms = ['Dupont', 'Durand', 'Leroy', 'Moreau', 'Simon'];
        $prenoms = ['Alice', 'Bob', 'Claire', 'David', 'Emma'];

        for ($i = 1; $i <= 20; $i++) {
            // Création User
            $user = new User();
            $user->setEmail("patient{$i}@example.com")
                 ->setPassword($this->userPasswordHasher->hashPassword($user, "patient{$i}"));
            $manager->persist($user);

            // Création Patient
            $patient = new Patient();
            $patient->setNom($noms[array_rand($noms)])
                    ->setPrenom($prenoms[array_rand($prenoms)])
                    ->setTelephone('06' . rand(10000000, 99999999))
                    ->setAdresse("Adresse $i, Ville")
                    ->setUser($user);
            $manager->persist($patient);

            // if ($i % $this->batchSize === 0) {
                // $manager->flush();
                // $manager->clear(); // Détache toutes les entités
                // gc_collect_cycles(); // Force le garbage collector
            // }
        }
        $manager->flush();
        $manager->clear();
    }

    private function loadDoctors(ObjectManager $manager): void
    {
        $specialites = ['Cardiologie', 'Orthopédie', 'Gastro-entérologie'];
        $listeSpecialites = array_map(function ($nom) use ($manager) {
            $spec = new Specialite();
            $spec->setNom($nom);
            $manager->persist($spec);
            return $spec;
        }, $specialites);

        $manager->flush();
        $manager->clear();

        // ... (code similaire pour les docteurs)
    }

    private function loadAppointments(ObjectManager $manager): void
    {
        $patients = $manager->getRepository(Patient::class)->findAll();
        $docteurs = $manager->getRepository(Docteur::class)->findAll();
        $type_consulations = ["en ligne", "à l'hopital", "à la maison"];
        $statuts =[RendezVous::STATUT_ACCEPTE, RendezVous::STATUT_REFUSE, RendezVous::STATUT_ENATTENTE];
        for ($i = 1; $i <= 50; $i++) {
            $rdv = new RendezVous();
            $rdv->setDescription("Consultation $i");
            if(!empty($patients)){
                $rdv->setPatient($patients[array_rand($patients)]);
                
            }
            if(!empty($docteurs)){
                $rdv->setDocteur($docteurs[array_rand($docteurs)]);
            }
            $rdv->setTypeConsultation($type_consulations[array_rand($type_consulations)]);
            $rdv->setStatut($statuts[array_rand($statuts)]);
            $rdv->setDateConsultationAt(new \DateTimeImmutable());
            $rdv->setHeureConsultation(new \DateTimeImmutable());
            $manager->persist($rdv);

            if ($i % $this->batchSize === 0) {
                $manager->flush();
                $manager->clear();
                $patients = $manager->getRepository(Patient::class)->findAll();
                $docteurs = $manager->getRepository(Docteur::class)->findAll();
            }
        }
        $manager->flush();
    }
}