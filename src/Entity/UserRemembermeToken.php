<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\Authentication\RememberMe\PersistentTokenInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRemembermeTokenRepository")
 */
class UserRemembermeToken implements PersistentTokenInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(length=88)
     */
    private $series;

    /**
     * @ORM\Column(length=88)
     */
    private $value;

    /**
     * @ORM\Column(type="datetime")
     */
    private $lastUsed;

    /**
     * @ORM\Column(length=255)
     */
    private $class;

    /**
     * @ORM\Column(length=255)
     */
    private $username;

    /**
     * @return mixed
     */
    public function getSeries()
    {
        return $this->series;
    }

    /**
     * @param mixed $series
     */
    public function setSeries($series): void
    {
        $this->series = $series;
    }

    /**
     * @return mixed
     */
    public function getTokenValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setTokenValue($value): void
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getLastUsed()
    {
        return $this->lastUsed;
    }

    /**
     * @param mixed $lastUsed
     */
    public function setLastUsed($lastUsed): void
    {
        $this->lastUsed = $lastUsed;
    }

    /**
     * @return mixed
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param mixed $class
     */
    public function setClass($class): void
    {
        $this->class = $class;
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param mixed $username
     */
    public function setUsername($username): void
    {
        $this->username = $username;
    }

}
