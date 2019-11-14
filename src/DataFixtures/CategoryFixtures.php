<?php
/**
 * Created by PhpStorm.
 * User: michal
 * Date: 20.10.2018
 * Time: 18:58
 */

namespace App\DataFixtures;


use App\Entity\ShopCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        if(true){
            return;
        }
        for ($i = 0; $i < 5; $i++) {
            /** @var ShopCategory $category */
            $category = new ShopCategory();
            $category->setName("Cat: " . $i);
            $category->setNavbar(rand(0, 1) == 0 ? true : false);
            $manager->persist($category);
            for ($a = 0; $a < 3; $a++) {
                /** @var ShopCategory $sub */
                $sub = new ShopCategory();
                $sub->setName("Sub: " . $a . " Cat: " . $i);
                $sub->setParent($category);
                $sub->setNavbar(false);
                $manager->persist($sub);
            }
        }

        $manager->flush();
    }
}