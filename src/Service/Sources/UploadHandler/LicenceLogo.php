<?php

namespace Sourceml\Service\Sources\UploadHandler;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

use Sourceml\Entity\Sources\Licence;

class LicenceLogo {

    private $container;
    private $licence;
    private $em;
    private $allowedFiles;

    public function __construct(Container $container, $allowedFiles) {
        $this->container = $container;
        $this->allowedFiles = explode(",", $allowedFiles);
        foreach($this->allowedFiles as $i => $extension) {
            $this->allowedFiles[$i] = trim($extension);
        }
    }

    public function getMediaDir() {
        return "images/licences/".$this->licence->getId();
    }

    public function getAllowedFiles() {
        return $this->allowedFiles;
    }

    public function init($id) {
        if(!isset($id)) {
            throw new \Exception("missing parameter id");
        }
        $this->em = $this->container->get('doctrine')->getManager();
        $licenceRepo = $this->em->getRepository(Licence::class);
        if(!($this->licence = $licenceRepo->find($id))) {
            throw new \Exception("unable to load licence infos");
        }
        if(
            !$this->container->get('security.authorization_checker')->isGranted(
                'ROLE_ADMIN'
            )
        ) {
            throw new \Exception("you must be admin");
        }
    }

    public function get() {
        $medias = array();
        if($media = $this->licence->getImage()) {
            $medias[] = $media;
        }
        return $medias;
    }

    public function post($media) {
        if($this->licence->getImage()) {
            return $media->setError("licence already have an image");
        }
        if($thumbnail = $media->getThumbnail()) {
            $this->em->persist($thumbnail);
        }
        $this->em->persist($media);
        $this->licence->setImage($media);
        $this->em->flush();
        return $media;
    }

    public function delete($media) {
        if(!($image = $this->licence->getImage())) {
            return $media->setError("no image to delete");
        }
        if($image->getId() != $media->getId()) {
            throw new \Exception("delete refused");
        }
        $this->licence->setImage(null);
        $this->em->remove($image);
        if($thumbnail = $image->getThumbnail()) {
            $this->em->remove($thumbnail);
        }
        $this->em->flush();
        return $media;
    }

}
