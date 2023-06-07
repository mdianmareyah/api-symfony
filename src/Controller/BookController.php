<?php

namespace App\Controller;

use ApiPlatform\Api\QueryParameterValidator\Validator\ValidatorInterface as ValidatorValidatorInterface;
use App\Entity\Book;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use App\Service\VersioningService as ServiceVersioningService;
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

class BookController extends AbstractController
{
    #[Route('/api/books', name: 'all_book', methods: ['GET'])]
    public function getAllBook(
        SerializerInterface $serializerInterface, 
        BookRepository $bookRepository, 
        Request $request,
        TagAwareCacheInterface $cachePool,
        ServiceVersioningService $versioningService
    ): JsonResponse
    {
        $page = $request->get('page',1);
        $limit = $request->get('limit', 3);
        $idCache = "getAllBook". $page . "-" . $limit;
        $books = $cachePool->get($idCache, function (ItemInterface $item) use ($bookRepository, $page , $limit) {
            $item->tag('booksCache');
            return $bookRepository->findAllWithPagination($page, $limit);
        } );

        $contextSerializer = SerializationContext::create()->setGroups(['getBooks']);
        $contextSerializer->setVersion($versioningService->getVersion());
        $jsonBooks = $serializerInterface->serialize($books,'json', $contextSerializer);
        return new JsonResponse($jsonBooks, Response::HTTP_OK, [], true);
    }


    #[Route('/api/books/{id}', name: 'one_book', methods: ['GET'])]
    public function getBookById(Book $book, SerializerInterface $serializerInterface)
    {
        $contextSerializer = SerializationContext::create()->setGroups('getBooks');
        $bookjson = $serializerInterface->serialize($book,'json', $contextSerializer);
        return new JsonResponse($bookjson,Response::HTTP_OK, [], true);
    }

    #[Route('/api/books', name: 'create_book', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN',message: "vous n'avez pas les droits suffisant pour créer un livre")]
    public function createBook(Request $request, SerializerInterface $serializerInterface, BookRepository $bookRepository, AuthorRepository $authorRepository, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator)
    {
        $book = $serializerInterface->deserialize(
            $request->getContent(),
            Book::class,
            'json',

        );

        $error = $validator->validate($book);

        if($error->count() > 0)
        {
            return new JsonResponse(
                $serializerInterface->serialize($error, 'json'),
                Response::HTTP_BAD_REQUEST,
                [],
                true
            );
        }

        $content = $request->toArray();
        $author = $authorRepository->find($content['idAuthor']);
        $book->setAuthor($author);
        $bookRepository->save($book, true);

        $serializerContext = SerializationContext::create()->setGroups('getBooks');
        $bookJson = $serializerInterface->serialize($book, 'json', $serializerContext);

        $location = $urlGenerator->generate('one_book', ['id' => $book->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        
        return new JsonResponse($bookJson, Response::HTTP_CREATED, ['Location' => $location], true);
    }

    #[Route('/api/books/{id}', name: 'delete_book', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'vous n\'avez pas les droits suffisant pour supprimer un livre')]
    public function removeBook(Book $book, BookRepository $bookRepository, TagAwareCacheInterface $cachePool): JsonResponse
    {
        $bookRepository->remove($book, true);
        $cachePool->invalidateTags(['booksCache']);
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/books/{id}', name: 'update_book', methods: ['PUT'])]
    public function updateBook(
        Book $currentBook, 
        Request $request, 
        BookRepository $bookRepository,
        AuthorRepository $authorRepository, 
        SerializerInterface $serializerInterface,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cachePool
    ): JsonResponse
    {
        $book = $serializerInterface->deserialize($request->getContent(), Book::class, 'json');

        $currentBook->setTitle($book->getTitle());
        $currentBook->setCoverText($book->getCoverText());

        $content = $request->toArray();
        $idAuthor = $content['idAuthor'] ?? -1;
        $author = $authorRepository->find($idAuthor);
        $currentBook->setAuthor($author);

        $error = $validator->validate($currentBook);
        if($error->count() > 0)
        {
            return new JsonResponse(
                $serializerInterface->serialize($error, 'json'),
                Response::HTTP_BAD_REQUEST,
                [],
                true
            );
        }


        $bookRepository->save($currentBook, true);

        $cachePool->invalidateTags(['booksCache']);
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    
}