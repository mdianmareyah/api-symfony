<?php

namespace App\Controller;

use App\Entity\Author;
use App\Repository\AuthorRepository;
use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class AuthorController extends AbstractController
{

    #[Route('/api/authors', name: 'all_author', methods: ['GET'])]
    public function getAllAuthor(
        AuthorRepository $authorRepository, 
        SerializerInterface $serializerInterface,
        Request $request,
        TagAwareCacheInterface $cachePool
    ): JsonResponse
    {
        $page = $request->get('page',1);
        $limit = $request->get('limit', 3);
        $idCache = "getAllAuthor". $page . "-" . $limit;
        $authorsJson = $cachePool->get(
            $idCache, function (ItemInterface $itemInterface) use ($page, $limit, $authorRepository, $serializerInterface) {
                $itemInterface->tag('authorCache');
                $authors = $authorRepository->findAllWithPagination($page, $limit);
                $context = SerializationContext::create()->setGroups('getAuthors');
                return $serializerInterface->serialize($authors,'json',$context);
            }
        );

        return new JsonResponse($authorsJson, Response::HTTP_OK, [], true);
    }

    #[Route('/api/authors/{id}', name: 'one_author', methods: ['GET'])]
    public function getAuthorById(Author $author ,SerializerInterface $serializerInterface): JsonResponse
    {
        $authorJson = $serializerInterface->serialize(
            $author, 'json',
            SerializationContext::create()->setGroups('getAuthors')
        );
        return new JsonResponse($authorJson, Response::HTTP_OK, [], true);
    }

    #[Route('/api/authors', name: 'create_author', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: "vous n'avez pas les droits suffisants pour réaliser cette action")]
    public function createAuthor(Request $request, SerializerInterface $serializerInterface, AuthorRepository $authorRepository, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validatorInterface)
    {
        $author = $serializerInterface->deserialize($request->getContent(), Author::class,'json');
        
        $error = $validatorInterface->validate($author);
        if($error->count() > 0)
        {
            return new JsonResponse(
                $serializerInterface->serialize($error, 'json'),
                Response::HTTP_BAD_REQUEST,
                [],
                true
            );
        }
        
        $authorRepository->save($author,true);

        $authorJson = $serializerInterface->serialize(
            $author, 'json',
            SerializationContext::create()->setGroups('getAuthors')
        );
        $location = $urlGenerator->generate('one_author', ['id' => $author->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($authorJson, Response::HTTP_CREATED, ['Location' => $location], true);
    }

    #[Route('/api/authors/{id}', name: 'update_author', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: "vous n'avez pas les droits suffissants pour modifier un livre")]
    public function updateAuthor(
        Author $currentAuthor, 
        Request $request, 
        SerializerInterface $serializerInterface, 
        AuthorRepository $authorRepository,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cachePool
    ): JsonResponse
    {
        $author = $serializerInterface->deserialize($request->getContent(), Author::class, 'json');
        $currentAuthor->setFirstName($author->getFirstName());
        $currentAuthor->setLastName($author->getLastName());
        
        $error = $validator->validate($currentAuthor);
        if($error->count() > 0)
        {
            return new JsonResponse(
                $serializerInterface->serialize($error,'json'),
                Response::HTTP_BAD_REQUEST,
                [],
                true
            );
        }

        $authorRepository->save($author,true);
        $cachePool->invalidateTags(['authorCache']);
        return new JsonResponse(null,Response::HTTP_NO_CONTENT);
    }


    #[Route('/api/authors/{id}', name: 'delete_author', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: "vous n'avez pas les droits suffissants pour supprimer un livre")]
    public function deleteBook(
        Author $author, 
        AuthorRepository $authorRepository, 
        TagAwareCacheInterface $cachePool
    ): JsonResponse
    {
        $authorRepository->delete($author,true);
        $cachePool->invalidateTags(['authorCache']);
        return new JsonResponse(null,Response::HTTP_NO_CONTENT);
    }
}