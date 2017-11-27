<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class Fixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $user = new User();

        $user->setUsername("admin");

        $encoder = $this->container->get('security.password_encoder');
        $password = $encoder->encodePassword($user, 'admin');
        $user->setPassword($password);

        $user->setEmail("admin@admin.com");
        $user->addRole("ROLE_SUPER_ADMIN");
        $user->setFirstname("admin");
        $user->setLastname("admin");
        $user->setEnabled(true);

        $manager->persist($user);
        $manager->flush();
    }
}