<?php

namespace App\Controller;

use App\Entity\Docteur;
use App\Repository\DocteurRepository;
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
    #[Route('/api/docteurs', name: 'app_docteur', methods:['GET'])]
    public function ListeDocteur(DocteurRepository $docteurRepository,SerializerInterface $serializer): JsonResponse
    {

        $docteurs = $docteurRepository->findAll();
        $jsonDocteur = $serializer->serialize($docteurs, 'json', ['groups' => 'getDocteur']);
        return new JsonResponse($jsonDocteur, Response::HTTP_OK, [], true);
    }

    #[Route('/api/docteur/{id}', name: 'app_docteur_show', methods:['GET'])]
    public function show(Docteur $docteur, SerializerInterface $serializer): JsonResponse
    {
        $jsonDocteur = $serializer->serialize($docteur, 'json', ['groups' => 'getDocteur']);
        return new JsonResponse($jsonDocteur, Response::HTTP_OK, [], true);
    }

    #[Route('/api/docteur', name: 'app_docteur_create', methods:['POST'])]
    public function create(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        $docteur = $serializer->deserialize($request->getContent(), Docteur::class, 'json');
        $em->persist($docteur);
        $em->flush();

        $location = $urlGenerator->generate('detailDocteur', ['id' => $docteur->getId()]);

        $jsonDocteur = $serializer->serialize($docteur, 'json', ['groups' => 'getDocteur']);
        return new JsonResponse($jsonDocteur, Response::HTTP_CREATED, ["Location"=>$location], true);
    }

    #[Route('/api/docteur/{id}', name: 'app_docteur_delete', methods:['DELETE'])]
    public function delete(Docteur $docteur, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($docteur);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
    
}
