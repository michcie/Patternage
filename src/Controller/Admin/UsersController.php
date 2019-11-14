<?php
/**
 * Created by PhpStorm.
 * User: michal
 * Date: 25.11.2018
 * Time: 14:44
 */

namespace App\Controller\Admin;


use App\Entity\ShopProduct;
use App\Entity\ShopCategory;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UsersController extends CrudController {
    /* SETTINGS */
    protected $entityClass = User::class;

    protected $sortable = 'e.id e.email e.username';
    protected $searchable = 'e.id';
    protected $sortableDefault = 'e.id asc';

    /* END SETTINGS */
    public function __construct(ValidatorInterface $validator, TranslatorInterface $translator, UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
        parent::__construct($validator, $translator);
    }


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

        $filterFormBuilder->add('username', TextType::class, [
            'required'=> false,
        ]);
        $filterFormBuilder->add('superAdmin', ChoiceType::class, [
            'required' => false,
            'choices' => [
                'panel.yes' => 'yes',
                'panel.no' => 'no',
            ]
        ]);



        return $filterFormBuilder;
    }

    protected function createActionFormBuilder(string $forAction, $entity): FormBuilderInterface
    {
        $formBuilder = $this->createFormBuilder($entity, [
            'translation_domain' => 'panel',
        ]);
        $formBuilder
            ->add('email', EmailType::class, [
            ])
            ->add('username', TextType::class, [])
            ->add('plainPassword', PasswordType::class, [
                'required' => false,
            ])
            ->add('superadmin', CheckboxType::class, [
                'label' => 'Uprawnienie administracyjne',
                'required' => false,
            ]);
        return $formBuilder;
    }
    public function createAction(Request $request)
    {
        // disable creating users - user should be created only in auth register
        throw new NotFoundHttpException();
    }


    protected function delete(&$entities)
    {
        $idiotDetected = false;
        foreach ($entities as $k => $entity) {
            if ($entity->getId() == $this->getUser()->getId()) {
                unset($entities[$k]);
                $idiotDetected = true;
                continue;
            }
        }

        if ($idiotDetected) {
            $this->addFlash('warning', 'Nie można usunąć własnego konta :)');
        }

        return parent::delete($entities);
    }

    protected function persist($entity, $formData, $action): bool
    {
        /** @var $entity User */

        // password
        if ($entity->getPlainPassword()) {
            $password = $this->passwordEncoder->encodePassword($entity, $entity->getPlainPassword());
            $entity->setPassword($password);
        }

        // persist entity with doctrine
        return parent::persist($entity, $formData, $action);
    }

    protected function listBuildQuery(QueryBuilder $queryBuilder, Request $request, ParameterBag $filterFormData): void
    {
        if ($filterFormData->has('username')) {
            $queryBuilder->andWhere('e.username = :username')
                ->setParameter(':username', $filterFormData->get('username'));
        }
        if ($filterFormData->has('superAdmin')) {
            $queryBuilder->andWhere('e.superAdmin = :superAdmin')
                ->setParameter(':superAdmin', $filterFormData->get('superAdmin') == 'yes');
        }
    }

    protected function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        return parent::findBy($criteria, $orderBy, $limit, $offset);
    }
}
