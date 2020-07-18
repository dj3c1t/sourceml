<?php

namespace Sourceml\Controller\App\View;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\HttpFoundation\Response;

class ColonneController extends Controller {

    public function displayAction($route, $parameters) {
        return $this->render(
            'App/sidebar.html.twig',
            array(
                "route" => $route,
                "parameters" => $parameters,
                "menu" => $this->get("sourceml_app.menus")->getMenu($route),
            )
        );
    }

}
