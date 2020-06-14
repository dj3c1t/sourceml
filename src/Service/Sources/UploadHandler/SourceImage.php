<?php

namespace Sourceml\Service\Sources\UploadHandler;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

use Sourceml\Entity\Sources\Source;

class SourceImage {

    private $container;
    private $source;
    private $image;
    private $em;
    private $sm;
    private $sourceRepo;
    private $allowedFiles;

    public function __construct(Container $container, $allowedFiles) {
        $this->container = $container;
        $this->allowedFiles = explode(",", $allowedFiles);
        foreach($this->allowedFiles as $i => $extension) {
            $this->allowedFiles[$i] = trim($extension);
        }
    }

    public function getMediaDir() {
        return "images/sources/".$this->source->getId();
    }

    public function getAllowedFiles() {
        return $this->allowedFiles;
    }

    public function init($id) {
        if(!isset($id)) {
            throw new \Exception("missing parameter id");
        }
        $this->em = $this->container->get('doctrine')->getManager();
        $this->sm = $this->container->get('sourceml.source_manager');
        $this->sourceRepo = $this->em->getRepository(Source::class);
        if(!($this->source = $this->sourceRepo->find($id))) {
            throw new \Exception("unable to load source infos");
        }
        if(
            !$this->container->get('security.authorization_checker')->isGranted(
                'IS_AUTHENTICATED_REMEMBERED'
            )
        ) {
            throw new \Exception("you must be logged in");
        }
        if(!$this->sm->userCan("edit", $this->source)) {
            throw new \Exception("access denied");
        }
        $this->image = $this->source->getImage();
    }

    public function get() {
        $medias = array();
        if(isset($this->image)) {
            $medias[] = $this->image;
        }
        return $medias;
    }

    public function post($media) {
        if(isset($this->image)) {
            return $media->setError("source already have an image");
        }
        if($thumbnail = $media->getThumbnail()) {
            $this->em->persist($thumbnail);
        }
        $this->em->persist($media);
        $this->source->setImage($media);
        $this->em->flush();
        return $media;
    }

    public function delete($media) {
        if(!isset($this->image)) {
            return $media->setError("no image to delete");
        }
        if($this->image->getId() != $media->getId()) {
            throw new \Exception("delete refused");
        }
        $this->source->setImage(null);
        $this->em->remove($this->image);
        if($thumbnail = $this->image->getThumbnail()) {
            $this->em->remove($thumbnail);
        }
        $this->em->flush();
        return $media;
    }

}
