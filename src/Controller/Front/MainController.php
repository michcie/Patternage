<?php

namespace App\Controller\Front;

use App\Entity\ShopCart;
use App\Entity\ShopCartItem;
use App\Entity\ShopCategory;
use App\Entity\ShopProduct;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use App\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class MainController extends Controller
{
    protected $searchable = 'e.id e.name';

    public function index(Request $request)
    {
        return $this->render("front/index.html.twig", $this->prepareDefaultParemeters($request));
    }

    public function product(Request $request, $id)
    {
        $array = $this->prepareDefaultParemeters($request);
        /** @var ShopProduct $entity */
        $entity = $this->getDoctrine()->getRepository(ShopProduct::class)->find($id);
        $array['entity'] = $entity;
        return $this->render("front/product.html.twig", $array);
    }

    protected function prepareDefaultParemeters(Request $request)
    {
        /** @var  $categories */
        $categories = $this->getDoctrine()->getRepository(ShopCategory::class)->findBy([
            "parent" => null,
        ]);
        // filter
        $filterFormBuilder = $this->listFilterFormBuilder(['perPage' => 25], $request);
        $filterForm = $filterFormBuilder->getForm();

        $filterForm->handleRequest($request);
        $filterFormData = new ParameterBag();
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $filterFormData->add(array_filter($filterForm->getData()));
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = $this->getDoctrine()->getRepository(ShopProduct::class)->createQueryBuilder('e');
            //check search...
            if ($filterFormData->has('search') && $filterFormData->get('search')) {
                $searchable = $this->searchable ? explode(' ', $this->searchable) : [];
                $sf = $filterFormData->get('search');
                $sf = '%' . $sf . '%';
                $queryBuilder->setParameter(':search', $sf);
                $whSa = [];
                foreach ($searchable as $field) {
                    $whSa[] = "{$field} LIKE :search";
                }
                $queryBuilder->andWhere(implode(' OR ', $whSa));
            }

            $adapter = new DoctrineORMAdapter($queryBuilder);
            $entities = new Pagerfanta($adapter);
            $entities->setMaxPerPage(9);

            $cpage = $request->get('page');
            if ($cpage <= 0) {
                $cpage = 1;
            } elseif ($cpage > $entities->getNbPages()) {
                $cpage = $entities->getNbPages();
            }

            $entities->setCurrentPage($cpage);

            return [
                "categories" => $categories,
                'filterForm' => $filterForm->createView(),
                'entities' => $entities,
                'type'=> 'standard',
                'cart' => $this->getCartData(),
            ];
        }else {
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = $this->getDoctrine()->getRepository(ShopProduct::class)->createQueryBuilder('e');
            $queryBuilder->andWhere('e.recommendedProduct = :param');
            $queryBuilder->setParameter(':param', 1);
            $adapter = new DoctrineORMAdapter($queryBuilder);
            $entities = new Pagerfanta($adapter);
            $entities->setMaxPerPage(9);
            return [
                "categories" => $categories,
                'filterForm' => $filterForm->createView(),
                'cart' => $this->getCartData(),
                'type' => 'recommended',
                'entities' => $entities,
            ];
        }
    }

    public function getCartData()
    {
        /** @var ShopCart $data */
        $data = $this->getCartById();
        $data->recalculateData();
        return [
            'total' => $data->getTotalPrice(),
            'itemsCount' => $data->getItemsTotal(),
            'items' => $data->getItems(),
        ];
    }

    public function getCartById()
    {
        if ($this->get('session')->has("shopId")) {
            $data = $this->getDoctrine()->getRepository(ShopCart::class)->find($this->get('session')->get("shopId"));
            if ($data == null) {
                $data = new ShopCart();
            }
            return $data;
        }
        return new ShopCart();
    }

    public function addToCart(Request $request)
    {
        /** @var ShopCart $data */
        $data = $this->getCartById();
        /** @var ShopProduct $product */
        $product = $this->getDoctrine()->getRepository(ShopProduct::class)->find($request->request->get('id'));
        $em = $this->getDoctrine()->getEntityManager();
        $data->addItem($product, $em);
        $data->recalculateData();
        $em->persist($data);
        $em->flush();
        $this->get('session')->set("shopId", $data->getId());
        return new JsonResponse([
            'action' => "Pomyślnie dodano przedmiot do koszyka",
            'currentAmmount' => $data->getItemsTotal(),
        ]);
    }

    public function changeAmount(Request $request)
    {
        /** @var ShopCart $data */
        $data = $this->getCartById();
        /** @var ShopProduct $product */
        $product = $this->getDoctrine()->getRepository(ShopProduct::class)->find($request->request->get('id'));
        $em = $this->getDoctrine()->getEntityManager();
        $newAm = $data->changeAmount($product, $request->request->get("amount"), $em);
        $data->recalculateData();
        $em->persist($data);
        $em->flush();
        $this->get('session')->set("shopId", $data->getId());
        return new JsonResponse(["price" => $data->getTotalPrice(), "quant" => $newAm]);
    }

    public function removeFromCart(Request $request)
    {
        /** @var ShopCart $data */
        $data = $this->getCartById();
        /** @var ShopProduct $product */
        $product = $this->getDoctrine()->getRepository(ShopProduct::class)->find($request->request->get('id'));
        $em = $this->getDoctrine()->getEntityManager();
        $data->removeItem($product, $em);
        $data->recalculateData();
        $em->persist($data);
        $em->flush();

        $this->get('session')->set("shopId", $data->getId());
        return $this->responseAjaxSuccess([
            'action' => "Pomyślnie usunięto przedmiot do koszyka",
            'currentAmmount' => $data->getItemsTotal(),
        ]);
    }

    public function category(Request $request, $category)
    {
        $categories = $this->getDoctrine()->getRepository(ShopCategory::class)->findBy([
            "parent" => null,
        ]);
        $cat = $this->getDoctrine()->getRepository(ShopCategory::class)->findOneBy([
            'name' => $category,
        ]);

        $filterFormBuilder = $this->listFilterFormBuilder(['perPage' => 25], $request);
        $filterForm = $filterFormBuilder->getForm();

        $filterForm->handleRequest($request);
        $filterFormData = new ParameterBag();
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $filterFormData->add(array_filter($filterForm->getData()));
        }

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getRepository(ShopProduct::class)->createQueryBuilder('e');
        $queryBuilder
            ->andWhere('e.category = :cat')
            ->setParameter(':cat', $cat);
        // pagination

        //check search...
        if ($filterFormData->has('search') && $filterFormData->get('search')) {
            $searchable = $this->searchable ? explode(' ', $this->searchable) : [];
            $sf = $filterFormData->get('search');
            $queryBuilder->setParameter(':search', $sf);
            $sOperator = strpos($sf, '%') !== FALSE ? 'LIKE' : '=';
            $whSa = [];
            foreach ($searchable as $field) {
                $whSa[] = "{$field} {$sOperator} :search";
            }
            $queryBuilder->andWhere(implode(' OR ', $whSa));
        }

        $adapter = new DoctrineORMAdapter($queryBuilder);
        $entities = new Pagerfanta($adapter);
        $entities->setMaxPerPage(9);

        $cpage = $request->get('page');
        if ($cpage <= 0) {
            $cpage = 1;
        } elseif ($cpage > $entities->getNbPages()) {
            $cpage = $entities->getNbPages();
        }

        $entities->setCurrentPage($cpage);

        return $this->render('front/index.html.twig', [
            "categories" => $categories,
            'type'=> 'standard',
            'filterForm' => $filterForm->createView(),
            "entities" => $entities,
        ]);
    }


    public function checkout()
    {
        $categories = $this->getDoctrine()->getRepository(ShopCategory::class)->findBy([
            "parent" => null,
        ]);
        return $this->render('front/checkout.html.twig', [
            'categories' => $categories,
            'cart' => $this->getCartData()
        ]);
    }

    protected function listBuildQuery(QueryBuilder $queryBuilder, Request $request, ParameterBag $filterFormData): void
    {

    }

    protected function listFilterFormBuilder(array $formData, Request $request): FormBuilderInterface
    {
        $formBuilder = $this->createFormBuilderNamed('filter', $formData, [
            'translation_domain' => 'panel',
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
        $formBuilder->add('search', TextType::class, [
            'required' => false,
        ]);
        $formBuilder->add('confirm', SubmitType::class);
        return $formBuilder;
    }
}
