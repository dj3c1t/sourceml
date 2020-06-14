<?php

namespace Sourceml\DataFixtures\App\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

use Sourceml\Entity\App\Role;

class LoadAppBaseData extends Fixture {

    public function load(ObjectManager $manager) {
        $adminRole = new Role();
        $adminRole->setName('admin');
        $adminRole->setRole('ROLE_ADMIN');
        $manager->persist($adminRole);
        $adminRole = new Role();
        $adminRole->setName('user');
        $adminRole->setRole('ROLE_USER');
        $manager->persist($adminRole);
        $manager->flush();
    }

}
