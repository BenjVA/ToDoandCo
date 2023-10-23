<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Group('homepage')]
#[CoversClass(User::class)]
#[UsesClass(User::class)]
#[CoversFunction('indexAction')]
final class DefaultControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        $this->client = self::createClient();

        $this->userRepository = $this->client->getContainer()->get(
            'doctrine.orm.entity_manager'
        )->getRepository(User::class);

        $this->user = $this->userRepository->findOneByEmail('admin@email.com');

        $this->urlGenerator = $this->client->getContainer()->get(
            'router.default'
        );
    }

    public function testIndex(): void
    {
        $this->client->loginUser($this->user);
        $this->client->request(
            Request::METHOD_GET,
            $this->urlGenerator->generate('homepage')
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorTextContains(
            'h1',
            "Bienvenue sur Todo List, l'application vous permettant de gérer l'ensemble de vos tâches sans effort !"
        );
    }

    public function testIndexNotLoggedIn(): void
    {
        $this->client->request(
            Request::METHOD_GET,
            $this->urlGenerator->generate('homepage')
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
        $this->assertResponseRedirects('http://localhost/login');
    }
}
