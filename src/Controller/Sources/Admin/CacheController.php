<?php

namespace Sourceml\Controller\Sources\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class CacheController extends Controller {

    public function emptyAction() {
        $source_cache = $this->container->get('sourceml.source_cache');
        try {
            $source_cache->emptyCache();
            $this->get('session')->getFlashBag()->add('success', "Le cache a été vidé");
            return $this->redirect($this->generateUrl('sourceml_admin_config'));
        }
        catch(\Exception $e) {
            $this->get('session')->getFlashBag()->add('error', $e->getMessage());
        }
        return $this->redirect($this->generateUrl('sourceml_admin_config'));
    }

}
