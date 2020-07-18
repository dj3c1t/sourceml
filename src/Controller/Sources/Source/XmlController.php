<?php

namespace Sourceml\Controller\Sources\Source;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Sourceml\Entity\Sources\Source;

class XmlController extends Controller {

    public function sourceAction(Request $request, Source $source) {
        $sm = $this->get('sourceml.source_manager');
        if(!($content = $sm->getXmlFromSource($source))) {
            $response = new Response("<source></source>");
        }
        else {
            $response = new Response($content);
        }
        $response->headers->set('Content-Type', 'application/xml');
        return $response;
    }

}
