<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ShopCartRepository")
 */
class ShopCart
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ShopCartItem", mappedBy="shopCart")
     */
    private $items;

    /**
     * @ORM\Column(type="decimal", precision=20, scale=3)
     */
    private $totalPrice = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $itemsTotal = 0;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection|ShopCartItem[]
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(ShopProduct $item, $em): self
    {
        /** @var ShopCartItem $it */
        foreach ($this->items as $it) {
            if ($it->getProduct()->getId() == $item->getId()) {
                $it->setQuantity($it->getQuantity() + 1);
                $it->setTotalPrice($it->getQuantity() * $item->getPrice());
                return $this;
            }
        }
        $cartItem = new ShopCartItem();
        $cartItem->setQuantity(1);
        $cartItem->setProduct($item);
        $cartItem->setTotalPrice($item->getPrice());
        $cartItem->setShopCart($this);
        $em->persist($cartItem);

        $this->itemsTotal += 1;
        $this->items[] = $cartItem;

        return $this;
    }

    public function recalculateData()
    {
        /** @var ShopCartItem $it */
        $this->itemsTotal = 0;
        $this->totalPrice = 0;
        foreach ($this->items as $it) {
            $this->itemsTotal += 1;
            $this->totalPrice += $it->getTotalPrice();
        }
    }


    public function changeAmount(ShopProduct $item, $amount, $em)
    {
        /** @var ShopCartItem $it */
        foreach ($this->items as $it) {
            if ($it->getProduct()->getId() == $item->getId()) {
                $it->setQuantity($amount);
                $it->setTotalPrice($it->getQuantity() * $item->getPrice());
                $em->persist($it);
                return $it->getQuantity();
            }
        }
    }

    public function removeItem(ShopProduct $item, $em): self
    {

        /** @var ShopCartItem $it */
        foreach ($this->items as $it) {
            if ($it->getProduct()->getId() == $item->getId()) {
                $this->items->removeElement($it);
                // set the owning side to null (unless already changed)
                if ($it->getShopCart() === $this) {
                    $it->setShopCart(null);
                }
                $em->remove($it);
                $this->itemsTotal -= 1;
                return $this;
            }
        }

        return $this;
    }

    public function getTotalPrice()
    {
        return $this->totalPrice;
    }

    public function setTotalPrice($totalPrice): self
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    public function getItemsTotal(): ?int
    {
        return $this->itemsTotal;
    }

    public function setItemsTotal(int $itemsTotal): self
    {
        $this->itemsTotal = $itemsTotal;

        return $this;
    }
}
