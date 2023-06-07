<?php

namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

        //création d'un user "normal"
        $user = new User();
        $user->setEmail('marly@gmail.com');
        $user->setRoles(["ROLE_USER"]);
        $user->setPassword($this->userPasswordHasher->hashPassword($user,'bobo'));
        $manager->persist($user);

        //création d'un user 
        $userAdmin = new User();
        $userAdmin->setEmail('mdian@gmail.com');
        $userAdmin->setRoles(["ROLE_ADMIN"]);
        $userAdmin->setPassword($this->userPasswordHasher->hashPassword($userAdmin,'bobo'));
        $manager->persist($userAdmin);

        $listAuthor = [];

        for ($j=1; $j <= 30; $j++)
        {
            $author = new Author();
            $author->setFirstName('Prénom '. $j);
            $author->setLastName('Nom '. $j);
            $manager->persist($author);

            $listAuthor[] = $author; 
        }

        for ($i=1; $i <= 100; $i++)
        {
            $book = new Book;
            $book->setTitle("Livre  $i");
            $book->setCoverText("couverture $i");
            $book->setAuthor($listAuthor[array_rand($listAuthor)]);
            $book->setComments("commentaires $i");
            $manager->persist($book);
        }

        $manager->flush();
    }
}
