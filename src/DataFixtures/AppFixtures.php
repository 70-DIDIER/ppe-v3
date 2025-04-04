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
    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // créer un administrateur
        $adminUser= new User();
        $adminUser->setEmail("admin@myhospital.com");
        $adminUser->setRoles(["ROLE_ADMIN"]);
        $adminUser->setPassword($this->userPasswordHasher->hashPassword($adminUser, "adminpassword"));
        $manager->persist($adminUser);

        // Créer quelques spécialités pour les docteurs
        $specialites = ['Cardiologie', 'Orthopédie', 'Gastro-entérologie', 'Pneumologie', 'Gynécologie'];
        foreach ($specialites as $specialite) {
            $specialiteObj = new Specialite();
            $specialiteObj->setNom($specialite);
            $manager->persist($specialiteObj);
            $listeSpecialites[] = $specialiteObj; // Ajouter à la liste
        }

        // Créer les docteurs
        $docteurs = [];
        for ($i = 0; $i < 10; $i++) {
            $docteur = new Docteur();
            $docteur->setNom('Docteur' . $i);
            $docteur->setPrenom('Docteur' . $i);
            $docteur->setTelephone('06' . rand(10000000, 99999999));

            // Vérifier que $listeSpecialites n'est pas vide avant d'utiliser array_rand
            if (!empty($listeSpecialites)) {
                $docteur->addSpecialite($listeSpecialites[array_rand($listeSpecialites)]);
            }

            $manager->persist($docteur);
            $docteurs[] = $docteur;
        }

        // Créer les patients
        $patients = [];
        for ($i = 0; $i < 20; $i++) {
            $patient = new Patient();
            $patient->setNom('Patient' . $i);
            $patient->setPrenom('Patient' . $i);
            $patient->setTelephone('06' . rand(10000000, 99999999));
            $patient->setAdresse('Adresse' . $i);

            $manager->persist($patient);
            $patients[] = $patient;
        }

        // Créer les rendez-vous en les attribuant aléatoirement aux docteurs et aux patients
        $typesConsultation = ['En ligne', 'À domicile', 'À l\'hôpital'];
        $statuts = ['En attente', 'Accepté', 'Refusé'];

        // Vérifier que les patients et docteurs existent
        if (!empty($patients) && !empty($docteurs)) {
            for ($i = 0; $i < 50; $i++) {
                $rendezVous = new RendezVous();
                $rendezVous->setDescription('Rendez-vous ' . $i);
                $rendezVous->setTypeConsultation($typesConsultation[array_rand($typesConsultation)]);
                $rendezVous->setStatut($statuts[array_rand($statuts)]);
                $rendezVous->setDateConsultationAt(new \DateTimeImmutable());
                $rendezVous->setHeureConsultation(new \DateTimeImmutable());

                // Sélectionner un patient et un docteur aléatoire dans les tableaux
                $rendezVous->setPatient($patients[array_rand($patients)]);
                $rendezVous->setDocteur($docteurs[array_rand($docteurs)]);

                $manager->persist($rendezVous);
            }
        } else {
            throw new \Exception("Impossible de créer des rendez-vous : aucun patient ou docteur trouvé.");
        }

        // Sauvegarder tous les objets dans la base de données
        $manager->flush();
    }
}
