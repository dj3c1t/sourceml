<?php

namespace Sourceml\Controller\Sources\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class IndexController extends Controller {

    public function indexAction() {
        return $this->render(
            'Sources/Admin/index.html.twig',
            array(
            )
        );
    }

}
