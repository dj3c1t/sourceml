<?php

namespace Sourceml\Controller\App\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;

class IndexController extends Controller {

    public function indexAction() {
        return $this->redirect($this->generateUrl('admin_configuration'));
    }

}
