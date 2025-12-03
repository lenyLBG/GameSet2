<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker\Factory;

class UserFixtures extends Fixture
{
    public const USER_ADMIN = 'user-admin';
    public const USER_REFERENCE = 'user-';

    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Admin user
        $admin = new User();
        $admin->setEmail('admin@gameset.com');
        $admin->setNom('Admin');
        $admin->setPrenom('Super');
        $admin->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setLicence('ADM' . rand(1000, 9999));
        $manager->persist($admin);
        $this->addReference(self::USER_ADMIN, $admin);

        // Regular users
        for ($i = 1; $i <= 30; $i++) {
            $user = new User();
            $user->setEmail($faker->unique()->email());
            $user->setNom($faker->lastName());
            $user->setPrenom($faker->firstName());
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
            
            // 70% have a licence
            if ($faker->boolean(70)) {
                $user->setLicence(strtoupper($faker->lexify('???')) . rand(1000, 9999));
            }
            
            $manager->persist($user);
            $this->addReference(self::USER_REFERENCE . $i, $user);
        }

        $manager->flush();
    }
}
