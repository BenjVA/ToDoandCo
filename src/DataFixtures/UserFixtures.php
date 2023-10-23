<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        for ($i = 0; $i < 10; ++$i) {
            $user = new User();
            $user->setUsername($faker->userName())
                ->setEmail($faker->email())
                ->setPassword($this->passwordHasher->hashPassword($user, 'password'))
                ->setRoles(['ROLE_USER']);
            $this->addReference(self::getReferenceKey($i), $user);
            $manager->persist($user);
        }

        $admin = $this->addAdmin();
        // Create anonymous User (default tasks will be assigned to them)
        $anon = $this->addAnonymousUser();

        $manager->persist($admin);
        $manager->persist($anon);

        $manager->flush();
    }

    //Create 1 admin account, you can set your username, mail, and password
    public function addAdmin(): User
    {
        $admin = new User();
        $admin->setEmail('admin@email.com')
            ->setUsername('admin')
            ->setPassword($this->passwordHasher->hashPassword($admin, 'admin'))
            ->setRoles(['ROLE_ADMIN']);

        return $admin;
    }

    public function addAnonymousUser(): User
    {
        $anon = new User();
        $anon->setEmail('anon@anon.fr')
            ->setUsername("anon")
            ->setPassword($this->passwordHasher->hashPassword($anon, 'anonymouspassword'))
            ->setRoles([]);

        $this->addReference(self::getReferenceKey('anon'), $anon);
        return $anon;
    }

    public static function getReferenceKey($key): string
    {
        return sprintf('user_%s', $key);
    }
}
