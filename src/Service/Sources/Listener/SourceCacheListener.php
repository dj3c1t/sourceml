<?php

namespace Sourceml\Service\Sources\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

use Sourceml\Entity\Sources\Author;
use Sourceml\Entity\Sources\Source;
use Sourceml\Entity\Sources\DerivationSource;
use Sourceml\Entity\Sources\Licence;
use Sourceml\Entity\Sources\SourceAuthor;
use Sourceml\Entity\Sources\SourceComposition;
use Sourceml\Entity\Sources\SourceDocument;
use Sourceml\Entity\Sources\SourceInfo;
use Sourceml\Entity\JQFileUpload\Media;

class SourceCacheListener {

    private $container;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    public function postPersist(LifecycleEventArgs $args) {
        $this->checkSourceCacheUpdates($args->getEntity());
    }

    public function postUpdate(LifecycleEventArgs $args) {
        $this->checkSourceCacheUpdates($args->getEntity());
    }


    public function postRemove(LifecycleEventArgs $args) {
        $this->checkSourceCacheUpdates($args->getEntity());
    }

    protected function checkSourceCacheUpdates($entity) {
        $source_cache = $this->container->get('sourceml.source_cache');
        foreach($this->getSourceCacheToUpdate($entity) as $source) {
            $source_cache->deleteCacheFile($source);
        }
    }

    protected function getSourceCacheToUpdate($entity) {
        $sources = array();
        if($entity instanceof Author) {
            foreach($entity->getSources() as $sourceAuthor) {
                $sources[] = $sourceAuthor->getSource();
            }
        }
        if($entity instanceof DerivationSource) {
            $sources[] = $entity->getSource();
        }
        if($entity instanceof Licence) {
            $em = $this->container->get('doctrine')->getManager();
            $sourceRepo = $em->getRepository(Source::class);
            $sources = $sourceRepo->findBy(
                array(
                    "licence" => $entity->getId(),
                )
            );
        }
        if($entity instanceof SourceAuthor) {
            $sources[] = $entity->getSource();
        }
        if($entity instanceof SourceComposition) {
            $sources[] = $entity->getSource();
            $sources[] = $entity->getComposition();
        }
        if($entity instanceof SourceDocument) {
            $sources[] = $entity->getSource();
        }
        if($entity instanceof SourceInfo) {
            $sources[] = $entity->getSource();
        }
        if($entity instanceof Source) {
            $sources[] = $entity;
        }
        if($entity instanceof Media) {
            $em = $this->container->get('doctrine')->getManager();
            $sourceRepo = $em->getRepository(Source::class);
            if($source = $sourceRepo->getMediaSource($entity)) {
                $sources[] = $source;
            }
        }
        return $sources;
    }

}
