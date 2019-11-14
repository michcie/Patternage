<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
/**
 * @ORM\Entity(repositoryClass="App\Repository\ShopProductRepository")
 */
class ShopProduct
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(length=255, unique=true)
     * @Assert\NotBlank()
     * @Assert\Length(max=254)
     */
    private $name;
//     * @Assert\Regex(pattern="/^[a-zA-Z0-9_]+$/")

    /**
     * @var ShopCategory
     * @ORM\ManyToOne(targetEntity="ShopCategory", inversedBy="products")
     */
    private $category;


    /**
     * @var double
     * @ORM\Column(type="decimal", precision=7, scale=2)
     */
    private $price;

    /**
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    private $recommendedProduct;
    /**
     * @var string
     * @ORM\Column(type="text")
     * @Assert\NotBlank()
     */
    private $description;

    /**
     * @var string
     * @ORM\Column(length=255)
     * @Assert\NotBlank()
     * @Assert\Length(max=254)
     */
    private $producent;


    /**
     * @var string
     * @ORM\Column(type="text")
     * @Assert\NotBlank()
     */
    private $productFeatures;


    /**
     * @Vich\UploadableField(mapping="product_images", fileNameProperty="imageFile")
     * @Assert\Image(
     *     detectCorrupted=true,
     *     allowLandscape=true,
     *     allowSquare=false,
     *     minWidth = 100,
     *     maxWidth = 100,
     *     minHeight = 200,
     *     maxHeight = 200,
     *     maxSize="1M"
     * )
     */
    private $imageFile;


    /**
     * @Vich\UploadableField(mapping="product_images", fileNameProperty="imageFileBigger")
     * @Assert\Image(
     *     detectCorrupted=true,
     *     allowLandscape=true,
     *     allowSquare=false,
     *     minWidth = 200,
     *     maxWidth = 200,
     *     minHeight = 400,
     *     maxHeight = 400,
     *     maxSize="1M"
     * )
     */
    private $imageFileBigger;

    /**
     * @ORM\Column(type="text")
     * @var string
     */
    private $image;

    /**
     * @ORM\Column(type="text")
     * @var string
     */
    private $icon;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    private $uploadUpdatedAt;

    /**
     * @var integer
     * @ORM\Column(type="integer")
     * @Assert\NotBlank()
     */
    private $quantity;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\ShopCartItem", mappedBy="product")
     */
    private $shopCartItems;

    public function __construct()
    {
        $this->shopCartItems = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return ShopCategory
     */
    public function getCategory(): ?ShopCategory
    {
        return $this->category;
    }

    /**
     * @param ShopCategory $category
     */
    public function setCategory(ShopCategory $category): void
    {
        $this->category = $category;
    }

    /**
     * @return float
     */
    public function getPrice(): ?float
    {
        return $this->price;
    }

    /**
     * @param float $price
     */
    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getProducent(): ?string
    {
        return $this->producent;
    }

    /**
     * @param string $producent
     */
    public function setProducent(string $producent): void
    {
        $this->producent = $producent;
    }

    /**
     * @return string
     */
    public function getProductFeatures(): ?string
    {
        return $this->productFeatures;
    }

    /**
     * @param array $productFeatures
     */
    public function setProductFeatures(string $productFeatures): void
    {
        $this->productFeatures = $productFeatures;
    }

    public function getProductFeaturesArray(): ?array
    {
        return json_decode($this->productFeatures,true);
    }

    /**
     * @return mixed
     */
    public function getImageFile()
    {
        return $this->imageFile;
    }

    /**
     * @param mixed $imageFile
     */
    public function setImageFile($imageFile): void
    {
        $this->imageFile = $imageFile;
    }

    /**
     * @return \DateTime
     */
    public function getUploadUpdatedAt(): \DateTime
    {
        return $this->uploadUpdatedAt;
    }

    /**
     * @param \DateTime $uploadUpdatedAt
     */
    public function setUploadUpdatedAt(\DateTime $uploadUpdatedAt): void
    {
        $this->uploadUpdatedAt = $uploadUpdatedAt;
    }

    /**
     * @return int
     */
    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     */
    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    /**
     * @return Collection|ShopCartItem[]
     */
    public function getShopCartItems(): Collection
    {
        return $this->shopCartItems;
    }

    public function addShopCartItem(ShopCartItem $shopCartItem): self
    {
        if (!$this->shopCartItems->contains($shopCartItem)) {
            $this->shopCartItems[] = $shopCartItem;
            $shopCartItem->setProduct($this);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getImage(): string
    {
        return $this->image;
    }

    /**
     * @param string $image
     */
    public function setImage(string $image): void
    {
        $this->image = $image;
    }

    /**
     * @return string
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * @param string $icon
     */
    public function setIcon(string $icon): void
    {
        $this->icon = $icon;
    }



    public function removeShopCartItem(ShopCartItem $shopCartItem): self
    {
        if ($this->shopCartItems->contains($shopCartItem)) {
            $this->shopCartItems->removeElement($shopCartItem);
            // set the owning side to null (unless already changed)
            if ($shopCartItem->getProduct() === $this) {
                $shopCartItem->setProduct(null);
            }
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isRecommendedProduct(): ?bool
    {
        return $this->recommendedProduct;
    }

    /**
     * @param bool $recommendedProduct
     */
    public function setRecommendedProduct(bool $recommendedProduct): void
    {
        $this->recommendedProduct = $recommendedProduct;
    }

    /**
     * @return mixed
     */
    public function getImageFileBigger()
    {
        return $this->imageFileBigger;
    }

    /**
     * @param mixed $imageFileBigger
     */
    public function setImageFileBigger($imageFileBigger): void
    {
        $this->imageFileBigger = $imageFileBigger;
    }





}
