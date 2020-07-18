<?php

namespace Sourceml\Service\Sources\UploadHandler;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

use Sourceml\Entity\Sources\Author;

class AuthorLogo {

    private $container;
    private $author;
    private $em;
    private $allowedFiles;

    public function __construct(Container $container, string $allowedFiles) {
        $this->container = $container;
        $this->allowedFiles = explode(",", $allowedFiles);
        foreach($this->allowedFiles as $i => $extension) {
            $this->allowedFiles[$i] = trim($extension);
        }
    }

    public function getMediaDir() {
        return "images/authors/".$this->author->getId();
    }

    public function getAllowedFiles() {
        return $this->allowedFiles;
    }

    public function init($id) {
        if(!isset($id)) {
            throw new \Exception("missing parameter id");
        }
        $this->em = $this->container->get('doctrine')->getManager();
        $authorRepo = $this->em->getRepository(Author::class);
        if(!($this->author = $authorRepo->find($id))) {
            throw new \Exception("unable to load author infos");
        }
        if(
            !$this->container->get('security.authorization_checker')->isGranted(
                'IS_AUTHENTICATED_REMEMBERED'
            )
        ) {
            throw new \Exception("you must be logged in");
        }
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        if($this->author->getUser()->getId() != $user->getId()) {
            throw new \Exception("access denied");
        }
    }

    public function get() {
        $medias = array();
        if($media = $this->author->getImage()) {
            $medias[] = $media;
        }
        return $medias;
    }

    public function post($media) {
        if($this->author->getImage()) {
            return $media->setError("author already have an image");
        }
        if($thumbnail = $media->getThumbnail()) {
            $this->em->persist($thumbnail);
        }
        $this->em->persist($media);
        $this->author->setImage($media);
        $this->em->flush();
        return $media;
    }

    public function delete($media) {
        if(!($image = $this->author->getImage())) {
            return $media->setError("no image to delete");
        }
        if($image->getId() != $media->getId()) {
            throw new \Exception("delete refused");
        }
        $this->author->setImage(null);
        $this->em->remove($image);
        if($thumbnail = $image->getThumbnail()) {
            $this->em->remove($thumbnail);
        }
        $this->em->flush();
        return $media;
    }

}
