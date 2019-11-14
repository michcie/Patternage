<?php
/**
 * Created by PhpStorm.
 * User: michal
 * Date: 25.11.2018
 * Time: 14:44
 */

namespace App\Controller\Admin;


use App\Entity\ShopCartItem;
use App\Entity\ShopProduct;
use App\Entity\ShopCategory;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraint;

class ProductController extends CrudController
{
    /* SETTINGS */
    protected $entityClass = ShopProduct::class;


    protected $sortable = 'e.id e.category e.name e.price e.producent e.quantity';
    protected $searchable = 'e.id';
    protected $sortableDefault = 'e.id asc';

    /* END SETTINGS */

    protected function entityTitle($entity): string
    {
        return '#' . $entity->getId();
    }

    protected function getSecurityAttribute($action)
    {
        return 'ROLE_SUPER_ADMIN';
    }

    protected function listFilterFormBuilder(array $formData, Request $request): FormBuilderInterface
    {
        $filterFormBuilder = parent::listFilterFormBuilder($formData, $request);

        $filterFormBuilder->add('name', TextType::class, [
            'required' => false,
        ]);
        $filterFormBuilder->add('category', EntityType::class, [
            'class' => ShopCategory::class,
            'required' => false,
        ]);
        $filterFormBuilder->add('producent', TextType::class, [
            'required' => false,
        ]);

        return $filterFormBuilder;
    }

    protected function createActionFormBuilder(string $forAction, $entity): FormBuilderInterface
    {
        $formBuilder = $this->createFormBuilder($entity, [
            'translation_domain' => 'panel',
            'validation_groups' => array($forAction, Constraint::DEFAULT_GROUP),
        ]);

        $formBuilder->add('name', TextType::class, [
            'required' => true,
        ]);
        $formBuilder->add('category', EntityType::class, [
            'class' => ShopCategory::class,
        ]);
        $formBuilder->add('price', IntegerType::class, [
            'required' => true,
        ]);
        $formBuilder->add('description', TextType::class, [
            'required' => true,
        ]);
        $formBuilder->add('producent', TextType::class, [
            'required' => true,
        ]);
        $formBuilder->add('productFeatures', TextareaType::class, [
            'attr' => [
                'rows' => '4',
                'style' => 'width: 100%',
            ],
            'required' => true,
        ]);
        $formBuilder->add('quantity', IntegerType::class, [
            'required' => true,
        ]);
        $formBuilder->add('recommendedProduct', CheckboxType::class, [
            'required' => false,
        ]);
        $formBuilder->add('imageFile', FileType::class, [
            'required' => false,
            'data_class' => null,
        ]);
        $formBuilder->add('imageFileBigger', FileType::class, [
            'required' => false,
            'data_class' => null,
        ]);
        return $formBuilder;
    }


    protected function persist($entity, $formData, $action): bool
    {
        /** @var ShopProduct $ent */
        $entity->setUploadUpdatedAt(new \DateTime());
        // transform image
        if ($entity->getImageFile()) {
            $data = file_get_contents($entity->getImageFile());
            $base64 = 'data:' . $entity->getImageFile()->getMimeType() . ';base64,' . base64_encode($data);
            $entity->setImage($base64);
        }
        if ($entity->getImageFileBigger()) {
            $data = file_get_contents($entity->getImageFileBigger());
            $base64 = 'data:' . $entity->getImageFileBigger()->getMimeType() . ';base64,' . base64_encode($data);
            $entity->setIcon($base64);
        }
        return parent::persist($entity, $formData, $action);
    }

    protected function listBuildQuery(QueryBuilder $queryBuilder, Request $request, ParameterBag $filterFormData): void
    {
        if ($filterFormData->get('name') != null) {
            $queryBuilder->andWhere('e.name = :name')
                ->setParameter('name', $filterFormData->get('name'));
        }
        if ($filterFormData->get('category') != null) {
            $queryBuilder->andWhere('e.category = :category')
                ->setParameter('category', $filterFormData->get('category'));
        }
        if ($filterFormData->get('producent') != null) {
            $queryBuilder->andWhere('e.producent = :producent')
                ->setParameter('producent', $filterFormData->get('producent'));
        }
    }

    protected function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        return parent::findBy($criteria, $orderBy, $limit, $offset);
    }


    protected function delete(&$entities)
    {
        /**
         * @var integer $k
         * @var  ShopProduct $entity
         */
        foreach ($entities as $k => $entity) {
            if (count($entity->getShopCartItems()) > 0) {
                /** @var EntityManager $em */
                $em = $this->getDoctrine()->getManagerForClass(ShopCartItem::class);

                /** @var ShopCartItem $item */
                foreach ($entity->getShopCartItems() as $item) {
                    $em->remove($item);
                }
                $em->flush();
                $this->addFlash('warning', 'Przedmiot znajdowal sie w koszykach uzytkownikow!');
            }
        }

        return parent::delete($entities);
    }

}
