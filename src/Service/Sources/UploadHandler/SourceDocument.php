<?php

namespace Sourceml\Service\Sources\UploadHandler;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

use Sourceml\Entity\Sources\Source;

class SourceDocument {

    private $container;
    private $source;
    private $document;
    private $media;
    private $em;
    private $sm;
    private $sourceRepo;
    private $documentRepo;
    private $allowedFiles;

    public function __construct(Container $container, $allowedFiles) {
        $this->container = $container;
        $this->allowedFiles = explode(",", $allowedFiles);
        foreach($this->allowedFiles as $i => $extension) {
            $this->allowedFiles[$i] = trim($extension);
        }
    }

    public function getMediaDir() {
        return "documents/".$this->document->getId();
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
        $this->documentRepo = $this->em->getRepository(\Sourceml\Entity\Sources\SourceDocument::class);
        if(!($this->document = $this->documentRepo->find($id))) {
            throw new \Exception("unable to load document infos");
        }
        $this->source = $this->document->getSource();
        $this->media = $this->document->getMedia();
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
    }

    public function get() {
        $medias = array();
        if(isset($this->media)) {
            $medias[] = $this->media;
        }
        return $medias;
    }

    public function post($media) {
        if(isset($this->media)) {
            return $media->setError("document already have a file");
        }
        if($thumbnail = $media->getThumbnail()) {
            $this->em->persist($thumbnail);
        }
        $this->em->persist($media);
        $this->em->flush();
        $this->document->setUrl(null);
        $this->document->setMedia($media);
        $sw = $this->container->get('sourceml.source_waveform');
        try {
            $sw->updateWaveform($this->document->getSource());
        }
        catch(\Exception $e) {
        }
        $this->em->flush();
        return $media;
    }

    public function delete($media) {
        if(!isset($this->media)) {
            return $media->setError("no media to delete");
        }
        if($this->media->getId() != $media->getId()) {
            throw new \Exception("delete refused");
        }
        $this->document->setMedia(null);
        $this->em->remove($this->media);
        if($thumbnail = $this->media->getThumbnail()) {
            $this->em->remove($thumbnail);
        }
        $sw = $this->container->get('sourceml.source_waveform');
        try {
            $sw->updateWaveform($this->document->getSource(), true);
        }
        catch(\Exception $e) {
        }
        $this->em->flush();
        return $media;
    }

}
