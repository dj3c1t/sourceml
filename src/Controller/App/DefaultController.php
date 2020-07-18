<?php

namespace Sourceml\Controller\App;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;

class DefaultController extends Controller {

    public function indexAction() {
        $default_route = [
            "route" => "",
            "parameters" => [],
        ];
        $defaultRouteParameter = $this->container->getParameter("default_route");
        if($defaultRouteParameter) {
            if(is_array($defaultRouteParameter)) {
                if(isset($defaultRouteParameter['route'])) {
                    $default_route['route'] = $defaultRouteParameter['route'];
                }
                if(isset($defaultRouteParameter['parameters']) && is_array($defaultRouteParameter['parameters'])) {
                    foreach($defaultRouteParameter['parameters'] as $key => $value) {
                        $default_route['parameters'][$key] = $value;
                    }
                }
            }
            else {
                $default_route['route'] = $defaultRouteParameter;
            }
        }
        return $this->redirect(
            $this->generateUrl($default_route['route'], $default_route['parameters'])
        );
    }

}
