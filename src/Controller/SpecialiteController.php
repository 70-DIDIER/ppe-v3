<?php

namespace App\Controller;

use App\Entity\Specialite;
use App\Repository\SpecialiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class SpecialiteController extends AbstractController
{
    #[Route('/api/specialites', name: 'app_specialite', methods:['GET'])]
    public function ListeSpecialite(SpecialiteRepository $specialite,SerializerInterface $serializer): JsonResponse
    {

        $specialites = $specialite->findAll();
        $jsonSpecialite = $serializer->serialize($specialites, 'json', ['groups' => 'getDocteur']);
        return new JsonResponse($jsonSpecialite, Response::HTTP_OK, [], true);
    }

    #[Route('/api/specialite/{id}', name: 'app_specialite_show', methods:['GET'])]
    public function show(Specialite $specialite, SerializerInterface $serializer): JsonResponse
    {
        $jsonSpecialite = $serializer->serialize($specialite, 'json', ['groups' => 'getDocteur']);
        return new JsonResponse($jsonSpecialite, Response::HTTP_OK, [], true);
    }

    #[Route('/api/specialite', name: 'app_specialite_create', methods:['POST'])]
    public function create(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        $specialite = $serializer->deserialize($request->getContent(), Specialite::class, 'json');
        $em->persist($specialite);
        $em->flush();

        $location = $urlGenerator->generate('app_specialite_show', ['id' => $specialite->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $jsonSpecialite = $serializer->serialize($specialite, 'json', ['groups' => 'getDocteur']);
        return new JsonResponse($jsonSpecialite, Response::HTTP_CREATED, ["Location"=>$location], true);
    }

    #[Route('/api/specialite/{id}', name: 'app_specialite_delete', methods:['DELETE'])]
    public function delete(Specialite $specialite, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($specialite);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
