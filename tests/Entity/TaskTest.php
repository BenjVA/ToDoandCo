<?php

namespace App\Tests\Entity;

use App\Entity\Task;
use App\Entity\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Group('task')]
#[CoversClass(Task::class)]
final class TaskTest extends KernelTestCase
{
    public function setUp(): void
    {
        self::bootKernel();
    }

    public function testCreateTask(): void
    {
        $user = new User();
        $user->setUsername('usertest')
            ->setPassword('password')
            ->setEmail('usertest@gmail.fr')
            ->setRoles(['ROLE_USER']);
        $this->assertInstanceOf(User::class, $user);
        $this->validateUser($user);

        $task = new Task();
        $task->setTitle('Title test')
            ->setContent('Content test')
            ->setCreatedAt(new \DateTime())
            ->setUser($user);
        $this->assertInstanceOf(Task::class, $task);
        $this->validateTask($task);

        $this->assertSame('Title test', $task->getTitle());
        $this->assertSame($user, $task->getUser());
    }

    public function validateTask(Task $task, int $numberErrors = 0): void
    {
        $errors = self::getContainer()->get(ValidatorInterface::class)->validate($task);
        $messages = [];
        /** @var ConstraintViolation $error */
        foreach ($errors as $error) {
            $messages[] = $error->getPropertyPath() . '=>' . $error->getMessage();
        }
        $this->assertCount($numberErrors, $errors, implode(', ', $messages));
    }

    public function validateUser(User $user, int $numberErrors = 0): void
    {
        $errors = self::getContainer()->get(ValidatorInterface::class)->validate($user);
        $messages = [];
        /** @var ConstraintViolation $error */
        foreach ($errors as $error) {
            $messages[] = $error->getPropertyPath() . '=>' . $error->getMessage();
        }
        $this->assertCount($numberErrors, $errors, implode(', ', $messages));
    }
}
