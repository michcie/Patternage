<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ShopCartItemRepository")
 */
class ShopCartItem
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $quantity;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ShopProduct", inversedBy="shopCartItems")
     * @ORM\JoinColumn(nullable=false)
     */
    private $product;

    /**
     * @ORM\Column(type="decimal", precision=20, scale=2)
     */
    private $totalPrice;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ShopCart", inversedBy="items")
     */
    private $shopCart;



    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getProduct(): ?ShopProduct
    {
        return $this->product;
    }

    public function setProduct(?ShopProduct $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getShopCart(): ?ShopCart
    {
        return $this->shopCart;
    }

    public function setShopCart(?ShopCart $shopCart): self
    {
        $this->shopCart = $shopCart;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTotalPrice()
    {
        return $this->totalPrice;
    }

    /**
     * @param mixed $totalPrice
     */
    public function setTotalPrice($totalPrice): void
    {
        $this->totalPrice = $totalPrice;
    }

}
