<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Group('homepage')]
#[CoversClass(User::class)]
final class DefaultControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        $this->client = self::createClient();

        $this->urlGenerator = $this->client->getContainer()->get(
            'router.default'
        );
    }

    public function testIndex(): void
    {
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
}
