<?php

namespace Sourceml\Controller\Sources\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\HttpFoundation\Request;

use Sourceml\Entity\App\Configuration;

class ConfigController extends Controller {

    public function indexAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $configurationRepo = $em->getRepository(Configuration::class);
        $lifetime_enabled = $configurationRepo->getConfiguration("sourceml.cache.lifetime_enabled");
        $lifetime_enabled = isset($lifetime_enabled) ? ($lifetime_enabled ? true : false) : false;
        $lifetime = $configurationRepo->getConfiguration("sourceml.cache.lifetime");
        $lifetime = isset($lifetime) ? $lifetime : "72";
        $header_menu_authors = $configurationRepo->getConfiguration("sourceml.header_menu.authors");
        $header_menu_authors = isset($header_menu_authors) ? ($header_menu_authors ? true : false) : false;
        $header_menu_albums = $configurationRepo->getConfiguration("sourceml.header_menu.albums");
        $header_menu_albums = isset($header_menu_albums) ? ($header_menu_albums ? true : false) : false;
        $header_menu_tracks = $configurationRepo->getConfiguration("sourceml.header_menu.tracks");
        $header_menu_tracks = isset($header_menu_tracks) ? ($header_menu_tracks ? true : false) : false;
        $header_menu_sources = $configurationRepo->getConfiguration("sourceml.header_menu.sources");
        $header_menu_sources = isset($header_menu_sources) ? ($header_menu_sources ? true : false) : false;
        if($request->getMethod() == "POST") {
            $data = $request->request->all();
            try {
                $sourceml_config = $this->container->get('sourceml.config');
                $sourceml_config->setMenuHeaderConfig($data);
                $sourceml_config->setCacheConfig($data);
                $this->get('session')->getFlashBag()->add('success', "La configuration a été enregistrée");
                return $this->redirect($this->generateUrl('sourceml_admin_config'));
            }
            catch(\Exception $e) {
                $this->get('session')->getFlashBag()->add('error', $e->getMessage());
            }
        }
        return $this->render(
            'Sources/Admin/config.html.twig',
            array(
                "lifetime_enabled" => $lifetime_enabled,
                "lifetime" => $lifetime,
                "header_menu_authors" => $header_menu_authors,
                "header_menu_albums" => $header_menu_albums,
                "header_menu_tracks" => $header_menu_tracks,
                "header_menu_sources" => $header_menu_sources,
            )
        );
    }

}
