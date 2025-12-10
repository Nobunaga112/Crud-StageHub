<?php
// src/DataFixtures/UserFixtures.php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        
        // Create Admin user
        $admin = new User();
        $admin->setUsername('admin');
        $admin->setEmail('admin@stagehub.com');
        $admin->setFirstName('System');
        $admin->setLastName('Administrator');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setStatus('active');
        
        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword(
            $admin,
            'admin123'
        );
        $admin->setPassword($hashedPassword);
        
        $manager->persist($admin);

        // Create Staff user
        $staff = new User();
        $staff->setUsername('staff');
        $staff->setEmail('staff@stagehub.com');
        $staff->setFirstName('John');
        $staff->setLastName('Staff');
        $staff->setRoles(['ROLE_STAFF']);
        $staff->setStatus('active');
        
        $hashedPassword = $this->passwordHasher->hashPassword(
            $staff,
            'staff123'
        );
        $staff->setPassword($hashedPassword);
        
        $manager->persist($staff);

        // Create another staff user
        $staff2 = new User();
        $staff2->setUsername('emma');
        $staff2->setEmail('emma@stagehub.com');
        $staff2->setFirstName('Emma');
        $staff2->setLastName('Wilson');
        $staff2->setRoles(['ROLE_STAFF']);
        $staff2->setStatus('active');
        
        $hashedPassword = $this->passwordHasher->hashPassword(
            $staff2,
            'emma123'
        );
        $staff2->setPassword($hashedPassword);
        
        $manager->persist($staff2);

        $manager->flush();
        
        // Add references if needed for other fixtures
        $this->addReference('admin_user', $admin);
        $this->addReference('staff_user', $staff);
        $this->addReference('staff2_user', $staff2);
    }
}