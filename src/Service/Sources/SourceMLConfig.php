<?php

namespace Sourceml\Service\Sources;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

use Sourceml\Entity\App\Configuration;
use Sourceml\Entity\Sources\Source;
use Sourceml\Entity\Sources\DerivationSource;

class SourceMLConfig {

    private $container;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    public function setMenuHeaderConfig($data) {
        $em = $this->container->get('doctrine')->getManager();
        $configurationRepo = $em->getRepository(Configuration::class);
        if($data) {
            $em->persist(
                $configurationRepo->setConfiguration(
                    "sourceml.header_menu.authors",
                    isset($data["header_menu_authors"]) ? "1" : "0"
                )
            );
            $em->persist(
                $configurationRepo->setConfiguration(
                    "sourceml.header_menu.albums",
                    isset($data["header_menu_albums"]) ? "1" : "0"
                )
            );
            $em->persist(
                $configurationRepo->setConfiguration(
                    "sourceml.header_menu.tracks",
                    isset($data["header_menu_tracks"]) ? "1" : "0"
                )
            );
            $em->persist(
                $configurationRepo->setConfiguration(
                    "sourceml.header_menu.sources",
                    isset($data["header_menu_sources"]) ? "1" : "0"
                )
            );
            $em->flush();
        }
    }

    public function setCacheConfig($data) {
        $em = $this->container->get('doctrine')->getManager();
        $configurationRepo = $em->getRepository(Configuration::class);
        $lifetime_enabled = $configurationRepo->getConfiguration("sourceml.cache.lifetime_enabled");
        $lifetime_enabled = isset($lifetime_enabled) ? ($lifetime_enabled ? true : false) : false;
        $lifetime = $configurationRepo->getConfiguration("sourceml.cache.lifetime");
        $lifetime = isset($lifetime) ? $lifetime : "72";
        if($data) {
            if(isset($data["lifetime_enabled"])) {
                $lifetime_enabled_conf = $configurationRepo->setConfiguration(
                    "sourceml.cache.lifetime_enabled",
                    "1"
                );
                if(isset($data["lifetime"])) {
                    if(!preg_match("/^[0-9]+$/", $data["lifetime"])) {
                        throw new \Exception("La durée de validité doit être un nombre entier");
                    }
                    $lifetime_conf = $configurationRepo->setConfiguration(
                        "sourceml.cache.lifetime",
                        $data["lifetime"]
                    );
                }
                else {
                    $lifetime_conf = $configurationRepo->setConfiguration(
                        "sourceml.cache.lifetime",
                        $lifetime
                    );
                }
                $em->persist($lifetime_enabled_conf);
                $em->persist($lifetime_conf);
            }
            else {
                $lifetime_enabled_conf = $configurationRepo->setConfiguration(
                    "sourceml.cache.lifetime_enabled",
                    "0"
                );
                $em->persist($lifetime_enabled_conf);
            }
            $em->flush();
        }
    }

    public function emptyCache() {
        $source_cache = $this->container->get('sourceml.source_cache');
        $source_cache->emptyCache();
    }

}
