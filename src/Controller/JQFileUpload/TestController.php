<?php

namespace Sourceml\Controller\JQFileUpload;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TestController extends Controller {

    public function indexAction(Request $request) {
        return new Response("");
        return $this->render(
            'JQFileUpload/sample.html.twig',
            array(
            )
        );
    }

}
