<?php

namespace Sourceml\Service\JQFileUpload;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

use Sourceml\Entity\JQFileUpload\Media;

class MediaLoader {

    private $container;
    private $request_stack;

    public function __construct(Container $container) {
        $this->container = $container;
        $this->request_stack = $this->container->get('request_stack');
    }

    public function postLoad(LifecycleEventArgs $args) {
        $media = $args->getEntity();
        $em = $args->getEntityManager();
        if($media instanceof Media) {
            $upload_manager = $this->container->get('jq_file_upload.upload_manager');
            $base_url = "/";
            if($request = $this->request_stack->getCurrentRequest()) {
                $base_url = $request->getScheme()."://".$request->getHttpHost().$request->getBasePath()."/";
            }
            $media->setUrl($base_url.$upload_manager->getMediaRootDir()."/".$media->getName());
        }
    }

}
