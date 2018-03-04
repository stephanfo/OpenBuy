<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Config;
use AppBundle\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class Fixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        // Create Admin user
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

        $config = new Config();
        $config->setName('InterfaceDigikey');
        $config->setParameters(array(
            'redirectUri' => "https://openbuy.localdev/api/digikey/code",
            'clientId' => "Please change this one with your Client ID",
            'clientSecret' => "Please change this one with your Client Secret",
        ));
        $manager->persist($config);

        $manager->flush();
    }
}