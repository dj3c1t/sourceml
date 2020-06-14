<?php

namespace Sourceml\Controller\Sources\Account;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Sourceml\Entity\Sources\Source;
use Sourceml\Entity\Sources\DerivationSource;
use Sourceml\Http\REST\RestResponse;

class DerivationController extends Controller {

    public function addAction(Request $request) {
        $sm = $this->get('sourceml.source_manager');
        if(!$sm->canOpenUrl()) {
            return new RestResponse(json_encode(array(
                "error" => 'cannot read external url from the server'
            )));
        }
        $data = $request->request->all();
        if(
                !isset($data["id"])
        ) {
            return new RestResponse(json_encode(array(
                "error" => "missing parameter"
            )));
        }
        $em = $this->get('doctrine')->getManager();
        $sourceRepo = $em->getRepository(Source::class);
        if(!($source = $sourceRepo->find($data["id"]))) {
            return new RestResponse(json_encode(array(
                "error" => "can't load source informations"
            )));
        }
        if(!$sm->userCan("edit", $source)) {
            return new RestResponse(json_encode(array(
                "error" => "your are not allowed to edit this source"
            )));
        }

        $derivationSource = new DerivationSource();
        $derivationSource->setSource($source);
        $derivationSource->setUrl("");
        $em->persist($derivationSource);
        $source->addDerivation($derivationSource);
        try {
            $em->flush();
            $sm->loadSource($source);
        }
        catch(\Exception $e) {
            return new RestResponse(json_encode(array(
                "error" => $e->getMessage()
            )));
        }
        return new RestResponse(
            json_encode(
                array(
                    "error" => false,
                    "data" => array(
                        "derivation_form" => $this->get('twig')->render(
                            'Sources/Account/Form/derivation.html.twig',
                            array(
                                "derivation" => $derivationSource,
                                "source" => $source
                            )
                        ),
                        "derivation_id" => $derivationSource->getId()
                    )
                )
            )
    	);
    }

    public function saveAction(Request $request) {
        $data = $request->request->all();
        if(
                !isset($data["id"])
            ||  !isset($data["derivation_url"])
        ) {
            return new RestResponse(json_encode(array(
                "error" => "missing parameter"
            )));
        }
        $em = $this->get('doctrine')->getManager();
        $derivationRepo = $em->getRepository(DerivationSource::class);
        if(!($derivation = $derivationRepo->find($data["id"]))) {
            return new RestResponse(json_encode(array(
                "error" => "can't load derivation informations"
            )));
        }
        $sm = $this->get('sourceml.source_manager');
        if(!$sm->userCan("edit", $derivation->getSource())) {
            return new RestResponse(json_encode(array(
                "error" => "your are not allowed to edit this source"
            )));
        }
        $derivation->setUrl($data["derivation_url"]);
        try {
            $em->flush();
            $sm->loadSource($derivation->getSource());
        }
        catch(\Exception $e) {
            return new RestResponse(json_encode(array(
                "error" => $e->getMessage()
            )));
        }
        return new RestResponse(
            json_encode(
                array(
                    "error" => false,
                    "data" => array(
                        "derivation_form" => $this->get('twig')->render(
                            'Sources/Account/Form/derivation.html.twig',
                            array(
                                "derivation" => $derivation,
                                "source" => $derivation->getSource()
                            )
                        ),
                        "derivation_id" => $derivation->getId()
                    )

                )
            )
    	);
    }

    public function deleteAction(Request $request) {
        $data = $request->request->all();
        if(
                !isset($data["id"])
        ) {
            return new RestResponse(json_encode(array(
                "error" => "missing parameter"
            )));
        }
        $em = $this->get('doctrine')->getManager();
        $derivationRepo = $em->getRepository(DerivationSource::class);
        if(!($derivation = $derivationRepo->find($data["id"]))) {
            return new RestResponse(json_encode(array(
                "error" => "can't load derivation informations"
            )));
        }
        $sm = $this->get('sourceml.source_manager');
        if(!$sm->userCan("edit", $derivation->getSource())) {
            return new RestResponse(json_encode(array(
                "error" => "your are not allowed to edit this source"
            )));
        }
        $derivation->getSource()->removeDerivation($derivation);
        $em->remove($derivation);
        try {
            $em->flush();
        }
        catch(\Exception $e) {
            return new RestResponse(json_encode(array(
                "error" => $e->getMessage()
            )));
        }
        return new RestResponse(
            json_encode(
                array(
                    "error" => false,
                    "data" => array()
                )
            )
    	);
    }

}
