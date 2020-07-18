<?php

namespace Sourceml\Controller\Sources\Account;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Sourceml\Entity\Sources\Author;
use Sourceml\Form\Sources\Type\AuthorType;

class AuthorController extends Controller {

    public function indexAction(Request $request) {
        $em = $this->get('doctrine')->getManager();
        $query = $em->createQuery("
            SELECT a FROM Sourceml\Entity\Sources\Author a
            WHERE a.user=:user_id
        ")->setParameter("user_id", $this->getUser()->getId());
        $authors = $this->get('knp_paginator')->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );
        return $this->render(
            'Sources/Account/Author/index.html.twig',
            array(
                'authors' => $authors
            )
        );
    }

    public function addAction(Request $request) {
        $em = $this->get('doctrine')->getManager();
        $author = new Author();
        $author->setHasContactForm(false);
        $author->setUseCaptcha(false);
        $author->setUser($this->getUser());
        $form = $this->createForm(
            AuthorType::class,
            $author
        );
        $form->handleRequest($request);
        if($form->isSubmitted()) {
            if($form->isValid()) {
                try {
                    $em->persist($author);
                    $em->flush();
                    $upload_manager = $this->get('jq_file_upload.upload_manager');
                    $upload_manager->init("sourceml_author_logo", $author->getId());
                    $media = $upload_manager->makeMediaFromFiles("logo");
                    if(isset($media)) {
                        if($error = $media->getError()) {
                            $em->remove($author);
                            $em->flush();
                            throw new \Exception($error);
                        }
                        $em->persist($media);
                        $author->setImage($media);
                        $em->flush();
                    }
                    $this->get('session')->getFlashBag()->add('success', "L'auteur a été ajouté");
                    return $this->redirect($this->generateUrl('account_author_index'));
                }
                catch(\Exception $e) {
                    $this->get('session')->getFlashBag()->add('error', $e->getMessage());
                }
            }
        }
        return $this->render(
            'Sources/Account/Author/add.html.twig',
            array(
                "form" => $form->createView(),
                "author" => $author,
            )
        );
    }

    public function editAction(Request $request, Author $author) {
        if($author->getUser()->getId() != $this->getUser()->getId()) {
            throw new AccessDeniedException("your are not allowed to edit this author");
        }
        $em = $this->get('doctrine')->getManager();
        $form = $this->createForm(
            AuthorType::class,
            $author
        );
        $form->handleRequest($request);
        if($form->isSubmitted()) {
            if($form->isValid()) {
                try {
                    $em->persist($author);
                    $em->flush();
                    $this->get('session')->getFlashBag()->add('success', "L'auteur a été enregistré");
                    return $this->redirect($this->generateUrl('account_author_index'));
                }
                catch(\Exception $e) {
                    $this->get('session')->getFlashBag()->add('error', $e->getMessage());
                }
            }
        }
        return $this->render(
            'Sources/Account/Author/edit.html.twig',
            array(
                "form" => $form->createView(),
                "author" => $author,
            )
        );
    }

    public function deleteAction(Request $request, Author $author) {
        if($author->getUser()->getId() != $this->getUser()->getId()) {
            throw new AccessDeniedException("your are not allowed to delete this author");
        }
        if($this->get('sourceml.source_manager')->authorHasSources($author)) {
            $this->get('session')->getFlashBag()->add('error', "this author has sources");
            return $this->redirect($this->generateUrl('account_author_index'));
        }
        $em = $this->get('doctrine')->getManager();
        if($media = $author->getImage()) {
            $upload_manager = $this->container->get('jq_file_upload.upload_manager');
            $upload_manager->init("sourceml_author_logo", $author->getId());
            if(!$upload_manager->delete_file(basename($media->getName()))) {
                throw new \Exception("unable to delete author image file");
            }
            if($thumbnail = $media->getThumbnail()) {
                $media->setThumbnail(null);
                $em->remove($thumbnail);
            }
            $author->setImage(null);
            $em->remove($media);
        }
        $em->remove($author);
        try {
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', "L'auteur a été supprimé");
        }
        catch(\Excption $e) {
            $this->get('session')->getFlashBag()->add('error', $e->getMessage());
        }
        return $this->redirect($this->generateUrl('account_author_index'));
    }

}
