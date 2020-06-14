<?php

namespace Sourceml\DataFixtures\Sources\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

use Sourceml\Entity\Sources\SourceType;
use Sourceml\Entity\Sources\AuthorRole;

class LoadSourceMLBaseData extends Fixture {

    public function load(ObjectManager $manager) {
        $this->loadSourceTypes($manager);
        $this->loadAuthorRoles($manager);
    }

    public function loadSourceTypes(ObjectManager $manager) {
        $sourceTypes = array(
            1 => "album",
            2 => "track",
            3 => "source",
        );
        foreach($sourceTypes as $id => $sourceTypeName) {
            $sourceType = new SourceType();
            $metadata = $manager->getClassMetaData(get_class($sourceType));
            $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
            $metadata->setIdGenerator(new \Doctrine\ORM\Id\AssignedGenerator());
            $sourceType->setId($id);
            $sourceType->setName($sourceTypeName);
            $manager->persist($sourceType);
        }
        $manager->flush();
    }

    public function loadAuthorRoles(ObjectManager $manager) {
        $authorRoles = array(
            1 => "admin",
            2 => "editor",
            3 => "contributor",
        );
        foreach($authorRoles as $id => $authorRoleName) {
            $authorRole = new AuthorRole();
            $metadata = $manager->getClassMetaData(get_class($authorRole));
            $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
            $metadata->setIdGenerator(new \Doctrine\ORM\Id\AssignedGenerator());
            $authorRole->setId($id);
            $authorRole->setName($authorRoleName);
            $manager->persist($authorRole);
        }
        $manager->flush();
    }

}
