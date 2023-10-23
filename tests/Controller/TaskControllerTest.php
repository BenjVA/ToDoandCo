<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\TaskController;
use App\Entity\Task;
use App\Entity\User;
use App\Form\TaskType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Group('task')]
#[CoversClass(TaskController::class)]
#[UsesClass(TaskType::class)]
#[CoversFunction('listAction')]
#[CoversFunction('createAction')]
#[CoversFunction('toggleTaskAction')]
#[CoversFunction('deleteTaskAction')]
final class TaskControllerTest extends WebTestCase
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

        $this->taskRepository = $this->client->getContainer()->get(
            'doctrine.orm.entity_manager'
        )->getRepository(Task::class);

        $this->user = $this->userRepository->findOneByEmail('admin@email.com');
    }

    public function testAccessTasksListWhenLoggedIn(): void
    {
        $this->client->loginUser($this->user);
        $this->client->request(
            Request::METHOD_GET,
            $this->urlGenerator->generate('task_list')
        );

        $this->assertResponseIsSuccessful();
        $this->assertSame('http://localhost/tasks', $this->client->getCrawler()->getUri());
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testAccessTasksListWhenLoggedInViaLink(): void
    {
        $this->client->loginUser($this->user);
        $this->client->request(
            Request::METHOD_GET,
            $this->urlGenerator->generate('homepage')
        );

        $this->client->clickLink('Consulter la liste des tâches à faire');

        $this->assertResponseIsSuccessful();
        $this->assertSame('http://localhost/tasks', $this->client->getCrawler()->getUri());
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testAccessTasksListWhenNotLoggedIn(): void
    {
        $this->client->request(
            Request::METHOD_GET,
            $this->urlGenerator->generate('task_list')
        );

        $this->assertResponseRedirects('http://localhost/login', 302);
    }

    public function testCannotAccessCreateTaskNotLoggedIn(): void
    {
        $this->client->request(
            Request::METHOD_GET,
            $this->urlGenerator->generate('task_create')
        );

        $this->assertResponseRedirects('http://localhost/login', 302);
    }

    public function testAccessCreateTaskLoggedIn(): void
    {
        $this->client->loginUser($this->user);
        $this->client->request(
            Request::METHOD_GET,
            $this->urlGenerator->generate('task_create')
        );

        $this->assertResponseIsSuccessful();
        $this->assertSame('http://localhost/tasks/create', $this->client->getCrawler()->getUri());
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testUsersCreateNewTask(): void
    {
        $this->client->loginUser($this->user);
        $crawler = $this->client->request(
            'GET',
            '/tasks/create'
        );

        $form = $crawler->selectButton('Ajouter')->form();
        $form['task[title]'] = 'Tache';
        $form['task[content]'] = 'Contenu';

        $this->client->submit($form);
        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();
    }
}
