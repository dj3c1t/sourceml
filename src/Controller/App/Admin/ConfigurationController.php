<?php

namespace Sourceml\Controller\App\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\HttpFoundation\Request;

class ConfigurationController extends Controller {

    public function indexAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $configurationRepo = $em->getRepository(\Sourceml\Entity\App\Configuration::class);
        $configuration = $configurationRepo->loadConfiguration(
            $configurationRepo->getDefaultValues()
        );
        if($request->getMethod() == 'POST') {
            $data = $request->request->all();
            $HAS_ERROR = false;
            if(
                !isset($data["site_title"])
            ) {
                $this->get('session')->getFlashBag()->add('error', 'parametre manquant');
                $HAS_ERROR = true;
            }
            if(!$HAS_ERROR) {
                $configuration["site_title"] = $data["site_title"];
                $configurationEntity = $configurationRepo->setConfiguration('site_title', $data["site_title"]);
                try {
                    $em->persist($configurationEntity);
                    $em->flush();
                }
                catch(\Exception $e) {
                    $this->get('session')->getFlashBag()->add('error', "impossible d'enregistrer la configuration");
                    $HAS_ERROR = true;
                }
            }
            if(!$HAS_ERROR) {
                $this->get('session')->getFlashBag()->add('success', "la configuration a été enregistrée");
                return $this->redirect($this->generateUrl('admin_configuration'));
            }
        }
        return $this->render(
            'App/Admin/Configuration/index.html.twig',
            array(
                "configuration" => $configuration,
            )
        );
    }

}
