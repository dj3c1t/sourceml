<?php

namespace Sourceml\Service\Sources;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

use Sourceml\Entity\JQFileUpload\Media;
use Sourceml\Entity\Sources\Source;
use Sourceml\Entity\Sources\SourceType;
use Sourceml\Entity\Sources\Author;
use Sourceml\Entity\Sources\SourceAuthor;
use Sourceml\Entity\Sources\Licence;
use Sourceml\Entity\Sources\SourceDocument;
use Sourceml\Entity\Sources\SourceComposition;
use Sourceml\Entity\Sources\DerivationSource;

class SourceXMLParser {

    private $container;
    private $sm;

    public function __construct(Container $container) {
        $this->container = $container;
        $this->sm = $this->container->get('sourceml.source_manager');
    }

    public function readSourceFromXML($xml_file, Source $source = null) {
        if(($xml = @simplexml_load_file($xml_file)) === false) {
            throw new \Exception("couldn't read xml file");
        }
        if(!isset($source)) {
            $source = new Source();
        }
        if($type = (string) $xml['type']) {
            $source->setSourceType($this->sm->getSourceType($type));
        }
        $source->setTitle(trim((string) $xml->title));
        if($link = (string) $xml->link['href']) {
            $source->setLink($link);
        }
        if($image = (string) $xml->image['src']) {
            $media = new Media();
            $media->setUrl($image);
            if($image = (string) $xml->image['thumbnail']) {
                $thumbnail = new Media();
                $thumbnail->setUrl($image);
                $media->setThumbnail($thumbnail);
            }
            $source->setImage($media);
        }
        if($waveform = (string) $xml->waveform['src']) {
            $media = new Media();
            $media->setUrl($waveform);
            $source->setWaveform($media);
        }
        if($date = trim((string) $xml->date)) {
            try {
                $creationDate = new \Datetime($date);
                if($creationDate) {
                    $source->setCreationDate($creationDate);
                }
            }
            catch(\Exception $e) {
            }
        }
        foreach($xml->author as $authorElt) {
            $author = new Author();
            $author->setName(trim((string) $authorElt));
            $author->setUrl((string) $authorElt['href']);
            $sourceAuthor = new SourceAuthor();
            $sourceAuthor->setSource($source);
            $sourceAuthor->setAuthor($author);
            if($role = (string) $authorElt['role']) {
                $sourceAuthor->setAuthorRole($this->sm->getAuthorRole($role));
            }
            if($imageUrl = (string) $authorElt['image']) {
                $image = new Media();
                $image->setUrl($imageUrl);
                $thumbnail = new Media();
                $thumbnail->setUrl($imageUrl);
                $image->setThumbnail($thumbnail);
                $author->setImage($image);
            }
            $source->addAuthor($sourceAuthor);
        }
        foreach($xml->document as $documentElt) {
            $sourceDocument = new SourceDocument();
            $sourceDocument->setName(trim((string) $documentElt));
            $sourceDocument->setUrl((string) $documentElt['src']);
            $source->addDocument($sourceDocument);
        }
        if($licenceElt = $xml->licence) {
            $licence = new Licence();
            $licence->setName(trim((string) $licenceElt));
            if($licenceUrl = (string) $licenceElt['href']) {
                $licence->setUrl($licenceUrl);
            }
            $source->setLicence($licence);
        }
        foreach($xml->source as $sourceElt) {
            if($rel = (string) $sourceElt['rel']) {
                switch($rel) {
                    case "composedOf":
                        $this->addComposedOf((string) $sourceElt['src'], $source);
                        break;
                    case "derivedFrom":
                        $this->addDerivedFrom((string) $sourceElt['src'], $source);
                        break;
                }
            }
            else {
                $this->addComposedOf((string) $sourceElt['src'], $source);
            }
        }
        foreach($xml->derivated_from as $sourceElt) {
            $this->addDerivedFrom((string) $sourceElt['src'], $source);
        }
        return $source;
    }

    protected function addComposedOf($xmlUrl, Source $source) {
        $sourceSource = new Source();
        $sourceSource->setXmlUrl($xmlUrl);
        $sourceSource->setReferenceUrl($xmlUrl);
        $sourceComposition = new SourceComposition();
        $sourceComposition->setComposition($source);
        $sourceComposition->setSource($sourceSource);
        $source->addSource($sourceComposition);
    }

    protected function addDerivedFrom($xmlUrl, Source $source) {
        $derivation = new DerivationSource();
        $derivation->setSource($source);
        $derivation->setUrl($xmlUrl);
        $source->addDerivation($derivation);
    }

    public function getXmlFromSource(Source $source) {
        return $this->container->get('twig')->render(
            'Sources/source.xml.twig',
            array(
                'source' => $source->getReference() ? $source->getReference() : $source
            )
        );
    }

}
