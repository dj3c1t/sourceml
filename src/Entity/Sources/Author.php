<?php

namespace Sourceml\Entity\Sources;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

use Sourceml\Entity\App\User;
use Sourceml\Entity\JQFileUpload\Media;

/**
 * Author
 *
 * @ORM\Table(name="author")
 * @ORM\Entity
 */
class Author
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
     * @ORM\ManyToOne(targetEntity="\Sourceml\Entity\App\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="\Sourceml\Entity\JQFileUpload\Media")
     * @ORM\JoinColumn(name="image", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     */
    protected $image;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\OneToMany(targetEntity="SourceAuthor", mappedBy="author")
     */
    protected $sources;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @var boolean
     *
     * @ORM\Column(name="has_contact_form", type="boolean")
     */
    private $hasContactForm;

    /**
     * @var boolean
     *
     * @ORM\Column(name="use_captcha", type="boolean")
     */
    private $useCaptcha;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="publication_date", type="datetime")
     */
    private $publicationDate;

    /**
     * @var string
     */
    protected $url;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->sources = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set name
     *
     * @param string $name
     * @return Author
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
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Author
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Author
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set hasContactForm
     *
     * @param boolean $hasContactForm
     * @return Author
     */
    public function setHasContactForm($hasContactForm)
    {
        $this->hasContactForm = $hasContactForm;

        return $this;
    }

    /**
     * Get hasContactForm
     *
     * @return boolean 
     */
    public function getHasContactForm()
    {
        return $this->hasContactForm;
    }

    /**
     * Set useCaptcha
     *
     * @param boolean $useCaptcha
     * @return Author
     */
    public function setUseCaptcha($useCaptcha)
    {
        $this->useCaptcha = $useCaptcha;

        return $this;
    }

    /**
     * Get useCaptcha
     *
     * @return boolean 
     */
    public function getUseCaptcha()
    {
        return $this->useCaptcha;
    }


    /**
     * Set user
     *
     * @param User $user
     * @return Author
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return User 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Add sources
     *
     * @param SourceAuthor $sources
     * @return Author
     */
    public function addSource(SourceAuthor $sources)
    {
        $this->sources[] = $sources;

        return $this;
    }

    /**
     * Remove sources
     *
     * @param SourceAuthor $sources
     */
    public function removeSource(SourceAuthor $sources)
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
     * Set image
     *
     * @param Media $image
     * @return Author
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

    public function __toString() {
        return $this->getName();
    }

    /**
     * Set url
     *
     * @param string $url
     * @return Media
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
     * Set publicationDate
     *
     * @param \DateTime $publicationDate
     * @return Author
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

}
