<?php

namespace Sourceml\Controller\App\Account;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormError;

use Sourceml\Form\App\Type\UserType;

class ConfigurationController extends Controller {

    public function indexAction(Request $request) {
        $user = $this->getUser();
        $oldPassword = $user->getPassword();
        $form = $this->createForm(
            UserType::class,
            $user,
            array(
                'validation_groups' => array("edit")
            )
        );
        $form->handleRequest($request);
        if($form->isSubmitted()) {
            if($form->isValid()) {
                if($form->get('changePassword')->getData()) {
                    if($user->getPassword()) {
                        $encoder = $this->get('security.encoder_factory')->getEncoder($user);
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
                        $this->get('session')->getFlashBag()->add('success', "Vos informations ont été enregistrées");
                        return $this->redirect(
                            $this->generateUrl(
                                'account_configuration',
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
            'App/Account/Configuration/index.html.twig',
            array(
                "form" => $form->createView(),
            )
        );
    }

}
