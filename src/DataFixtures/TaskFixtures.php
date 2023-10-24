<?php

namespace App\DataFixtures;

use App\Entity\Task;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class TaskFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $anonymousUser = $this->getReference(UserFixtures::getReferenceKey('anon'));

        for ($i = 0; $i < 10; $i++) {
            $createdAt = $faker->dateTimeThisYear();
            $task = new Task();
            $task->setTitle($faker->sentence())
                ->setContent($faker->paragraph())
                ->setCreatedAt($createdAt)
                ->toggle($faker->boolean())
                ->setUser($anonymousUser)
            ;
            $manager->persist($task);
        }
        $testTask = $this->createTestTask();
        $manager->persist($testTask);

        $manager->flush();
    }

    public function createTestTask(): Task
    {
        $anonymousUser = $this->getReference(UserFixtures::getReferenceKey('anon'));
        $faker = Factory::create('fr_FR');

        $createdAt = $faker->dateTimeThisYear();
        $testTask = new Task();
        $testTask->setTitle('TestTitle')
            ->setContent('TestContent')
            ->setCreatedAt($createdAt)
            ->toggle(false)
            ->setUser($anonymousUser);

        return $testTask;
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class
        ];
    }
}
