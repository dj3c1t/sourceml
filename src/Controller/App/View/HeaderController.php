<?php

namespace Sourceml\Controller\App\View;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sourceml\Entity\App\Configuration;
use Sourceml\Repository\App\DefaultConfiguration;

class HeaderController extends Controller {

    public function displayAction() {
        $em = $this->getDoctrine()->getManager();
        $configurationRepo = $em->getRepository(Configuration::class);
        if(!($site_title = $configurationRepo->getConfiguration("site_title"))) {
            $site_title = DefaultConfiguration::getDefaultValue("site_title");
        }
        return $this->render(
            'App/header.html.twig',
            array(
                "site_title" => $site_title,
                "menu" => $this->get("sourceml_app.menus")->getMenuByName('header'),
            )
        );
    }

}
