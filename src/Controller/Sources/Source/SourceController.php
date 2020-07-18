<?php

namespace Sourceml\Controller\Sources\Source;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\HttpFoundation\Request;

use Sourceml\Http\REST\RestResponse;
use Sourceml\Entity\Sources\Source;
use Sourceml\Entity\Sources\Author;

class SourceController extends Controller {

    public function indexAction(Request $request, $sourceType) {
        $em = $this->get('doctrine')->getManager();
        $sm = $this->get('sourceml.source_manager');
        $queryParams = array(
            "sourceType" => $sourceType,
        );
        if($request->query->get('author')) {
            $queryParams['author'] = $request->query->get('author');
            $queryParams['isReference'] = false;
        }
        if($request->query->get('composition')) {
            $queryParams['composition'] = $request->query->get('composition');
        }
        if(isset($queryParams['composition'])) {
            $sources = $sm->getSourceQuery($queryParams)->getResult();
        }
        else {
            $sources = $this->get('knp_paginator')->paginate(
                $sm->getSourceQuery($queryParams),
                $request->query->getInt('page', 1),
                10
            );
        }
        $authorRepo = $em->getRepository(Author::class);
        $authors = $authorRepo->findAll();
        $compositions = array();
        $compositionType = null;
        switch($sourceType) {
            case "track":
                $compositionType = $sm->getSourceType("album");
                break;
            case "source":
                $compositionType = $sm->getSourceType("track");
                break;
        }
        if(isset($compositionType)) {
            $compositions = $sm->getSourceQuery(
                array(
                    "sourceType" => $compositionType->getName(),
                    "isReference" => false,
                )
            )->getResult();
        }
        return $this->render(
            'Sources/Source/Source/index.html.twig',
            array(
                'sourceType' => $sourceType,
                'sources' => $sources,
                'authors' => $authors,
                'compositions' => $compositions,
            )
        );
    }

    public function viewAction(Request $request, Source $source) {
        return $this->render(
            'Sources/Source/Source/page.html.twig',
            array(
                'source' => $source->getReference() ? $source->getReference() : $source,
            )
        );
    }

    public function compositionSourcesAction(Request $request, Source $source) {
        $sources = array();
        if($source->isReference()) {
            $source_xml_parser = $this->container->get('sourceml.source_xml_parser');
            foreach($source->getReference()->getSources() as $sourceComposition) {
                $sources[] = $source_xml_parser->readSourceFromXML(
                    $sourceComposition->getSource()->getXmlUrl()
                );
            }
        }
        else {
            $sources = $this->get('sourceml.source_manager')->getSourceQuery(
                array(
                    "composition" => $source->getId(),
                )
            )->getResult();
        }
        return new RestResponse(
            json_encode(
                array(
                    "error" => false,
                    "data" => array(
                        "source_list" => $this->get('twig')->render(
                            'Sources/Source/Source/list.html.twig',
                            array(
                                "sources" => $sources
                            )
                        )
                    )
                )
            )
    	);
    }

    public function sourceDerivationsAction(Request $request, Source $source) {
        $sources = array();
        if(!$source->isReference()) {
            $sources = $this->get('sourceml.source_manager')->getDerivations($source);
        }
        return new RestResponse(
            json_encode(
                array(
                    "error" => false,
                    "data" => array(
                        "source_list" => $this->get('twig')->render(
                            'Sources/Source/Source/list.html.twig',
                            array(
                                "sources" => $sources
                            )
                        )
                    )
                )
            )
        );
    }

    public function toggleAutoPlayNextTrackAction(Request $request) {
        $this->get('session')->set(
            'auto_play_next_track',
            !$this->get('session')->get(
                'auto_play_next_track',
                false
            )
        );
        return new RestResponse(
            json_encode(
                array(
                    "error" => false,
                    "data" => $this->get('session')->get('auto_play_next_track', false) ? 'true' : 'false',
                )
            )
        );
    }

}
