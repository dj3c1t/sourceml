<?php

namespace Sourceml\Controller\Sources\Source;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\HttpFoundation\Request;

use Sourceml\Entity\Sources\Author;

class AuthorController extends Controller {

    public function indexAction(Request $request) {
        $em = $this->get('doctrine')->getManager();
        $query = $em->createQuery("
            SELECT a FROM Sourceml\Entity\Sources\Author a
            ORDER BY a.publicationDate DESC
        ");
        $authors = $this->get('knp_paginator')->paginate(
            $query,
            $request->query->getInt('page', 1),
            36
        );
        return $this->render(
            'Sources/Source/Author/index.html.twig',
            array(
                'authors' => $authors
            )
        );
    }

    public function viewAction(Author $author) {
        $albums = $query = $this->get('sourceml.source_manager')->getSourceQuery(
            array(
                "sourceType" => "album",
                "author" => $author->getId(),
            )
        )->getResult();
        return $this->render(
            'Sources/Source/Author/view.html.twig',
            array(
                "author" => $author,
                'albums' => $albums
            )
        );
    }

}
