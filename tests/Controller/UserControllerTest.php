<?php

namespace App\Tests\Controller;

use App\Controller\UserController;
use App\Entity\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

#[Group('user')]
#[CoversClass(UserController::class)]
#[CoversClass(User::class)]
final class UserControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        $this->client = self::createClient();

        $this->userRepository = $this->client->getContainer()->get(
            'doctrine.orm.entity_manager'
        )->getRepository(User::class);

        $this->admin = $this->userRepository->findOneByEmail('admin@email.com');
        $this->user = $this->userRepository->findOneByEmail('nathalie.morin@dbmail.com');
        $this->anonUser = $this->userRepository->findOneByEmail('anon@anon.fr');
    }

    public function testOnlyAdminsCanAccessUsersList(): void
    {
        $this->client->loginUser($this->admin);
        $this->client->request(
            'GET',
            '/users'
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseIsSuccessful();
    }

    public function testUsersCannotAccessUsersList(): void
    {
        $this->client->loginUser($this->user);
        $this->client->request(
            'GET',
            '/users'
        );
        $this->assertResponseStatusCodeSame(403);
    }

    public function testNotLoggedUsersCannotAccessUsersList(): void
    {
        $this->client->request(
            'GET',
            '/users'
        );
        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects('http://localhost/login', 302);
    }

    public function testSuccessfullUserCreation(): void
    {
        $this->client->loginUser($this->admin);
        $this->client->request(
            'POST',
            '/users/create'
        );
        $this->client->submitForm('Ajouter', [
            'user[username]' => 'userTest',
            'user[password][first]' => 'motdepasse',
            'user[password][second]' => 'motdepasse',
            'user[email]' => 'usertest@gmail.com',
            'user[roles]' => 'ROLE_USER',
        ]);
        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertNotNull($this->userRepository->findOneBy(['username' => 'userTest']));
        $this->assertSame("http://localhost/", $this->client->getCrawler()->getUri());
        $this->assertSelectorTextContains('.alert-success', "L'utilisateur a bien été ajouté.");
    }

    public function testUserCreationWithBadSecondPassword(): void
    {
        $this->client->loginUser($this->admin);
        $this->client->request(
            'POST',
            '/users/create'
        );
        $this->client->submitForm('Ajouter', [
            'user[username]' => 'userTest',
            'user[password][first]' => 'motdepasse',
            'user[password][second]' => 'motdepasse2',
            'user[email]' => 'usertest@gmail.com',
            'user[roles]' => 'ROLE_USER',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorTextContains('li', 'Les deux mots de passe doivent correspondre.');
        $this->assertNull($this->userRepository->findOneByUsername('userTest'));
    }

    public function testAdminCanPromoteAnotherUser(): void
    {
        $this->client->loginUser($this->admin);
        $this->client->request(
            'POST',
            'users/' . $this->user->getId() . '/edit'
        );
        $this->client->submitForm('Modifier', [
            'user[roles]' => 'ROLE_ADMIN',
        ]);
        $userAfterPromotion = $this->userRepository->find($this->user->getId());
        $this->client->followRedirect();

        $this->assertContains('ROLE_ADMIN', $userAfterPromotion->getRoles());
    }

    public function testAdminCanDemoteAnotherUser(): void
    {
        $this->client->loginUser($this->admin);
        $this->client->request(
            'POST',
            'users/' . $this->admin->getId() . '/edit'
        );
        $this->client->submitForm('Modifier', [
            'user[roles]' => 'ROLE_USER',
        ]);
        $userAfterPromotion = $this->userRepository->find($this->admin->getId());
        $this->client->followRedirect();

        $this->assertContains('ROLE_USER', $userAfterPromotion->getRoles());
    }

    public function testSuccessfullEditUser(): void
    {
        $this->client->loginUser($this->admin);
        $this->client->request(
            'POST',
            'users/' . $this->user->getId() . '/edit'
        );
        $this->client->submitForm('Modifier', [
            'user[username]' => 'userTestEdited',
            'user[roles]' => 'ROLE_ADMIN',
        ]);
        $userAfterEdit = $this->userRepository->find($this->user->getId());
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', "L'utilisateur a bien été modifié");
        $this->assertContains('ROLE_ADMIN', $userAfterEdit->getRoles());
        $this->assertSame('userTestEdited', $userAfterEdit->getUsername());
    }

    public function testUsersCannotModifyUsers(): void
    {
        $this->client->loginUser($this->user);
        $this->client->request(
            'POST',
            'users/' . $this->admin->getId() . '/edit'
        );
        $this->assertResponseStatusCodeSame(403);
    }
}
