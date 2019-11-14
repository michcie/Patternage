<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User implements AdvancedUserInterface, \Serializable, EquatableInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;


    /**
     * @ORM\Column(length=255, unique=true)
     * @Assert\NotBlank()
     * @Assert\Length(max=254)
     * @Assert\Regex(pattern="/^[a-zA-Z0-9_]+$/")
     */
    private $username;

    /**
     * @ORM\Column(unique=true, nullable=true)
     * @Assert\NotBlank()
     * @Assert\Email(checkHost=false, checkMX=false)
     */
    private $email;

    /**
     * @ORM\Column(unique=true, nullable=true)
     */
    private $facebookId;

    /**
     * @ORM\Column(nullable=true)
     */
    private $facebookName;

    /**
     * @ORM\Column(nullable=true)
     */
    private $facebookAvatar;

    /**
     * @ORM\Column(unique=true, nullable=true)
     */
    private $googleId;

    /**
     * @ORM\Column(nullable=true)
     */
    private $googleName;

    /**
     * @ORM\Column(nullable=true)
     */
    private $googleAvatar;


    /**
     * @ORM\Column(length=64, nullable=true)
     */
    private $password;

    /**
     * @Assert\NotBlank(groups={"register"})
     * @Assert\Length(max=4096)
     */
    private $plainPassword;

    /**
     * @ORM\Column()
     */
    private $salt;
    /**
     * @ORM\Column(type="boolean")
     * @var boolean
     */
    private $superAdmin;//if true user have full access to everythink!!!!

    /**
     * @ORM\Column(unique=true, length=128, nullable=true)
     */
    private $emailConfirmationToken;

    /**
     * @ORM\Column(type="boolean")
     * @var boolean
     */
    private $emailConfirmed;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function __construct()
    {
        $this->superAdmin = false;
        $this->salt = md5(uniqid('', true));
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email): void
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getFacebookId()
    {
        return $this->facebookId;
    }

    /**
     * @param mixed $facebookId
     */
    public function setFacebookId($facebookId): void
    {
        $this->facebookId = $facebookId;
    }

    /**
     * @return mixed
     */
    public function getFacebookName()
    {
        return $this->facebookName;
    }

    /**
     * @param mixed $facebookName
     */
    public function setFacebookName($facebookName): void
    {
        $this->facebookName = $facebookName;
    }

    /**
     * @return mixed
     */
    public function getFacebookAvatar()
    {
        return $this->facebookAvatar;
    }

    /**
     * @param mixed $facebookAvatar
     */
    public function setFacebookAvatar($facebookAvatar): void
    {
        $this->facebookAvatar = $facebookAvatar;
    }

    /**
     * @return mixed
     */
    public function getGoogleId()
    {
        return $this->googleId;
    }

    /**
     * @param mixed $googleId
     */
    public function setGoogleId($googleId): void
    {
        $this->googleId = $googleId;
    }

    /**
     * @return mixed
     */
    public function getGoogleName()
    {
        return $this->googleName;
    }

    /**
     * @param mixed $googleName
     */
    public function setGoogleName($googleName): void
    {
        $this->googleName = $googleName;
    }

    /**
     * @return mixed
     */
    public function getGoogleAvatar()
    {
        return $this->googleAvatar;
    }

    /**
     * @param mixed $googleAvatar
     */
    public function setGoogleAvatar($googleAvatar): void
    {
        $this->googleAvatar = $googleAvatar;
    }

    /**
     * @return mixed
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * @param mixed $plainPassword
     */
    public function setPlainPassword($plainPassword): void
    {
        $this->plainPassword = $plainPassword;
    }

    /**
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return $this->superAdmin;
    }

    /**
     * @param bool $superAdmin
     */
    public function setSuperAdmin(bool $superAdmin): void
    {
        $this->superAdmin = $superAdmin;
    }


    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @param mixed $username
     */
    public function setUsername($username): void
    {
        $this->username = $username;
    }

    /**
     * @param mixed $password
     */
    public function setPassword($password): void
    {
        $this->password = $password;
    }

    /**
     * @param mixed $salt
     */
    public function setSalt($salt): void
    {
        $this->salt = $salt;
    }


    /**
     * @return bool
     */
    public function isEmailConfirmed(): bool
    {
        return $this->emailConfirmed;
    }

    /**
     * @param bool $emailConfirmed
     */
    public function setEmailConfirmed(bool $emailConfirmed): void
    {
        $this->emailConfirmed = $emailConfirmed;
    }

    /**
     * @return mixed
     */
    public function getEmailConfirmationToken()
    {
        return $this->emailConfirmationToken;
    }

    /**
     * @param mixed $emailConfirmationToken
     */
    public function setEmailConfirmationToken($emailConfirmationToken): void
    {
        $this->emailConfirmationToken = $emailConfirmationToken;
    }

    /**
     * @return mixed
     */
    public function getCart()
    {
        return $this->cart;
    }

    /**
     * @param mixed $cart
     */
    public function setCart($cart): void
    {
        $this->cart = $cart;
    }


    /**
     * Checks whether the user's account has expired.
     *
     * Internally, if this method returns false, the authentication system
     * will throw an AccountExpiredException and prevent login.
     *
     * @return bool true if the user's account is non expired, false otherwise
     *
     * @see AccountExpiredException
     */
    public function isAccountNonExpired()
    {
        return true;//account never expire
    }

    /**
     * Checks whether the user is locked.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a LockedException and prevent login.
     *
     * @return bool true if the user is not locked, false otherwise
     *
     * @see LockedException
     */
    public function isAccountNonLocked()
    {
        return true;//account never locked
    }

    /**
     * Checks whether the user's credentials (password) has expired.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a CredentialsExpiredException and prevent login.
     *
     * @return bool true if the user's credentials are non expired, false otherwise
     *
     * @see CredentialsExpiredException
     */
    public function isCredentialsNonExpired()
    {
        return true; //credentials nver expired
    }

    /**
     * Checks whether the user is enabled.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a DisabledException and prevent login.
     *
     * @return bool true if the user is enabled, false otherwise
     *
     * @see DisabledException
     */
    public function isEnabled()
    {
        return true;//user is always enabled...
    }

    /**
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize()
    {
        return serialize(array(//serialize basic information
            $this->id,
            $this->username,
            $this->email,
            $this->password,
            $this->salt,
            $this->superAdmin
        ));
    }

    /**
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     * @since 5.1.0
     */
    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->username,
            $this->email,
            $this->password,
            $this->salt,
            $this->superAdmin
            ) = unserialize($serialized);
    }

    /**
     * The equality comparison should neither be done by referential equality
     * nor by comparing identities (i.e. getId() === getId()).
     *
     * However, you do not need to compare every attribute, but only those that
     * are relevant for assessing whether re-authentication is required.
     *
     * @return bool
     */
    public function isEqualTo(UserInterface $user)
    {
        if ($this->getId() !== $user->getId()) {
            return false;
        }

        if ($this->getPassword() !== $user->getPassword()) {
            return false;
        }

        if ($this->getSalt() !== $user->getSalt()) {
            return false;
        }

        if ($this->getEmail() !== $user->getEmail()) {
            return false;
        }

        if ($this->isSuperAdmin() !== $user->isSuperAdmin()) {
            return false;
        }

        if ($this->isAccountNonExpired() !== $user->isAccountNonExpired()) {
            return false;
        }

        if ($this->isAccountNonLocked() !== $user->isAccountNonLocked()) {
            return false;
        }

        if ($this->isCredentialsNonExpired() !== $user->isCredentialsNonExpired()) {
            return false;
        }
    }

    /**
     * Returns the roles granted to the user.
     *
     *     public function getRoles()
     *     {
     *         return array('ROLE_USER');
     *     }
     *
     * Alternatively, the roles might be stored on a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     *
     * @return (Role|string)[] The user roles
     */
    public function getRoles()
    {
        //only two roles, admin or standard user
        return [($this->isSuperAdmin() ? 'ROLE_SUPER_ADMIN' : 'ROLE_USER')];
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials()
    {
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return mixed
     */
    public function getSalt()
    {
        return $this->salt;
    }


}
