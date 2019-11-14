<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
/**
 * @ORM\Entity(repositoryClass="App\Repository\ShopCategoryRepository")
 */
class ShopCategory
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(length=255)
     * @Assert\NotBlank()
     * @Assert\Length(max=254)
     */
    private $name;

    /**
     * One Category has Many Categories.
     * @ORM\OneToMany(targetEntity="ShopCategory", mappedBy="parent")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    private $children;

    /**
     * One Category has Many Products.
     * @ORM\OneToMany(targetEntity="ShopProduct", mappedBy="category")
     */
    private $products;


    /**
     * Many Categories have One Category.
     * @ORM\ManyToOne(targetEntity="ShopCategory", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", onDelete="CASCADE", referencedColumnName="id")
     */
    private $parent;

    /**
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    private $navbar;


    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->products = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param mixed $children
     */
    public function setChildren($children): void
    {
        $this->children = $children;
    }

    /**
     * @return mixed
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param mixed $parent
     */
    public function setParent($parent): void
    {
        $this->parent = $parent;
    }

    /**
     * @return mixed
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * @param mixed $products
     */
    public function setProducts($products): void
    {
        $this->products = $products;
    }

    /**
     * @return mixed
     */
    public function getNavbar()
    {
        return $this->navbar;
    }

    /**
     * @param mixed $navbar
     */
    public function setNavbar($navbar): void
    {
        $this->navbar = $navbar;
    }
    public function __toString()
    {
        return $this->getName();
    }

}
