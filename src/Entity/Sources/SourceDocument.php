<?php

namespace Sourceml\Entity\Sources;

use Doctrine\ORM\Mapping as ORM;

use Sourceml\Entity\JQFileUpload\Media;

/**
 * SourceDocument
 *
 * @ORM\Table(name="source_document")
 * @ORM\Entity
 */
class SourceDocument
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
     * @ORM\ManyToOne(targetEntity="Source", inversedBy="documents")
     * @ORM\JoinColumn(name="source_id", referencedColumnName="id")
     */
    protected $source;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     */
    private $url;

    /**
     * @ORM\ManyToOne(targetEntity="\Sourceml\Entity\JQFileUpload\Media")
     * @ORM\JoinColumn(name="media", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     */
    protected $media;


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
     * Set id
     *
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return SourceDocument
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName() {
        if($this->name) {
            return $this->name;
        }
        $file = "";
        if(isset($this->media)) {
            $file = $this->media->getName();
        }
        else {
            $file = $this->url;
        }
        if($file) {
            $infos = pathinfo($file);
            if($infos['extension']) {
                return $infos['extension'];
            }
        }
        return '';
    }

    /**
     * Set url
     *
     * @param string $url
     * @return SourceDocument
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl() {
        if(isset($this->media)) {
            return $this->media->getUrl();
        }
        return $this->url;
    }

    /**
     * Set source
     *
     * @param Source $source
     * @return SourceDocument
     */
    public function setSource(Source $source = null)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return Source 
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set media
     *
     * @param Media $media
     * @return SourceDocument
     */
    public function setMedia(Media $media = null)
    {
        $this->media = $media;

        return $this;
    }

    /**
     * Get media
     *
     * @return Media 
     */
    public function getMedia()
    {
        return $this->media;
    }
}
