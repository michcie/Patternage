<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class Controller extends \Symfony\Bundle\FrameworkBundle\Controller\Controller
{
    /**
     * Creates and returns a Form instance from the type of the form.
     */
    protected function createFormNamed(string $name, string $type, $data = null, array $options = array()): FormInterface
    {
        return $this->container->get('form.factory')->createNamed($name, $type, $data, $options);
    }

    /**
     * Creates and returns a form builder instance.
     */
    protected function createFormBuilderNamed($name = "form", $data = null, array $options = array()): FormBuilderInterface
    {
        return $this->container->get('form.factory')->createNamedBuilder($name, FormType::class, $data, $options);
    }

    /**
     * Creates and returns a form builder instance.
     */
    protected function createFormBuilderTypeNamed($name = "form", $type = FormType::class, $data = null, array $options = array()): FormBuilderInterface
    {
        return $this->container->get('form.factory')->createNamedBuilder($name, $type, $data, $options);
    }

    /**
     * Creates and returns a form builder instance.
     */
    protected function createFormBuilderType($type = FormType::class, $data = null, array $options = array()): FormBuilderInterface
    {
        return $this->container->get('form.factory')->createBuilder($type, $data, $options);
    }

    protected function checkAuth()
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedException("Zalogowany");
        }

    }
    protected function checkGranted($attributes, $subject = null)
    {
        if (false === $this->isGranted($attributes, $subject)) {
            throw new AccessDeniedException($message = $attributes);
        }
    }

    public function redirectRewrite(Request $request, array $rewritedParams = [])
    {
        $route = $request->get('_route');
        $params = $request->get('_route_params') + $request->query->all();

        $a = array_filter($rewritedParams + $params, function ($val) {
            return $val !== null;
        });

        return $this->redirectToRoute($route, $a);
    }

    public function redirectToReferer(Request $request)
    {
        $referer = $request->headers->get('referer');
        return $this->redirect($referer);
    }
    public function responseAjaxSuccess(array $data)
    {
        return new JsonResponse(['error' => null] + $data, 200);
    }

    public function responseAjaxError($error, $message, $code = 500)
    {
        return new JsonResponse(['error' => $error, 'message' => $message], $code);
    }

}
