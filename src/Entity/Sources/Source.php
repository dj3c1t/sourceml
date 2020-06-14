<?php

namespace Sourceml\Entity\Sources;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

use Sourceml\Entity\JQFileUpload\Media;

/**
 * Source
 *
 * @ORM\Table(name="source")
 * @ORM\Entity(repositoryClass="Sourceml\Repository\Sources\SourceRepository")
 */
class Source
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="SourceType")
     * @ORM\JoinColumn(name="source_type_id", referencedColumnName="id")
     */
    protected $sourceType;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @ORM\ManyToOne(targetEntity="\Sourceml\Entity\JQFileUpload\Media")
     * @ORM\JoinColumn(name="image", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     */
    protected $image;

    /**
     * @ORM\OneToOne(targetEntity="\Sourceml\Entity\JQFileUpload\Media", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="waveform", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     */
    protected $waveform;

    /**
     * @var string
     *
     * @ORM\Column(name="reference", type="string", length=255, nullable=true)
     */
    private $referenceUrl;

    /**
     * @var Source
     */
    private $reference;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creation_date", type="date", nullable=true)
     */
    private $creationDate;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="publication_date", type="datetime")
     */
    private $publicationDate;

    /**
     * @ORM\OneToMany(targetEntity="SourceAuthor", mappedBy="source", cascade={"persist", "remove"})
     */
    protected $authors;

    /**
     * @ORM\ManyToOne(targetEntity="Licence")
     * @ORM\JoinColumn(name="licence_id", referencedColumnName="id")
     */
    protected $licence;

    /**
     * @ORM\OneToMany(targetEntity="SourceInfo", mappedBy="source", cascade={"persist", "remove"})
     */
    protected $infos;

    /**
     * @ORM\OneToMany(targetEntity="SourceDocument", mappedBy="source", cascade={"persist", "remove"})
     */
    protected $documents;

    /**
     * @ORM\OneToMany(targetEntity="SourceComposition", mappedBy="source", cascade={"persist", "remove"})
     */
    protected $compositions;

    /**
     * @ORM\OneToMany(targetEntity="SourceComposition", mappedBy="composition", cascade={"persist", "remove"})
     */
    protected $sources;

    protected $sourceSources;

    /**
     * @ORM\OneToMany(targetEntity="DerivationSource", mappedBy="source", cascade={"persist", "remove"})
     */
    protected $derivations;

    protected $derivationSources;

    /**
     * @var string
     */
    protected $link;

    /**
     * @var string
     */
    protected $xmlUrl;

    /**
     * @var string
     *
     */
    private $error;

    /**
     * Constructor
     */
    public function __construct() {
        $this->authors = new ArrayCollection();
        $this->infos = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->compositions = new ArrayCollection();
        $this->sources = new ArrayCollection();
        $this->sourceSources = new ArrayCollection();
        $this->derivations = new ArrayCollection();
        $this->derivationSources = new ArrayCollection();
    }

    /**
     * Set id
     *
     * @param int $id
     * @return Source
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Source
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Set referenceUrl
     *
     * @param string $referenceUrl
     * @return Source
     */
    public function setReferenceUrl($referenceUrl)
    {
        $this->referenceUrl = $referenceUrl;

        return $this;
    }

    /**
     * Get referenceUrl
     *
     * @return string 
     */
    public function getReferenceUrl()
    {
        return $this->referenceUrl;
    }

    public function isReference() {
        return isset($this->referenceUrl);
    }

    /**
     * Set reference
     *
     * @param Source $reference
     * @return Source
     */
    public function setReference($reference)
    {
        $this->reference = $reference;

        return $this;
    }

    /**
     * Get reference
     *
     * @return Source 
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * Set creationDate
     *
     * @param \DateTime $creationDate
     * @return Source
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate
     *
     * @return \DateTime 
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * Set publicationDate
     *
     * @param \DateTime $publicationDate
     * @return Source
     */
    public function setPublicationDate($publicationDate)
    {
        $this->publicationDate = $publicationDate;

        return $this;
    }

    /**
     * Get publicationDate
     *
     * @return \DateTime 
     */
    public function getPublicationDate()
    {
        return $this->publicationDate;
    }

    /**
     * Add authors
     *
     * @param SourceAuthor $authors
     * @return Source
     */
    public function addAuthor(SourceAuthor $authors)
    {
        $this->authors[] = $authors;

        return $this;
    }

    /**
     * Remove author
     *
     * @param SourceAuthor $author
     */
    public function removeAuthor(SourceAuthor $author)
    {
        $this->authors->removeElement($author);
    }

    /**
     * Get authors
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getAuthors()
    {
        return $this->authors;
    }

    /**
     * Get importedBy
     *
     * @return Author
     */
    public function getImportedBy() {
        $importedBy = null;
        foreach($this->getAuthors() as $sourceAuthor) {
            if($sourceAuthor->getAuthorRole()->getName() == 'admin') {
                $importedBy = $sourceAuthor->getAuthor();
                break;
            }
        }
        return $importedBy;
    }

    /**
     * Set sourceType
     *
     * @param SourceType $sourceType
     * @return Source
     */
    public function setSourceType(SourceType $sourceType = null)
    {
        $this->sourceType = $sourceType;

        return $this;
    }

    /**
     * Get sourceType
     *
     * @return SourceType 
     */
    public function getSourceType()
    {
        return $this->sourceType;
    }

    /**
     * Set licence
     *
     * @param Licence $licence
     * @return Source
     */
    public function setLicence(Licence $licence = null)
    {
        $this->licence = $licence;

        return $this;
    }

    /**
     * Get licence
     *
     * @return Licence 
     */
    public function getLicence()
    {
        return $this->licence;
    }

    /**
     * Add infos
     *
     * @param SourceInfo $infos
     * @return Source
     */
    public function addInfo(SourceInfo $infos)
    {
        $this->infos[] = $infos;

        return $this;
    }

    /**
     * Remove infos
     *
     * @param SourceInfo $infos
     */
    public function removeInfo(SourceInfo $infos)
    {
        $this->infos->removeElement($infos);
    }

    /**
     * Get infos
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getInfos()
    {
        return $this->infos;
    }

    public function setInfo($infoKey, $infoValue) {
        $infoKeyFound = false;
        foreach($this->infos as $sourceInfo) {
            if($sourceInfo->getInfoKey() == $infoKey) {
                $sourceInfo->setInfoValue($infoValue);
                return $this;
            }
        }
        $sourceInfo = new SourceInfo();
        $sourceInfo->setInfoKey($infoKey);
        $sourceInfo->setInfoValue($infoValue);
        $sourceInfo->setSource($this);
        $this->addInfo($sourceInfo);
        return $this;
    }

    public function getInfo($infoKey) {
        foreach($this->infos as $sourceInfo) {
            if($sourceInfo->getInfoKey() == $infoKey) {
                return $sourceInfo->getInfoValue();
            }
        }
        return null;
    }

    public function deleteInfo($infoKey) {
        $infoKeyFound = false;
        foreach($this->infos as $sourceInfo) {
            if($sourceInfo->getInfoKey() == $infoKey) {
                $this->removeInfo($sourceInfo);
            }
        }
        return $this;
    }

    /**
     * Add documents
     *
     * @param SourceDocument $documents
     * @return Source
     */
    public function addDocument(SourceDocument $documents)
    {
        $this->documents[] = $documents;

        return $this;
    }

    /**
     * Remove documents
     *
     * @param SourceDocument $documents
     */
    public function removeDocument(SourceDocument $documents)
    {
        $this->documents->removeElement($documents);
    }

    /**
     * Get documents
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getDocuments()
    {
        return $this->documents;
    }

    /**
     * Add compositions
     *
     * @param SourceComposition $compositions
     * @return Source
     */
    public function addComposition(SourceComposition $compositions)
    {
        $this->compositions[] = $compositions;

        return $this;
    }

    /**
     * Remove compositions
     *
     * @param SourceComposition $compositions
     */
    public function removeComposition(SourceComposition $compositions)
    {
        $this->compositions->removeElement($compositions);
    }

    /**
     * Get compositions
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getCompositions()
    {
        return $this->compositions;
    }

    /**
     * Get composition
     *
     * @return Source
     */
    public function getComposition() {
        if(isset($this->compositions)) {
            foreach($this->compositions as $sourceComposition) {
                return $sourceComposition->getComposition();
            }
        }
        return null;
    }

    /**
     * Add sources
     *
     * @param SourceComposition $sources
     * @return Source
     */
    public function addSource(SourceComposition $sources)
    {
        $this->sources[] = $sources;

        return $this;
    }

    /**
     * Remove sources
     *
     * @param SourceComposition $sources
     */
    public function removeSource(SourceComposition $sources)
    {
        $this->sources->removeElement($sources);
    }

    /**
     * Get sources
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getSources()
    {
        return $this->sources;
    }

    /**
     * Add derivation
     *
     * @param DerivationSource $derivation
     * @return Source
     */
    public function addDerivation(DerivationSource $derivation)
    {
        $this->derivations[] = $derivation;

        return $this;
    }

    /**
     * Remove derivation
     *
     * @param DerivationSource $derivation
     */
    public function removeDerivation(DerivationSource $derivation)
    {
        $this->derivations->removeElement($derivation);
    }

    /**
     * Get derivations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDerivations()
    {
        return $this->derivations;
    }

    /**
     * Add derivationSource
     *
     * @param Source $derivationSource
     * @return Source
     */
    public function addDerivationSource(Source $derivationSource)
    {
        $this->derivationSources[] = $derivationSource;

        return $this;
    }

    /**
     * Remove derivationSource
     *
     * @param Source $derivationSource
     */
    public function removeDerivationSource(Source $derivationSource)
    {
        $this->derivationSources->removeElement($derivationSource);
    }

    /**
     * Get derivationSources
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getDerivationSources()
    {
        return $this->derivationSources;
    }

    /**
     * Add sourceSource
     *
     * @param Source $sourceSource
     * @return Source
     */
    public function addSourceSource(Source $sourceSource)
    {
        $this->sourceSources[] = $sourceSource;

        return $this;
    }

    /**
     * Remove sourceSource
     *
     * @param Source $sourceSource
     */
    public function removeSourceSource(Source $sourceSource)
    {
        $this->sourceSources->removeElement($sourceSource);
    }

    /**
     * Get sourceSources
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSourceSources()
    {
        return $this->sourceSources;
    }

    /**
     * Set link
     *
     * @param string $link
     * @return Source
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Get link
     *
     * @return string 
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Set xmlUrl
     *
     * @param string $xmlUrl
     * @return Source
     */
    public function setXmlUrl($xmlUrl)
    {
        $this->xmlUrl = $xmlUrl;

        return $this;
    }

    /**
     * Get xmlUrl
     *
     * @return string
     */
    public function getXmlUrl()
    {
        return $this->xmlUrl;
    }

    /**
     * Set image
     *
     * @param Media $image
     * @return Source
     */
    public function setImage(Media $image = null)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image
     *
     * @return Media 
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set waveform
     *
     * @param Media $waveform
     * @return Source
     */
    public function setWaveform(Media $waveform = null)
    {
        $this->waveform = $waveform;

        return $this;
    }

    /**
     * Get waveform
     *
     * @return Media
     */
    public function getWaveform()
    {
        return $this->waveform;
    }

    /**
     * @Assert\IsTrue(message = "a source can't be composed in a reference")
     */
    public function hasValidComposition() {
        if($compositions = $this->getCompositions()) {
            $composition = null;
            foreach($compositions as $sourceComposition) {
                $composition = $sourceComposition->getComposition();
                break;
            }
            if(isset($composition) && $composition->isReference()) {
                return false;
            }
        }
        return true;
    }

    /**
     * @Assert\IsTrue(message = "a source must have at least one admin author")
     */
    public function hasValidAuthor() {
        if(!($authors = $this->getAuthors())) {
            return false;
        }
        $hasAdminAuthor = false;
        foreach($authors as $sourceAuthor) {
            if(
                    $sourceAuthor->getAuthorRole()->getName() == "admin"
                &&  $sourceAuthor->getIsValid()
            ) {
                $hasAdminAuthor = true;
                break;
            }
        }
        return $hasAdminAuthor;
    }

    public function __toString() {
        return isset($this->title) ? $this->title : "";
    }

    public function __toArray() {
        $source = array();
        if(isset($this->id)) {
            $source["id"] = $this->id;
        }
        if(isset($this->referenceUrl)) {
            $source["referenceUrl"] = $this->referenceUrl;
        }
        if(isset($this->sourceType)) {
            $source["sourceType"] = $this->sourceType->getName();
        }
        if(isset($this->title)) {
            $source["title"] = $this->title;
        }
        if(isset($this->image)) {
            $source["image"] = $this->image->getUrl();
        }
        if(isset($this->creationDate)) {
            $source["creationDate"] = $this->creationDate->format("Y-m-d");
        }
        if(isset($this->publicationDate)) {
            $source["publicationDate"] = $this->publicationDate->format("Y-m-d H:i:s");
        }
        $authors = array();
        foreach($this->authors as $sourceAuthor) {
            $authorRole = $sourceAuthor->getAuthorRole();
            $author = array(
                "name" => $sourceAuthor->getAuthor()->getName(),
            );
            if($author_url = $sourceAuthor->getAuthor()->getUrl()) {
                $author["url"] = $author_url;
            }
            if(isset($authorRole)) {
                $author["role"] = $authorRole->getName();
            }
            $authors[] = $author;
        }
        if($authors) {
            $source["authors"] = $authors;
        }
        if(isset($this->licence)) {
            $source["licence"] = array(
                "name" => $this->licence->getName(),
                "url" => $this->licence->getUrl(),
            );
        }
        $infos = array();
        foreach($this->infos as $sourceInfo) {
            $infos[] = array(
                "key" => $sourceInfo->getKey(),
                "value" => $sourceInfo->getValue(),
            );
        }
        if($infos) {
            $source["infos"] = $infos;
        }
        $documents = array();
        foreach($this->documents as $sourceDocument) {
            $document = array(
                "name" => $sourceDocument->getName(),
            );
            $url = $sourceDocument->getUrl();
            if(isset($url)) {
                $document["url"] = $url;
            }
            $media = $sourceDocument->getMedia();
            if(isset($media)) {
                $document["media"] = $media->getName();
            }
            $documents[] = $document;
        }
        if($documents) {
            $source["documents"] = $documents;
        }
        $sources = array();
        foreach($this->sources as $sourceComposition) {
            $sources[] = $sourceComposition->getSource()->getXmlUrl();
        }
        if($sources) {
            $source["sources"] = $sources;
        }
        $derivationSources = array();
        foreach($this->derivations as $derivationSource) {
            $derivationSources[] = $derivationSource->getUrl();
        }
        if($derivationSources) {
            $source["derivationSources"] = $derivationSources;
        }
        if(isset($this->link)) {
            $source["link"] = $this->link;
        }
        return $source;
    }

    /**
     * Set error
     *
     * @param string $error
     * @return Source
     */
    public function setError($error)
    {
        $this->error = $error;

        return $this;
    }

    /**
     * Get error
     *
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

}
