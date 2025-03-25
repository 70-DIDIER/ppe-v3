<?php

namespace App\DataFixtures;

use App\Entity\Docteur;
use App\Entity\Patient;
use App\Entity\Specialite;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {

        // Créer quelque spécialité poue les docteurs
        $specialites = ['Cardiologie', 'Orthopédie', 'Gastro-enterologie', 'Pneumologie', 'Gynécologie'];
        foreach ($specialites as $specialite) {
            $specialiteObj = new Specialite();
            $specialiteObj->setNom($specialite);
            $manager->persist($specialiteObj);
            $listeSpecialites[] = $specialiteObj;
        }
        // Créer les docteurs
        for ($i = 0; $i < 10; $i++) {
            $docteur = new Docteur();
            $docteur->setNom('Docteur'.$i);
            $docteur->setPrenom('Docteur'.$i);
            $docteur->setTelephone('06'.rand(10000000, 99999999));
            $docteur->addSpecialite($listeSpecialites[array_rand($listeSpecialites)]);
            // Sauvegarder le docteur dans la base de données
            $manager->persist($docteur);
        }

        // Créer les patients
        for ($i = 0; $i < 20; $i++) {
            $patient = new Patient();
            $patient->setNom('Patient'.$i);
            $patient->setPrenom('Patient'.$i);
            $patient->setTelephone('06'.rand(10000000, 99999999));
            $patient->setAdresse('Adresse'.$i);
            $manager->persist($patient);
        }

        // Sauvegarder tous les objets dans la base de données
        $manager->flush();
    }
}
