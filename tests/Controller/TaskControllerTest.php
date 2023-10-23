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
#[CoversFunction('editTaskAction')]
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

        $this->admin = $this->userRepository->findOneByEmail('admin@email.com');
        $this->user = $this->userRepository->findOneByEmail('nathalie.morin@dbmail.com');
        $this->anonUser = $this->userRepository->findOneByEmail('anon@anon.fr');
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
            'POST',
            '/tasks/create'
        );

        $form = $crawler->selectButton('Ajouter')->form();
        $form['task[title]'] = 'Tache';
        $form['task[content]'] = 'Contenu';
        $this->client->submit($form);
        $this->client->followRedirect();
        $task = $this->taskRepository->findOneBy(['title' => 'Tâche']);

        $this->assertNotNull($task);
        $this->assertSame("http://localhost/tasks", $this->client->getCrawler()->getUri());
        $this->assertSelectorTextContains('.alert-success', "La tâche a été bien été ajoutée.");
        $this->assertResponseIsSuccessful();
    }

    public function testUsersCanEditAnonCreatedTasks(): void
    {
        $this->client->loginUser($this->user);
        $task = $this->taskRepository->findOneByTitle('TestTitle');
        $crawler = $this->client->request(
            'POST',
            '/tasks/' . $task->getId() . '/edit'
        );

        $form = $crawler->selectButton('Modifier')->form();
        $form['task[title]'] = 'TacheEdit';
        $form['task[content]'] = 'ContenuEdit';

        $this->client->submit($form);
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', "La tâche a bien été modifiée.");
        $this->assertNotNull($this->taskRepository->findOneBy(['title' => 'TacheEdit']));
        $this->assertNotNull($this->taskRepository->findOneBy(['content' => 'ContenuEdit']));
        $this->assertNull($this->taskRepository->findOneBy(['title' => 'TestTitle']));
        $this->assertNotEquals(
            $this->taskRepository->findOneBy(['title' => 'TacheEdit'])->getUser(),
            $this->user
        );
    }

    public function testAdminCanDeleteAnonTasks(): void
    {
        $this->client->loginUser($this->admin);
        $task = $this->taskRepository->findOneByTitle('TestTitle');
        $this->client->request(
            'DELETE',
            '/tasks/' . $task->getId() . '/delete'
        );
        $this->client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.alert-success', "La tâche a bien été supprimée.");
        $this->assertNull($this->taskRepository->findOneByTitle('TestTitle'));
    }

    public function testUsersCanToggleTask(): void
    {
        $this->client->loginUser($this->user);
        $task = $this->taskRepository->findOneByTitle('TestTitle');
        $taskStatusBefore = $task->isDone();
        $this->client->request(
            'GET',
            '/tasks/' . $task->getId() . '/toggle'
        );
        $taskStatusAfter = $task->isDone();

        $this->assertNotSame($taskStatusBefore, $taskStatusAfter);
        $this->assertIsBool($taskStatusAfter);
        $this->assertIsBool($taskStatusBefore);
    }

    public function testUsersCanDeleteTheirOwnTask(): void
    {
        $this->testUsersCreateNewTask();
        $task = $this->taskRepository->findOneBy(['title' => 'Tâche']);
        $author = $task->getUser();
        $this->client->loginUser($author);
        $this->client->request('GET', '/tasks/' . $task->getId() . '/delete');
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.alert-success', "La tâche a bien été supprimée.");
        $this->assertNull($this->taskRepository->findOneByTitle('Tâche'));
    }

    public function testUsersCannotDeleteOthersTask(): void
    {
        $this->testUsersCreateNewTask();
        $task = $this->taskRepository->findOneBy(['title' => 'Tâche']);
        $author = $task->getUser();
        $this->client->loginUser($this->admin);
        $this->client->request('GET', '/tasks/' . $task->getId() . '/delete');
        $this->assertNotSame($author, $this->admin);
        $this->assertResponseStatusCodeSame(403);
        $this->assertNotNull($task);
    }
}
