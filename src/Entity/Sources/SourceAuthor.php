<?php

namespace Sourceml\Entity\Sources;

use Doctrine\ORM\Mapping as ORM;

/**
 * AuthorSource
 *
 * @ORM\Table(name="source_author")
 * @ORM\Entity(repositoryClass="Sourceml\Entity\Sources\SourceAuthorRepository")
 */
class SourceAuthor
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
     * @ORM\ManyToOne(targetEntity="Source", inversedBy="authors", cascade={"persist"})
     * @ORM\JoinColumn(name="source_id", referencedColumnName="id")
     */
    protected $source;

    /**
     * @ORM\ManyToOne(targetEntity="Author", inversedBy="sources")
     * @ORM\JoinColumn(name="author_id", referencedColumnName="id")
     */
    protected $author;

    /**
     * @ORM\ManyToOne(targetEntity="AuthorRole")
     * @ORM\JoinColumn(name="author_role_id", referencedColumnName="id")
     */
    protected $authorRole;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_valid", type="boolean")
     */
    private $isValid;

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
     * @return SourceAuthor
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
     * Set author
     *
     * @param Author $author
     * @return SourceAuthor
     */
    public function setAuthor(Author $author = null)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author
     *
     * @return Author 
     */
    public function getAuthor()
    {
        return $this->author;
    }



    /**
     * Set authorRole
     *
     * @param AuthorRole $authorRole
     * @return SourceAuthor
     */
    public function setAuthorRole(AuthorRole $authorRole = null)
    {
        $this->authorRole = $authorRole;

        return $this;
    }

    /**
     * Get authorRole
     *
     * @return AuthorRole 
     */
    public function getAuthorRole()
    {
        return $this->authorRole;
    }

    /**
     * Set isValid
     *
     * @param boolean $isValid
     * @return SourceAuthor
     */
    public function setIsValid($isValid)
    {
        $this->isValid = $isValid;

        return $this;
    }

    /**
     * Get isValid
     *
     * @return boolean
     */
    public function getIsValid()
    {
        return $this->isValid;
    }
}
