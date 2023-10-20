<?php

namespace App\DataFixtures;

use App\Entity\Task;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class TaskFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
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
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class
        ];
    }
}
