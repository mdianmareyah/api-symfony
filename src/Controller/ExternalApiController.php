<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ExternalApiController extends AbstractController
{
    //#[Route('/external/api', name: 'app_external_api')]
    //public function index(): Response
    //{
     //   return $this->render('external_api/index.html.twig', [
       //     'controller_name' => 'ExternalApiController',
        //]);
   // }

   #[Route('/api/external/symfonydoc', name: 'symfony_doc', methods: ['GET'])]
   public function getExternalApi(HttpClientInterface $httpClient) : JsonResponse
   {
        $response = $httpClient->request(
            'GET',
            'https://api.github.com/repos/symfony/symfony-docs'
        );

        return new JsonResponse($response->getContent(), Response::HTTP_OK, [], true);
   }
}
