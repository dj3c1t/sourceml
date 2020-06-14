<?php

namespace Sourceml\Service\Sources\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

use Sourceml\Entity\Sources\Author;
use Sourceml\Entity\Sources\Source;

class LoaderListener {

    private $container;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    public function postLoad(LifecycleEventArgs $args) {
        $entity = $args->getEntity();
        if($entity instanceof Author) {
            return $this->loadAuthor($entity);
        }
        if($entity instanceof Source) {
            return $this->loadSource($entity);
        }
    }

    protected function loadAuthor(Author $author) {
        $request_stack = $this->container->get('request_stack');
        $base_url = "";
        if($request = $request_stack->getCurrentRequest()) {
            $base_url = $request->getScheme()."://".$request->getHttpHost();
        }
        $author->setUrl(
            $base_url
            .$this->container->get('router')->generate(
                'author_view',
                array(
                    'author' => $author->getId()
                )
            )
        );
    }

    protected function loadSource(Source $source) {
        $source_manager = $this->container->get('sourceml.source_manager');
        try {
            $source_manager->loadSource($source);
        }
        catch(\Exception $e) {
        }
    }

}
