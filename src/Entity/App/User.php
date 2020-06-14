<?php

namespace Sourceml\Entity\App;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * User
 *
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="Sourceml\Repository\App\UserRepository")
 * @UniqueEntity(
 *     fields={"username"},
 *     message="A user with this login already exists",
 *     groups={"Default","edit"}
 * )
 * @UniqueEntity(
 *     fields={"email"},
 *     message="A user with this email already exists",
 *     groups={"Default","edit"}
 * )
 */
class User implements UserInterface, \Serializable
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
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=255, unique=true)
     *
     * @Assert\NotBlank(groups = {"Default", "edit"}, message = "Please provide a login")
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="salt", type="string", length=255)
     */
    private $salt;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=255)
     * @Assert\NotBlank(message = "Please provide a password")
     */
    private $password;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, unique=true)
     *
     * @Assert\NotBlank(groups = {"Default", "edit"}, message="Please provide an email address")
     * @Assert\Email(groups = {"Default", "edit"}, message = "This doesn't seem to be a valid email")
     */
    private $email;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean")
     */
    private $isActive;

    /**
     * @ORM\ManyToMany(targetEntity="Role", inversedBy="users")
     *
     */
    private $roles;

    public function __construct() {
        $this->roles = new ArrayCollection();
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
     * Set username
     *
     * @param string $username
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string 
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set salt
     *
     * @param string $salt
     * @return User
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * Get salt
     *
     * @return string 
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string 
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return User
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
     * Set isActive
     *
     * @param boolean $isActive
     * @return User
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean 
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    public function addRole($role)
    {
        $this->roles->add($role);
        return $this;
    }

    public function removeRole($role)
    {
        $this->roles->remove($role);
        return $this;
    }

    public function setRoles($roles)
    {
        $this->roles = $roles;
        return $this;
    }

    public function getRoles()
    {
        $roles = array();
        foreach($this->roles as $role) {
            $roles[] = $role->getRole();
        }
        return $roles;
    }

    public function getRolesEntities() {
        return $this->roles;
    }

    public function hasRole($role)
    {
        foreach($this->roles as $_role) {
            if($_role->getRole() == $role)
            return true;
        }
        return false;
    }

    public function clearRoles() {
        $this->roles = new ArrayCollection();
    }

    public function eraseCredentials()
    {
    }

    public function isAccountNonExpired()
    {
        return true;
    }

    public function isAccountNonLocked()
    {
        return true;
    }

    public function isCredentialsNonExpired()
    {
        return true;
    }

    public function isEnabled()
    {
        return $this->isActive;
    }

    /** @see \Serializable::serialize() */
    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->username,
            $this->salt,
            $this->password,
        ));
    }

    /** @see \Serializable::unserialize() */
    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->username,
            $this->salt,
            $this->password,
        ) = unserialize($serialized);
    }

}
