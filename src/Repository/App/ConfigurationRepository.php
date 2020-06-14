<?php

namespace Sourceml\Repository\App;

use Doctrine\ORM\EntityRepository;

use Sourceml\Entity\App\Configuration;

class ConfigurationRepository extends EntityRepository {

    public function setConfiguration($name, $value) {
        if(!($configuration = $this->findOneByName($name))) {
            $configuration = new Configuration();
            $configuration->setName($name);
        }
        $configuration->setValue($value);
        return $configuration;
    }

    public function getConfigurationByName($name) {
        if($configuration = $this->findOneByName($name)) {
            return $configuration->getValue();
        }
        return null;
    }

    public function getConfiguration($name = null) {
        if(isset($name)) {
            return $this->getConfigurationByName($name);
        }
        $configurations = $this->getEntityManager()
            ->createQuery(
                'SELECT c FROM Sourceml:App\Configuration c'
            )
            ->getResult();
        $configuration = array();
        foreach($configurations as $item) {
            $configuration[$item->getName()] = $item->getValue(); 
        }
        return $configuration;
    }

    public function loadConfiguration($configuration = array()) {
        foreach($this->getConfiguration() as $name => $value) {
            $configuration[$name] = $value;
        }
        return $configuration;
    }

    public function getDefaultValues() {
        return DefaultConfiguration::getDefaultValues();
    }

}
