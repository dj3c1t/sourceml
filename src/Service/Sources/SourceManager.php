<?php

namespace Sourceml\Service\Sources;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\HttpFoundation\RequestStack;

use Sourceml\Entity\App\User;
use Sourceml\Entity\App\Configuration;
use Sourceml\Entity\Sources\Source;
use Sourceml\Entity\Sources\SourceType;
use Sourceml\Entity\Sources\Author;
use Sourceml\Entity\Sources\AuthorRole;
use Sourceml\Entity\Sources\SourceDocument;
use Sourceml\Entity\Sources\SourceComposition;
use Sourceml\Entity\Sources\DerivationSource;
use Sourceml\Entity\Sources\Licence;

class SourceManager {

    private $container;
    private $sourceRepo;
    private $user;

    public function __construct(Container $container) {
        $this->container = $container;
        $em = $this->container->get('doctrine')->getManager();
        $im = $this->container->get('sourceml_app.install_manager');
        try {
            if(!$im->runInstaller()) {
                $this->sourceRepo = $em->getRepository(Source::class);
            }
        }
        catch (\Exception $e) {
        }
        $this->refreshUser();
    }

    public function refreshUser() {
        $this->user = null;
        if($token = $this->container->get('security.token_storage')->getToken()) {
            $this->user = $token->getUser();
        }
    }

    public function canOpenUrl() {
        $em = $this->container->get('doctrine')->getManager();
        $configurationRepo = $em->getRepository(Configuration::class);
        $configValue = $configurationRepo->getConfiguration("sourceml.status.url_open");
        if(isset($configValue)) {
            return $configValue;
        }
        $configuration = new Configuration();
        $configuration->setName("sourceml.status.url_open");
        $request_stack = $this->container->get('request_stack');
        $base_url = "";
        if($request = $request_stack->getCurrentRequest()) {
           $base_url = $request->getScheme()."://".$request->getHttpHost();
        }
        $statusUrl = $base_url.$this->container->get('router')->generate(
            'sourceml_status_url_open'
        );
        $canOpenUrl = true;
        if($canOpenUrl && !($content = @file_get_contents($statusUrl))) {
            $canOpenUrl = false;
        }
        if($canOpenUrl && !($response = @json_decode($content, true))) {
            $canOpenUrl = false;
        }
        if($canOpenUrl && !isset($response['status'])) {
            $canOpenUrl = false;
        }
        $configuration->setValue($canOpenUrl ? "1" : "0");
        $em->persist($configuration);
        try {
            $em->flush();
        }
        catch(\Exception $e) {
        }
        return $configuration->getValue();
    }

    public function userCan($action, Source $source) {
        if(!isset($this->user)) {
            return false;
        }
        return $this->sourceRepo->userCan($action, $this->user, $source);
    }

    public function getSourceQuery($params) {
        return $this->sourceRepo->getSourceQuery($params);
    }

    public function getDocumentUrl(SourceDocument $sourceDocument) {
        if(($url = $sourceDocument->getUrl()) !== null) {
            return $url;
        }
        if(($media = $sourceDocument->getMedia()) !== null) {
            $upload_manager = $this->container->get('jq_file_upload.upload_manager');
            if($request = $this->container->get('request')) {
                return
                    $request->getScheme().'://'.$request->getHttpHost().$request->getBasePath()
                    ."/".$upload_manager->getMediaRootDir()
                    ."/".$media->getName();
            }
        }
        return null;
    }

    public function getSourceType($name) {
        $em = $this->container->get('doctrine')->getManager();
        $sourceTypeRepo = $em->getRepository(SourceType::class);
        foreach($sourceTypeRepo->findAll() as $sourceType) {
            if($sourceType->getName() == $name) {
                return $sourceType;
            }
        }
        return null;
    }

    public function getAuthorRole($name) {
        $em = $this->container->get('doctrine')->getManager();
        $authorRoleRepo = $em->getRepository(AuthorRole::class);
        foreach($authorRoleRepo->findAll() as $authorRole) {
            if($authorRole->getName() == $name) {
                return $authorRole;
            }
        }
        return null;
    }

    public function loadReference(Source $source) {
        if(!($xml_file = $source->getReferenceUrl())) {
            throw new \Exception("source has no reference url");
        }
        $source_cache = $this->container->get('sourceml.source_cache');
        $xml_file = $source_cache->getXmlFile($source);
        $source_xml_parser = $this->container->get('sourceml.source_xml_parser');
        $reference = $source_xml_parser->readSourceFromXML($xml_file);
        $reference->setId($source->getId());
        $reference->setXmlUrl($source->getReferenceUrl());
        $reference->setSourceType($source->getSourceType());
        $source->setReference($reference);
        $source->setTitle($reference->getTitle());
    }

    public function getDerivationSource(DerivationSource $derivation) {
        if($xml_file = $derivation->getUrl()) {
            $source_cache = $this->container->get('sourceml.source_cache');
            try {
                $xml_file = $source_cache->getDerivationXmlFile($derivation);
            }
            catch(\Exception $e) {
                return null;
            }
            $source_xml_parser = $this->container->get('sourceml.source_xml_parser');
            $derivationSource = $source_xml_parser->readSourceFromXML($xml_file);
            $derivationSource->setXmlUrl($derivation->getUrl());
            return $derivationSource;
        }
        return null;
    }

    public function getPreviousAndNext($source) {
        $sources = array(
            "previous" => null,
            "next" => null,
        );
        if(($composition = $source->getComposition()) === null) {
            return $sources;
        }
        $queryParams = array(
            "sourceType" => $source->getSourceType()->getName(),
            "composition" => $composition->getId(),
        );
        $found = false;
        $previous = null;
        foreach($this->getSourceQuery($queryParams)->getResult() as $_source) {
            if(!$found) {
                if($_source->getId() == $source->getId()) {
                    $found = true;
                    if(isset($previous)) {
                        $sources['previous'] = $previous;
                    }
                }
            }
            else {
                $sources['next'] = $_source;
                break;
            }
            $previous = $_source;
        }
        return $sources;
    }

    public function getSourceSource(SourceComposition $source) {
        $source_cache = $this->container->get('sourceml.source_cache');
        try {
            $xml_file = $source_cache->getXmlFile($source->getSource());
        }
        catch(\Exception $e) {
            return null;
        }
        $source_xml_parser = $this->container->get('sourceml.source_xml_parser');
        $sourceSource = $source_xml_parser->readSourceFromXML($xml_file);
        $sourceSource->setXmlUrl($this->getXmlUrl($source->getSource()));
        return $sourceSource;
    }

    public function getXmlFromSource(Source $source) {
        $source_cache = $this->container->get('sourceml.source_cache');
        try {
            return $source_cache->getXmlContent($source);
        }
        catch(\Exception $e) {
        }
        return null;
    }

    public function getXmlUrl(Source $source) {
        $request_stack = $this->container->get('request_stack');
        $base_url = "";
        if($request = $request_stack->getCurrentRequest()) {
           $base_url = $request->getScheme()."://".$request->getHttpHost();
        }
        return $base_url.$this->container->get('router')->generate(
            'source_xml',
            array(
                "source" => $source->getId()
            )
        );
    }

    public function getSourceUrl(Source $source) {
        if($url = $source->getLink()) {
            return $url;
        }
        $request_stack = $this->container->get('request_stack');
        $base_url = "";
        if($request = $request_stack->getCurrentRequest()) {
           $base_url = $request->getScheme()."://".$request->getHttpHost();
        }
        return $base_url.$this->container->get('router')->generate(
            'source_view',
            array(
                "source" => $source->getId(),
            )
        );
    }

    public function getAutoplay() {
        return $this->container->get('session')->get('auto_play_next_track', false);
    }

    public function setComposition(Source $source, Source $composition = null) {
        $em = $this->container->get('doctrine')->getManager();
        $found = false;
        foreach($source->getCompositions() as $oldComposition) {
            if(
                    isset($composition)
                &&  $oldComposition->getSource()->getId() == $source->getId()
                &&  $oldComposition->getComposition()->getId() == $composition->getId()
            ) {
                $found = true;
                continue;
            }
            $source->removeComposition($oldComposition);
            $em->remove($oldComposition);
        }
        if(isset($composition) && !$found) {
            $sourceComposition = new SourceComposition();
            $sourceComposition->setSource($source);
            $sourceComposition->setComposition($composition);
            $sourceComposition->setPosition(0);
            $em->persist($sourceComposition);
            $source->addComposition($sourceComposition);
        }
    }

    public function authorHasSources(Author $author) {
        return
                $this->getSourceQuery(
                    array(
                        "author" => $author->getId(),
                        "isValid" => true,
                    )
                )->getResult()
            ||  $this->getSourceQuery(
                    array(
                        "author" => $author->getId(),
                        "isValid" => false,
                    )
                )->getResult();
    }

    public function licenceHasSources(Licence $licence) {
        return count(
            $this->getSourceQuery(
                array(
                    "licence" => $licence->getId(),
                )
            )->getResult()
        );
    }

    public function loadSource(Source $source) {
        $source->setXmlUrl($this->getXmlUrl($source));
        $source->setLink($this->getSourceUrl($source));
        if($source->isReference()) {
            try {
                $this->loadReference($source);
            }
            catch(\Exception $e) {
                $source->setError($e->getMessage());
            }
        }
        if(!$source->getError() && ($derivations = $source->getDerivations())) {
            foreach($derivations as $derivation) {
                if($derivationSource = $this->getDerivationSource($derivation)) {
                    $source->addDerivationSource($derivationSource);
                }
            }
        }
        if(!$source->getError() && ($sources = $source->getSources())) {
            foreach($sources as $_source) {
                if($sourceSource = $this->getSourceSource($_source)) {
                    $source->addSourceSource($sourceSource);
                }
            }
        }
    }

    public function dump(Source $source) {
        return print_r($source->__toArray(), true);
    }

    public function getPlayerAudioFiles(Source $source) {
        $playerAudioFiles = array();
        foreach($source->getDocuments() as $document) {
            $url = $document->getUrl();
            if($media = $document->getMedia()) {
                $url = $media->getUrl();
            }
            if($url) {
                $infos = pathinfo($url);
                $audioMimeType = "";
                if(strtolower($infos['extension']) == "ogg") {
                    $audioMimeType = "audio/ogg";
                }
                elseif(strtolower($infos['extension']) == "mp3") {
                    $audioMimeType = "audio/mp3";
                }
                if($audioMimeType) {
                    $playerAudioFiles[] = array(
                        "url" => $url,
                        "mimeType" => $audioMimeType,
                    );
                }
            }
        }
        return $playerAudioFiles;
    }

    public function getDerivations(Source $source) {
        $derivations = array();
        if($source->getId()) {
            $em = $this->container->get('doctrine')->getManager();
            $derivationRepo = $em->getRepository(DerivationSource::class);
            $derivationSources = $derivationRepo->findByUrl($source->getXmlUrl());
            foreach($derivationSources as $derivationSource) {
                $derivations[] = $derivationSource->getSource();
            }
        }
        return $derivations;
    }

    public function remove(Source $source) {
        $em = $this->container->get('doctrine')->getManager();
        if($media = $source->getImage()) {
            $upload_manager = $this->container->get('jq_file_upload.upload_manager');
            $upload_manager->init("sourceml_source_image", $source->getId());
            if(!$upload_manager->delete_file(basename($media->getName()))) {
                throw new \Exception("unable to delete source image file");
            }
            if($thumbnail = $media->getThumbnail()) {
                $media->setThumbnail(null);
                $em->remove($thumbnail);
            }
            $source->setImage(null);
            $em->remove($media);
        }
        foreach($source->getDocuments() as $sourceDocument) {
            if($media = $sourceDocument->getMedia()) {
                $upload_manager = $this->container->get('jq_file_upload.upload_manager');
                $upload_manager->init("sourceml_source_document", $sourceDocument->getId());
                if(!$upload_manager->delete_file(basename($media->getName()))) {
                    throw new \Exception("unable to delete document file");
                }
                if($thumbnail = $media->getThumbnail()) {
                    $media->setThumbnail(null);
                    $em->remove($thumbnail);
                }
                $em->remove($media);
            }
            $source->removeDocument($sourceDocument);
            $em->remove($sourceDocument);
        }
        foreach($source->getDerivations() as $derivationSource) {
            $source->removeDerivation($derivationSource);
            $em->remove($derivationSource);
        }
        foreach($source->getAuthors() as $sourceAuthor) {
            $source->removeAuthor($sourceAuthor);
            $em->remove($sourceAuthor);
        }
        foreach($source->getCompositions() as $sourceComposition) {
            $source->removeComposition($sourceComposition);
            $em->remove($sourceComposition);
        }
        foreach($source->getSources() as $sourceComposition) {
            $source->removeSource($sourceComposition);
            $em->remove($sourceComposition);
        }
        foreach($source->getInfos() as $sourceInfo) {
            $source->removeInfo($sourceInfo);
            $em->remove($sourceInfo);
        }
        $em->remove($source);
        $em->flush();
    }

}
