<?php

namespace App\Controller\Admin;

use App\Entity\ShopCategory;
use App\Entity\ShopProduct;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GreaterThan;

class CategoryController extends CrudController
{
    /* SETTINGS */
    protected $entityClass = ShopCategory::class;

    protected $sortable = 'e.id';
    protected $searchable = 'e.id';
    protected $sortableDefault = 'e.id asc';

    /* END SETTINGS */

    protected function entityTitle($entity): string
    {
        return '#' . $entity->getId();
    }

    protected function getSecurityAttribute($action)
    {
        return 'ROLE_SUPER_ADMIN';//only admin can use it
    }

    protected function listFilterFormBuilder(array $formData, Request $request): FormBuilderInterface
    {
        $filterFormBuilder = parent::listFilterFormBuilder($formData, $request);


        $filterFormBuilder->add('navbar', CheckboxType::class, [
            'required' => false,
        ]);
        $filterFormBuilder->add('name', TextType::class, [
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

        $formBuilder->add('navbar', CheckboxType::class, [
            'required' => false,
        ]);

        $formBuilder->add('parent', EntityType::class, [
            'required' => false,
            'multiple' => false,
            'class' => ShopCategory::class,
        ]);

        $formBuilder->add('name', TextType::class);

        return $formBuilder;
    }


    protected function listBuildQuery(QueryBuilder $queryBuilder, Request $request, ParameterBag $filterFormData): void
    {
        if ($filterFormData->get('name') != null) {
            $queryBuilder->andWhere('e.name = :name')
                ->setParameter('name', $filterFormData->get('name'));
        }
        if ($filterFormData->get('parent') != null) {
            $queryBuilder->andWhere('e.parent = :parent')
                ->setParameter('parent', $filterFormData->get('parent'));
        }
        if ($filterFormData->get('navbar') != null) {
            $queryBuilder->andWhere('e.navbar = :navbar')
                ->setParameter('navbar', $filterFormData->get('navbar'));
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
         * @var  ShopCategory $entity
         */
        foreach ($entities as $k => $entity) {

            if (count($entity->getChildren()) > 0) {
                $this->addFlash('warning', 'Nie można usunać nad kategorii jeśli posiada ona sub kategorie ;)');
                unset($entities[$k]);
            }

            if (count($entity->getProducts()) > 0) {
                $this->addFlash('warning', 'Nie można usunać kategorii jeśli posiada ona jakies produkty ;)');
                unset($entities[$k]);
            }
        }

        return parent::delete($entities);
    }
}
