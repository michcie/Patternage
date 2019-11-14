<?php

namespace App\Controller\Admin;

use App\Controller\Controller;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


/**
 * Class BaseCrudController
 * @package App\Controller\Panel\Admin
 *
 * Założenia:
 * 1. Nazwy route musza byc wedlug wzoru:
 *  panel_{entity_table_name}_(list|edit|create|delete)
 * 2. Widoki:
 *  panel/{entity_table_name}/(list|edit|create)
 * 3. Role dostepu:
 *  ROLE_{strtoupper(entity_table_name)}_(READ|WRITE)
 * 4. Encja musi mieć pole 'id'
 * ...
 */
abstract class CrudController extends Controller
{
    /* SETTINGS */
    protected $entityClass = '';
    protected $sortable = 'e.id';
    protected $searchable = 'e.id';
    protected $sortableDefault = 'e.id asc';
    protected $sortableNatural = [];
    /* END SETTINGS */

    /** @var string */
    protected $entityName;
    /** @var ClassMetadata */
    protected $entityMeta;

    /** @var EntityManager */
    protected $em;
    /** @var EntityRepository */
    protected $repo;
    /** @var TranslatorInterface */
    protected $translator;
    /** @var ValidatorInterface */
    protected $validator;

    public function __construct(ValidatorInterface $validator, TranslatorInterface $translator)
    {
        if (!class_exists($this->entityClass)) {
            throw new \InvalidArgumentException('Class $this->entityClass dont exist!');
        }
        $this->translator = $translator;
        $this->validator = $validator;
    }

    public function listAction(Request $request)
    {
        $listData = $this->_list($request);

        if ($listData instanceof Response) {
            return $listData;
        }
        return $this->render('panel/' . $this->entityName . '/list.html.twig', $listData);
    }

    protected function getListSecurityAttribute(Request $request)
    {
        return 'list';
    }

    protected function _list(Request $request)
    {
        //check auth and initalizing basic paramaters, like EntityRepository
        $this->init();
        $this->checkGranted($this->getSecurityAttribute($this->getListSecurityAttribute($request)));

        // filter form
        $filterFormBuilder = $this->listFilterFormBuilder(['perPage' => 25], $request);
        $filterForm = $filterFormBuilder->getForm();

        $filterForm->handleRequest($request);

        $filterFormData = new ParameterBag();
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            //filter form is submited, copy data to parameter bag and checking\
            // if user not override standard maxPerPage element
            $filterFormData->add(array_filter($filterForm->getData()));
            $filterFormData->set('perPage', $this->getMaxItemsPerPage($filterFormData));
        }

        // list paramteres save
        if ($request->query->count() === 0) {
            $reqParamsQuery = $request->getSession()->get('last_list_data_' . $this->entityName);
            if (\is_array($reqParamsQuery) && \count($reqParamsQuery) > 0) {
                return $this->redirectRewrite($request, $reqParamsQuery);
            }
        }
        //saveing last paramters, so when we change page, all filter will not disappear
        $request->getSession()->set('last_list_data_' . $this->entityName, $request->getMethod() === 'POST' ? [] : $request->query->all());

        // build query
        $queryBuilder = $this->repo->createQueryBuilder('e');
        if ($this->searchable && $filterFormData->has('search') && $filterFormData->get('search')) {

            $searchable = $this->searchable ? explode(' ', $this->searchable) : [];
            //Making array from searchable string and getting value from form
            $sf = $filterFormData->get('search');
            $queryBuilder->setParameter(':search', $sf);
            //if in search we found % we making LIKE in sql otherwise equals
            $sOperator = strpos($sf, '%') !== FALSE ? 'LIKE' : '=';
            $whSa = [];
            foreach ($searchable as $field) {
                $whSa[] = "{$field} {$sOperator} :search";
            }
            $queryBuilder->andWhere(implode(' OR ', $whSa));
        }

        // sort
        $sortableField = $request->get('sort_field');

        if ($sortableField && \in_array($sortableField, explode(' ', $this->sortable))) {
            //sorting with sortable field defined in class
            $sortableDirection = strtolower($request->get('sort_direction')) === 'desc' ? 'desc' : 'asc';

            if (in_array($sortableField, $this->sortableNatural)) {
                $queryBuilder->addOrderBy("LENGTH(" . $sortableField . ")", $sortableDirection);
            }
            $queryBuilder->addOrderBy($sortableField, $sortableDirection);

            $request->query->set('sort_field', $sortableField);
            $request->query->set('sort_direction', $sortableDirection);
        } else {
            //sorting by default field f.e id
            $defaultSortable = explode(' ', $this->sortableDefault);
            if (in_array($defaultSortable[0], $this->sortableNatural)) {
                $queryBuilder->addOrderBy("LENGTH(" . $defaultSortable[0] . ")", $defaultSortable[1]);
            }
            $queryBuilder->addOrderBy($defaultSortable[0], $defaultSortable[1]);

            $request->query->set('sort_field', $defaultSortable[0]);
            $request->query->set('sort_direction', $defaultSortable[1]);
        }

        //we running this method to allow add specific constrain
        //f.e when we allow to filter for two product we can make in this method somethink like this:
        /*
         if ($filterFormData->get('products') && count($filterFormData->get('products')) > 0) {
            $queryBuilder->andWhere('e.product IN (:products)');
            $queryBuilder->setParameter(':products', $filterFormData->get('products'));
        }
         */
        $this->listBuildQuery($queryBuilder, $request, $filterFormData);

        //checking if user can delete Entity
        $canDelete = $this->isGranted($this->getSecurityAttribute('delete'));

        // pagination stuff
        $adapter = new DoctrineORMAdapter($queryBuilder);
        $entities = new Pagerfanta($adapter);
        //checking if what we get from form is in our bounds
        $entities->setMaxPerPage($this->getMaxItemsPerPage($filterFormData));

        $cpage = $request->get('page');
        if ($cpage <= 0) {
            $cpage = 1;
        } elseif ($cpage > $entities->getNbPages()) {
            $cpage = $entities->getNbPages();
        }

        $entities->setCurrentPage($cpage);

        $formBuilder = null;
        //if can delete generate deleteForm...
        if ($canDelete) {
            $formBuilder = $this->deleteFormBuilder();
        }

        //return inportant field for us later
        return [
            'entityName' => $this->entityName,
            'entities' => $entities,
            'canDelete' => $canDelete,
            'primaryField' => $this->entityMeta->getSingleIdentifierFieldName(),
            'title' => $this->trans('panel.' . $this->entityName . '.name_plural', [], 'panel'),
            'deleteForm' => $formBuilder ? $formBuilder->getForm()->createView() : null,
            'filterForm' => $filterForm->createView(),
        ];
    }

    public function createAction(Request $request)
    {
        $createData = $this->_create($request->get('id'), $request);

        if ($createData instanceof Response) {
            return $createData;
        }

        return $this->render('panel/' . $this->entityName . '/form.html.twig', $createData);
    }

    protected function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        return $this->repo->findBy($criteria, $orderBy, $limit, $offset);
    }

    protected function findOneBy(array $criteria, array $orderBy = null)
    {
        $entities = $this->findBy($criteria, $orderBy);
        return $entities && count($entities) > 0 ? $entities[0] : null;
    }

    protected function find($id)
    {
        if (!$id) {
            return null;
        }

        $entities = $this->findBy([
            $this->entityMeta->getSingleIdentifierFieldName() => $id,
        ]);

        return $entities && count($entities) > 0 ? $entities[0] : null;
    }

    protected function _create($id, Request $request)
    {
        //checking permission
        $this->init();
        $this->checkGranted($this->getSecurityAttribute('create'));

        //creating new entity object
        $entity = new $this->entityClass;

        $action = 'create';
        //if we have id it will be Copy action
        if ($id) {
            //look for entity which we will by copy
            $baseEntity = $this->find($id);
            if ($baseEntity) {
                $action = 'create_from';

                $clearFields = [];
                $validMeta = $this->validator->getMetadataFor($this->entityClass);
                //get all fields which can't be coppied(Unique constraint)
                foreach ($validMeta->getConstraints() as $constraint) {
                    if ($constraint instanceof UniqueEntity) {
                        if (\is_array($constraint->fields)) {//if array push whole array into $clearFields
                            foreach ($constraint->fields as $field) {
                                $clearFields[] = $field;
                            }
                        } else {
                            $clearFields[] = $constraint->fields;
                        }
                    }
                }
                //lets Coppy field but excluded $clearFields which should be clear...
                foreach ($this->entityMeta->getFieldNames() as $fieldName) {
                    if (!\in_array($fieldName, $clearFields)) {
                        $this->entityMeta->setFieldValue($entity, $fieldName, $this->entityMeta->getFieldValue($baseEntity, $fieldName));
                    }
                }
            }
        }

        //create form and heandle it
        $form = $this->createActionFormBuilder($action, $entity)->getForm();
        $form->handleRequest($request);

        $primaryField = $this->entityMeta->getSingleIdentifierFieldName();

        if ($form->isSubmitted() && $form->isValid()) {

            if ($this->persist($entity, $form->getData(), 'create')) {
                $this->em->flush();

                $id = $this->entityMeta->getFieldValue($entity, $primaryField);
                $this->doActionLog($entity, $this->entityName . '_create', [
                    $primaryField => $id
                ]);

                $this->addFlash('success', 'Tworzenie zakończone sukcesem.');
            }

            if ($request->request->get('goto')) {
                return $this->redirect(str_replace(urlencode('%id%'), $id, $request->request->get('goto')));
            }
            return $this->redirectToRoute('panel_' . $this->entityName . '_edit', [
                'id' => $id,
            ]);
        }

        return [
            'entity' => $entity,
            'entityName' => $this->entityName,
            'form' => $form->createView(),
            'primaryField' => $primaryField,
            'listRoute' => 'panel_' . $this->entityName . '_list',
            'action' => $action,
            'title' => $this->trans('panel.' . $this->entityName . '.name_plural', [''], 'panel'),
            'subtitle' => $this->trans('panel.' . $this->entityName . '.create', [], 'panel'),
        ];
    }

    public function editAction($id, Request $request)
    {
        $editData = $this->_edit($id, $request);

        if ($editData instanceof Response) {
            return $editData;
        }

        return $this->render('panel/' . $this->entityName . '/form.html.twig', $editData);
    }

    protected function getSecurityAttribute($action)
    {
        if (\in_array($action, ['delete', 'edit_write', 'create'])) {
            $action = 'write';
        }
        if (\in_array($action, ['list', 'edit'])) {
            $action = 'read';
        }
        return strtoupper($action) . '_' . strtoupper($this->entityName);
    }

    protected function _edit($id, Request $request)
    {
        $this->init();

        $entity = $this->find($id);
        if (!$entity) {
            throw new NotFoundHttpException();
        }

        $this->checkGranted($this->getSecurityAttribute('edit'), $entity);
        $canWrite = $this->isGranted($this->getSecurityAttribute('edit_write'), $entity);

        $formBuilder = $this->createActionFormBuilder('edit', $entity);

        if (!$canWrite) {
            foreach ($formBuilder->all() as $element) {
                if ($element instanceof FormBuilder) {
                    $element->setDisabled(true);
                }
            }
        }

        $form = $formBuilder->getForm();

        $primaryField = $this->entityMeta->getSingleIdentifierFieldName();
        if ($canWrite) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                //get his id before persits so we can log his old id :D
                $id = $this->entityMeta->getFieldValue($entity, $primaryField);

                if ($this->persist($entity, $form->getData(), 'edit')) {
                    $uow = $this->em->getUnitOfWork();
                    $uow->computeChangeSets();
                    $changeset = $uow->getEntityChangeSet($entity);
                    $this->doActionLog($entity, $this->entityName . '_edit', [
                        $primaryField => $id,
                        'changes' => $changeset
                    ]);

                    $this->em->flush();//PUSH TO DATABASE!
                    $this->addFlash('success', 'Edycja zakończona sukcesem.');
                }

                //if go to send to goto otherwise allow to edit created entity
                if ($request->request->get('goto')) {
                    return $this->redirect($request->request->get('goto'));
                }
                return $this->redirectToRoute('panel_' . $this->entityName . '_edit', [
                    'id' => $id,
                ]);
            }
        }

        return [
            'entity' => $entity,
            'entityName' => $this->entityName,
            'action' => 'edit',
            'primaryField' => $primaryField,
            'title' => $this->trans('panel.' . $this->entityName . '.name_plural', [], 'panel'),
            'subtitle' => $this->trans('panel.' . $this->entityName . '.edit', ['%name%' => $this->entityTitle($entity)], 'panel'),
            'canWrite' => $canWrite, 'listRoute' => 'panel_' . $this->entityName . '_list', 'form' => $form->createView(),
        ];
    }

    protected function doActionLog($subject, $actionName, $data)
    {
//        $this->actionLog($actionName, $data);
    }

    public function deleteAction(Request $request)
    {
        //ONLY ALLOW TO DELETE WITH POST ~!!!!
        if ($request->getMethod() !== Request::METHOD_POST) {
            throw new NotFoundHttpException();
        }

        //check permission
        $this->init();
        $this->checkGranted($this->getSecurityAttribute('delete'));

        $formB = $this->deleteFormBuilder();
        //if we don't define delete form in our MVC disallow using this route!
        if (!$formB) {
            throw new NotFoundHttpException();
        }

        $form = $formB->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //get all data
            $primaryField = $this->entityMeta->getSingleIdentifierFieldName();
            $redirectTo = $form->getData()['redirectTo'];
            $toRemoveIdsData = $form->getData()['toDeleteIds'];
            $removed = 0;
            $entities = null;
            $entitiesFormatted = [];

            //repeat process when we correctly remove elements
            do {
                $removedLocal = 0;
                if ($toRemoveIdsData == "*ALL*") {
                    //REMOVE everythink, can crush website, make 502 error, be carefull :D
                    $entitiesQb = $this->repo->createQueryBuilder('e');
                    $this->listBuildQuery($entitiesQb, $request, new ParameterBag());
                    $entitiesQb->delete();
                    $removed = $entitiesQb->getQuery()->execute();
                    $entitiesFormatted = null;
                } else {
                    //standard remove all entities which were given in request
                    $entities = $this->findBy([$primaryField => explode(',', $toRemoveIdsData)]);
                    if ($entities) {
                        $removedLocal = $this->delete($entities);
                        if ($removedLocal > 0) {
                            foreach ($entities as $entity) {
                                $entitiesFormatted[] = [
                                    $primaryField => $this->entityMeta->getFieldValue($entity, $primaryField),
                                ];
                            }
                            $this->em->flush();
                            $removed += $removedLocal;
                        }
                    }
                }

            } while ($removedLocal > 0);

            if ($removed > 0) {
                //show nice message and log stuff
                $this->doActionLog($entities ?: null, $this->entityName . '_delete', [
                    'removed' => $entitiesFormatted !== null ? $entitiesFormatted : "*all*",
                ]);
                $this->addFlash('success', 'Usunięto poprawnie ' . $removed . ' rekordów.');
            }

            if ($redirectTo) {
                $this->redirect($redirectTo);
            }
        }

        return $this->redirectToReferer($request);
    }

    protected function deleteFormBuilder(): ?FormBuilderInterface
    {
        //basic delete form elements(every form must have they!)
        $formB = $this->createFormBuilderNamed('deleteForm', null, [
            'action' => $this->generateUrl('panel_' . $this->entityName . '_delete')
        ]);
        $formB->add('toDeleteIds', HiddenType::class);
        $formB->add('redirectTo', HiddenType::class);
        return $formB;
    }

    protected function delete(&$entities)
    {
        $removed = 0;
        foreach ($entities as $e) {
            $this->em->remove($e);
            $removed++;
        }
        return $removed;
    }

    protected function persist($entity, $formData, $action): bool
    {
        $this->em->persist($entity);
        return true;
    }

    protected function init()
    {
        $this->initAuth();
        $this->em = $this->getDoctrine()->getManagerForClass($this->entityClass);
        $this->repo = $this->em->getRepository($this->entityClass);
        $this->entityMeta = $this->em->getClassMetadata($this->entityClass);
        $this->entityName = $this->entityMeta->getTableName();
    }

    protected function initAuth()
    {
        $this->checkAuth(true, false);
    }

    protected function listFilterFormBuilder(array $formData, Request $request): FormBuilderInterface
    {
        $formBuilder = $this->createFormBuilderNamed('filter', $formData, [
            'translation_domain' => 'panel',
            'method' => 'GET',
            'csrf_protection' => false,
        ]);

        $formBuilder
            ->add('perPage', ChoiceType::class, [
                'translation_domain' => false,
                'choices' => array(
                    '10' => 10,
                    '25' => 25,
                    '50' => 50,
                    '100' => 100,
                ),
            ]);

        if ($this->searchable) {
            $formBuilder->add('search', TextType::class, [
                'required' => false,
            ]);
        }

        return $formBuilder;
    }

    protected function getMaxItemsPerPage(ParameterBag $filterData): int
    {
        $perPage = (int)$filterData->get('perPage') ?: 25;
        $perPage = $perPage > 10 ? $perPage : 10;
        $perPage = $perPage <= 100 ? $perPage : 100;
        return $perPage;
    }

    abstract protected function createActionFormBuilder(string $forAction, $entity): FormBuilderInterface;

    abstract protected function entityTitle($entity): string;

    protected function listBuildQuery(QueryBuilder $queryBuilder, Request $request, ParameterBag $filterFormData): void
    {
    }

    protected function trans($id, array $parameters = array(), $domain = null, $locale = null)
    {
        return $this->translator->trans($id, $parameters, $domain, $locale);
    }

    protected function isGranted($attributes, $subject = null): bool
    {
        if ($attributes === null) {
            return true;
        }
        return parent::isGranted($attributes, $subject);
    }
}