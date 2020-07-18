<?php

namespace Sourceml\Controller\App\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormError;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;

use Sourceml\Entity\App\User;
use Sourceml\Form\App\Type\UserType;

class UserController extends Controller {

    protected $encoderFactory;

    public function __construct(EncoderFactory $encoderFactory) {
        $this->encoderFactory = $encoderFactory;
    }

    public function indexAction(Request $request) {
        $em = $this->get('doctrine')->getManager();
        $query = $em->createQuery(
            "SELECT u FROM Sourceml\Entity\App\User u"
        );
        $pagination = $this->get('knp_paginator')->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );
        return $this->render(
            'App/Admin/User/index.html.twig',
            array(
                'pagination' => $pagination
            )
        );
    }

    public function addAction(Request $request) {
        $em = $this->get('doctrine')->getManager();
        $user = new User();
        $user->setIsActive(true);
        $form = $this->createForm(
            UserType::class,
            $user,
            array(
                'withAdminFields' => true,
            )
        );
        $form->remove('changePassword');
        $form->handleRequest($request);
        if($form->isSubmitted()) {
            if($form->isValid()) {
                try {
                    $encoder = $this->encoderFactory->getEncoder($user);
                    $user->setPassword($encoder->encodePassword($user->getPassword(), $user->getSalt()));
                    $user->setSalt("");
                    $em->persist($user);
                    $em->flush();
                    $this->get('session')->getFlashBag()->add('success', "L'utilisateur a été ajouté");
                    return $this->redirect($this->generateUrl('admin_user_list'));
                }
                catch(\Exception $e) {
                    $this->get('session')->getFlashBag()->add('error', $e->getMessage());
                }
            }
        }
        return $this->render(
            'App/Admin/User/add.html.twig',
            array(
                "form" => $form->createView(),
            )
        );
    }

    public function editAction(Request $request, User $user) {
        $oldPassword = $user->getPassword();
        $form = $this->createForm(
            UserType::class,
            $user,
            array(
                'validation_groups' => array("edit"),
                'withAdminFields' => true,
            )
        );
        $form->handleRequest($request);
        if($form->isSubmitted()) {
            if($form->isValid()) {
                if($form->get('changePassword')->getData()) {
                    if($user->getPassword()) {
                        $encoder = $this->encoderFactory->getEncoder($user);
                        $user->setPassword(
                            $encoder->encodePassword($user->getPassword(), $user->getSalt())
                        );
                    }
                    else {
                        $form->get('password')->addError(new FormError('Merci de préciser un mot de passe'));
                    }
                }
                else {
                    $user->setPassword($oldPassword);
                }
                if($form->isValid()) {
                    try {
                        $em = $this->get('doctrine')->getManager();
                        $em->flush();
                        $this->get('session')->getFlashBag()->add('success', "L'utilisateur a été enregistré");
                        return $this->redirect(
                            $this->generateUrl(
                                'admin_user_edit',
                                array(
                                    "user" => $user->getId(),
                                )
                            )
                        );
                    }
                    catch(\Exception $e) {
                        $this->get('session')->getFlashBag()->add('error', $e->getMessage());
                    }
                }
            }
        }
        return $this->render(
            'App/Admin/User/edit.html.twig',
            array(
                "form" => $form->createView(),
            )
        );
    }

    public function toogleActiveAction(Request $request, User $user) {
        $em = $this->get('doctrine')->getManager();
        $user->setIsActive(!$user->getIsActive());
        try {
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', "L'utilisateur a été ".($user->getIsActive() ? "activé" : "désactivé"));
        }
        catch(\Exception $e) {
            $this->get('session')->getFlashBag()->add('error', $e->getMessage());
        }
        return $this->redirect($this->generateUrl('admin_user_list'));
    }

    public function deleteAction(Request $request, User $user) {
        $em = $this->get('doctrine')->getManager();
        $em->remove($user);
        try {
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', "L'utilisateur a été supprimé");
        }
        catch(\Exception $e) {
            $this->get('session')->getFlashBag()->add('error', $e->getMessage());
        }
        return $this->redirect($this->generateUrl('admin_user_list'));
    }

}
