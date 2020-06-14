<?php

namespace Sourceml\Entity\Sources;

use Doctrine\ORM\Mapping as ORM;

/**
 * SourceComposition
 *
 * @ORM\Table(name="source_composition")
 * @ORM\Entity
 */
class SourceComposition
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
     * @ORM\ManyToOne(targetEntity="Source", inversedBy="compositions")
     * @ORM\JoinColumn(name="source_id", referencedColumnName="id")
     */
    protected $source;

    /**
     * @ORM\ManyToOne(targetEntity="Source", inversedBy="sources")
     * @ORM\JoinColumn(name="composition_id", referencedColumnName="id")
     */
    protected $composition;

    /**
     * @var integer
     *
     * @ORM\Column(name="position", type="integer", options={"default" = 0})
     */
    private $position;

    /**
     * Constructor
     */
    public function __construct() {
        $this->position = 0;
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
     * Set source
     *
     * @param Source $source
     * @return SourceComposition
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
     * Set composition
     *
     * @param Source $composition
     * @return SourceComposition
     */
    public function setComposition(Source $composition = null)
    {
        $this->composition = $composition;

        return $this;
    }

    /**
     * Get composition
     *
     * @return Source
     */
    public function getComposition()
    {
        return $this->composition;
    }

    /**
     * Set position
     *
     * @param integer $position
     * @return SourceComposition
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return integer
     */
    public function getPosition()
    {
        return $this->position;
    }

}
