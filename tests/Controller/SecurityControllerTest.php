<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\SecurityController;
use App\Entity\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Group('security')]
#[CoversClass(User::class)]
#[CoversClass(SecurityController::class)]
final class SecurityControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        $this->client = self::createClient();

        $this->urlGenerator = $this->client->getContainer()->get(
            'router.default'
        );

        $this->userRepository = $this->client->getContainer()->get(
            'doctrine.orm.entity_manager'
        )->getRepository(User::class);
        $this->user = $this->userRepository->findOneByEmail('admin@email.com');
    }

    public function testDisplayLoginPage(): void
    {
        $this->client->request(
            Request::METHOD_GET,
            $this->urlGenerator->generate('app_login')
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorTextContains('button', "Se connecter");
        $this->assertSelectorNotExists('.alert.alert-danger');
    }

    public function testUsersCanLogin(): void
    {
        $crawler = $this->client->request(
            Request::METHOD_GET,
            $this->urlGenerator->generate('app_login')
        );

        $form = $crawler->selectButton('Se connecter')->form();
        $form['_username'] = 'admin';
        $form['_password'] = 'admin';
        $this->client->submit($form);
        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorTextContains(
            'h1',
            "Bienvenue sur Todo List, l'application vous permettant de gérer l'ensemble de vos tâches sans effort !"
        );
        $this->assertIsObject(unserialize($this->client->getContainer()->get('session')->get('_security_main')));
    }

    public function testUsersTryLoginWithBadUsername(): void
    {
        $crawler = $this->client->request(
            Request::METHOD_GET,
            $this->urlGenerator->generate('app_login')
        );

        $form = $crawler->selectButton('Se connecter')->form();
        $form['_username'] = 'badusername';
        $form['_password'] = 'admin';
        $this->client->submit($form);
        $this->client->followRedirect();

        $this->assertSelectorExists('.alert.alert-danger');
    }

    public function testUsersTryLoginWithBadPassword(): void
    {
        $crawler = $this->client->request(
            Request::METHOD_GET,
            $this->urlGenerator->generate('app_login')
        );

        $form = $crawler->selectButton('Se connecter')->form();
        $form['_username'] = 'admin';
        $form['_password'] = 'badpassword';
        $this->client->submit($form);
        $this->client->followRedirect();

        $this->assertSelectorExists('.alert.alert-danger');
    }

    public function testLogout(): void
    {
        $this->client->loginUser($this->user);

        $this->client->request(Request::METHOD_GET, $this->urlGenerator->generate('app_logout'));
        $this->client->followRedirect();

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertIsNotObject($this->client->getContainer()->get('session')->get('_security_main'));
    }
}
