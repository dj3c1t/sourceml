<?php

namespace Sourceml\Controller\Sources\Account;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Sourceml\Entity\Sources\Source;
use Sourceml\Entity\Sources\SourceDocument;
use Sourceml\Http\REST\RestResponse;

class DocumentController extends Controller {

    public function addAction(Request $request) {
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
        $sm = $this->get('sourceml.source_manager');
        if(!$sm->userCan("edit", $source)) {
            return new RestResponse(json_encode(array(
                "error" => "your are not allowed to edit this source"
            )));
        }

        $sourceDocument = new SourceDocument();
        $sourceDocument->setSource($source);
        $sourceDocument->setName("");
        $em->persist($sourceDocument);
        $source->addDocument($sourceDocument);
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
                    "data" => array(
                        "document_form" => $this->get('twig')->render(
                            'Sources/Account/Form/document.html.twig',
                            array(
                                "document" => $sourceDocument,
                                "source" => $source
                            )
                        ),
                        "document_id" => $sourceDocument->getId()
                    )
                )
            )
    	);
    }

    public function saveAction(Request $request) {
        $data = $request->request->all();
        if(
                !isset($data["id"])
            ||  !isset($data["document_type"])
            ||  !isset($data["document_name"])
            ||  !isset($data["document_url"])
        ) {
            return new RestResponse(json_encode(array(
                "error" => "missing parameter"
            )));
        }
        $em = $this->get('doctrine')->getManager();
        $documentRepo = $em->getRepository(SourceDocument::class);
        if(!($document = $documentRepo->find($data["id"]))) {
            return new RestResponse(json_encode(array(
                "error" => "can't load document informations"
            )));
        }
        $sm = $this->get('sourceml.source_manager');
        if(!$sm->userCan("edit", $document->getSource())) {
            return new RestResponse(json_encode(array(
                "error" => "your are not allowed to edit this source"
            )));
        }
        $document->setName($data["document_name"]);
        if($data["document_type"] == "url") {
            $document->setUrl($data["document_url"]);
        }
        else {
            $document->setUrl(null);
        }
        $sw = $this->container->get('sourceml.source_waveform');
        try {
            $sw->updateWaveform($document->getSource(), true);
        }
        catch(\Exception $e) {
        }
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
        $documentRepo = $em->getRepository(SourceDocument::class);
        if(!($document = $documentRepo->find($data["id"]))) {
            return new RestResponse(json_encode(array(
                "error" => "can't load document informations"
            )));
        }
        $sm = $this->get('sourceml.source_manager');
        if(!$sm->userCan("edit", $document->getSource())) {
            return new RestResponse(json_encode(array(
                "error" => "your are not allowed to edit this source"
            )));
        }
        if(($media = $document->getMedia()) !== null) {
            $upload_manager = $this->get('jq_file_upload.upload_manager');
            $upload_manager->init("sourceml_source_document", $document->getId());
            if(!$upload_manager->delete_file(basename($media->getName()))) {
                return new RestResponse(json_encode(array(
                    "error" => "unable to delete file"
                )));
            }
            if($thumbnail = $media->getThumbnail()) {
                $media->setThumbnail(null);
                $em->remove($thumbnail);
            }
            $document->setMedia(null);
            $em->remove($media);
        }
        $source = $document->getSource();
        $source->removeDocument($document);
        $em->remove($document);
        $sw = $this->container->get('sourceml.source_waveform');
        try {
            $sw->updateWaveform($source, true);
        }
        catch(\Exception $e) {
        }
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
