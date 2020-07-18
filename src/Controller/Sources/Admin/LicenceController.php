<?php

namespace Sourceml\Controller\Sources\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\HttpFoundation\Request;

use Sourceml\Entity\Sources\Licence;
use Sourceml\Form\Sources\Type\LicenceType;

class LicenceController extends Controller {

    public function indexAction(Request $request) {
        $em = $this->get('doctrine')->getManager();
        $query = $em->createQuery("
            SELECT l FROM Sourceml\Entity\Sources\Licence l
        ");
        $licences = $this->get('knp_paginator')->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );
        return $this->render(
            'Sources/Admin/Licence/index.html.twig',
            array(
                "licences" => $licences
            )
        );
    }

    public function addAction(Request $request) {
        $em = $this->get('doctrine')->getManager();
        $licence = new Licence();
        $form = $this->createForm(
            LicenceType::class,
            $licence
        );
        $form->handleRequest($request);
        if($form->isSubmitted()) {
            if($form->isValid()) {
                try {
                    $em->persist($licence);
                    $em->flush();
                    $upload_manager = $this->get('jq_file_upload.upload_manager');
                    $upload_manager->init("sourceml_licence_logo", $licence->getId());
                    $media = $upload_manager->makeMediaFromFiles("logo");
                    if(isset($media)) {
                        if($error = $media->getError()) {
                            $em->remove($licence);
                            $em->flush();
                            throw new \Exception($error);
                        }
                        $em->persist($media);
                        $licence->setImage($media);
                        $em->flush();
                    }
                    $this->get('session')->getFlashBag()->add('success', "La licence a été ajoutée");
                    return $this->redirect($this->generateUrl('sourceml_admin_licence_index'));
                }
                catch(\Exception $e) {
                    $this->get('session')->getFlashBag()->add('error', $e->getMessage());
                }
            }
        }
        return $this->render(
            'Sources/Admin/Licence/add.html.twig',
            array(
                "form" => $form->createView(),
                "licence" => $licence,
            )
        );
    }

    public function editAction(Request $request, Licence $licence) {
        $em = $this->get('doctrine')->getManager();
        $form = $this->createForm(
            LicenceType::class,
            $licence
        );
        $form->handleRequest($request);
        if($form->isSubmitted()) {
            if($form->isValid()) {
                try {
                    $em->persist($licence);
                    $em->flush();
                    $this->get('session')->getFlashBag()->add('success', "La licence a été enregistrée");
                    return $this->redirect($this->generateUrl('sourceml_admin_licence_index'));
                }
                catch(\Exception $e) {
                    $this->get('session')->getFlashBag()->add('error', $e->getMessage());
                }
            }
        }
        return $this->render(
            'Sources/Admin/Licence/edit.html.twig',
            array(
                "form" => $form->createView(),
                "licence" => $licence,
            )
        );
    }

    public function deleteAction(Request $request, Licence $licence) {
        $em = $this->get('doctrine')->getManager();
        $sm = $this->get('sourceml.source_manager');
        try {
            if($sm->licenceHasSources($licence)) {
                throw new \Exception("some sources use this licence");
            }
            if($media = $licence->getImage()) {
                $upload_manager = $this->container->get('jq_file_upload.upload_manager');
                $upload_manager->init("sourceml_licence_logo", $licence->getId());
                if(!$upload_manager->delete_file(basename($media->getName()))) {
                    throw new \Exception("unable to delete licence image file");
                }
                if($thumbnail = $media->getThumbnail()) {
                    $media->setThumbnail(null);
                    $em->remove($thumbnail);
                }
                $licence->setImage(null);
                $em->remove($media);
            }
            $em->remove($licence);
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', "La licence a été supprimée");
        }
        catch(\Excption $e) {
            $this->get('session')->getFlashBag()->add('error', $e->getMessage());
        }
        return $this->redirect($this->generateUrl('sourceml_admin_licence_index'));
    }

}
