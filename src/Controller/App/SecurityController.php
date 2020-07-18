<?php

namespace Sourceml\Controller\App;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Request;

class SecurityController extends Controller {

    public function loginAction() {
        $authenticationUtils = $this->get('security.authentication_utils');
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        if($error) {
            $this->get('session')->getFlashBag()->add('warning', $error->getMessage());
        }
        return $this->render(
            'App/Security/login.html.twig',
            array(
                'last_username' => $lastUsername,
            )
        );
    }

}
