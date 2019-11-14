<?php

namespace App\DataFixtures;

use App\Entity\ShopCategory;
use App\Entity\ShopProduct;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class ProductFxitures extends Fixture implements DependentFixtureInterface
{


    public function load(ObjectManager $manager)
    {

        if(true){
            return;
        }
        $categories = $manager->getRepository(ShopCategory::class)->getCategoryWithParents();
        /** @var ShopCategory $cat */

        foreach ($categories as $cat) {
            for ($i = 0; $i < 18; $i++) {
                /** @var ShopProduct $product */
                $product = new ShopProduct();
                $product->setName("Product: " . $i . " cat. " . $cat->getName());
                $product->setDescription("Fajny opis");
                $product->setPrice(rand(100, 1000));
                $product->setQuantity(rand(10, 50));
                $product->setProducent("Ja and Me");
                $product->setUploadUpdatedAt(new \DateTime());
                $product->setCategory($cat);
                $product->setProductFeatures(json_encode([
                    "Czas pracy: " => rand(100, 600),
                    "Stan: " => "Idealny"
                ]));
                $product->setRecommendedProduct(false);
                $product->setCategory($cat);
                $manager->persist($product);
            }
        }

        $manager->flush();
    }

    /**
     * This method must return an array of fixtures classes
     * on which the implementing class depends on
     *
     * @return array
     */
    public function getDependencies()
    {
        return array(
            CategoryFixtures::class,
        );
    }
}
