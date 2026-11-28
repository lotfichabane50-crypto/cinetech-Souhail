<?php

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\Movie;
use App\Repository\MovieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class MovieControllerTest extends WebTestCase
{
    private ?int $createdMovieId = null;
    private ?int $createdCategoryId = null;

    protected function tearDown(): void
    {
        if (null !== $this->createdMovieId || null !== $this->createdCategoryId) {
            $entityManager = self::getContainer()->get(EntityManagerInterface::class);

            if (null !== $movie = $entityManager->find(Movie::class, $this->createdMovieId)) {
                $entityManager->remove($movie);
            }

            if (null !== $category = $entityManager->find(Category::class, $this->createdCategoryId)) {
                $entityManager->remove($category);
            }

            $entityManager->flush();
        }

        $this->createdMovieId = null;
        $this->createdCategoryId = null;

        self::ensureKernelShutdown();

        parent::tearDown();
    }

    public function testCatalogueIndexIsReachable(): void
    {
        $client = static::createClient();
        $client->request('GET', '/catalogue');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Catalogue');
    }

    public function testMovieDetailPageIsReachableForExistingMovie(): void
    {
        $client = static::createClient();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $category = new Category();
        $category->setName('TNR — Catégorie de test');

        $movie = new Movie();
        $movie->setTitle('TNR — Film de test');
        $movie->setDuration(120);
        $movie->setReleaseDate(new \DateTimeImmutable('2020-01-01'));
        $movie->setCategory($category);

        $entityManager->persist($category);
        $entityManager->persist($movie);
        $entityManager->flush();

        $this->createdCategoryId = $category->getId();
        $this->createdMovieId = $movie->getId();

        /** @var MovieRepository $repository */
        $repository = $entityManager->getRepository(Movie::class);
        $existing = $repository->findOneWithCategory($this->createdMovieId);

        self::assertNotNull($existing, 'Le film doit être récupérable via le Repository.');

        $client->request('GET', '/catalogue/'.$existing->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'TNR — Film de test');
    }

    public function testUnknownUrlReturnsNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/cette-url-nexiste-pas');

        self::assertResponseStatusCodeSame(404);
    }
}
