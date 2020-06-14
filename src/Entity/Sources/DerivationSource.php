<?php

namespace Sourceml\Entity\Sources;

use Doctrine\ORM\Mapping as ORM;

/**
 * SourceDerivation
 *
 * @ORM\Table(name="derivation_source")
 * @ORM\Entity
 */
class DerivationSource
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
     * @ORM\ManyToOne(targetEntity="Source", inversedBy="derivations")
     * @ORM\JoinColumn(name="source_id", referencedColumnName="id")
     */
    protected $source;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255)
     */
    private $url;


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
     * Set url
     *
     * @param string $url
     * @return DerivationSource
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
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set source
     *
     * @param Source $source
     * @return DerivationSource
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

    public function getDerivationSource() {
        if(!$this->source->getDerivationSources()) {
            return null;
        }
        foreach($this->source->getDerivationSources() as $derivationSource) {
            if(
                    $derivationSource->getXmlUrl()
                &&  $derivationSource->getXmlUrl() == $this->url
            ) {
                return $derivationSource;
            }
        }
        return null;
    }

}
