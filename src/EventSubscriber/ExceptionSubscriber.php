<?php

namespace App\EventSubscriber;

use App\Exceptions\InMaintenanceException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Templating\EngineInterface;
use Twig\Environment;

class ExceptionSubscriber implements EventSubscriberInterface
{
    private $router;
    private $twig;
    private $templating;

    public function __construct(RouterInterface $router, Environment $twig = null, EngineInterface $templating = null)
    {
        $this->router = $router;
        $this->twig = $twig;
        $this->templating = $templating;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if ($event->getException() instanceof MethodNotAllowedHttpException) {
            $event->setResponse(new Response("Bad request.", Response::HTTP_BAD_REQUEST));
        }
        if ($event->getException() instanceof NotFoundHttpException) {
            $event->setResponse($this->templateAction("exception/notFound.html.twig", [], Response::HTTP_NOT_FOUND));
        }
        if ($event->getException() instanceof AccessDeniedHttpException || $event->getException() instanceof  AccessDeniedException) {
            $event->setResponse($this->templateAction('exception/accessDenied.html.twig', ['message' => $event->getException()->getMessage()], Response::HTTP_FORBIDDEN));
        }
    }

    private function templateAction(string $template, array $params = [], $status = Response::HTTP_SERVICE_UNAVAILABLE): Response
    {
        if ($this->templating) {
            $response = new Response($this->templating->render($template, $params), $status);
        } elseif ($this->twig) {
            $response = new Response($this->twig->render($template, $params), $status);
        } else {
            throw new \LogicException('You can not use the TemplateController if the Templating Component or the Twig Bundle are not available.');
        }

        return $response;
    }

    public static function getSubscribedEvents()
    {
        return [
            'kernel.exception' => 'onKernelException',
        ];
    }
}
