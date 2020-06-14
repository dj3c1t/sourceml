<?php

namespace Sourceml\Entity\Sources;

use Doctrine\ORM\Mapping as ORM;

/**
 * SourceInfo
 *
 * @ORM\Table(name="source_info")
 * @ORM\Entity
 */
class SourceInfo
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
     * @ORM\ManyToOne(targetEntity="Source", inversedBy="infos")
     * @ORM\JoinColumn(name="source_id", referencedColumnName="id")
     */
    protected $source;

    /**
     * @var string
     *
     * @ORM\Column(name="info_key", type="string", length=255)
     */
    private $infoKey;

    /**
     * @var string
     *
     * @ORM\Column(name="info_value", type="text", nullable=true)
     */
    private $infoValue;


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
     * Set infoKey
     *
     * @param string $infoKey
     * @return SourceInfo
     */
    public function setInfoKey($infoKey)
    {
        $this->infoKey = $infoKey;

        return $this;
    }

    /**
     * Get infoKey
     *
     * @return string 
     */
    public function getInfoKey()
    {
        return $this->infoKey;
    }

    /**
     * Set infoValue
     *
     * @param string $infoValue
     * @return SourceInfo
     */
    public function setInfoValue($infoValue)
    {
        $this->infoValue = $infoValue;

        return $this;
    }

    /**
     * Get infoValue
     *
     * @return string 
     */
    public function getInfoValue()
    {
        return $this->infoValue;
    }

    /**
     * Set source
     *
     * @param Source $source
     * @return SourceInfo
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
}
