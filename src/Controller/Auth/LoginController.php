<?php

namespace App\Controller\Auth;

use App\Entity\User;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends Controller
{
    public function login(AuthenticationUtils $authUtils, Request $request)
    {

        $targetPath = $request->get('_target_path');
        $ref = $request->getSession()->get('_security.main.target_path');
        $host = getenv('APP_DOMAIN');
        //check if request not contains target_path parameter and cookies contains auth_target_url parameter;
        if (!$targetPath && $request->cookies->has('_auth_target_url')) {
            //set url from cookies
            $url = $request->cookies->get('_auth_target_url');
        }// check redirect paths
        else {
            if ($targetPath) {
              //override targetPath
                $url = ($request->isSecure() ? "https://" : "http://") . $host . $targetPath;
            } elseif ($ref) {
                $url = $ref;
            } else {
                $path = "/";
                $url = ($request->isSecure() ? "https://" : "http://") . $host . $path;
            }
        }
        if ($this->getUser()) {
            //if user is authorized redirect without authorization
            /** @var User $user */
            $user = $this->getUser();
            $redirectResponse = $this->redirect($url);
            $redirectResponse->headers->clearCookie('_auth_target_url', '/',
                '.' . getenv('APP_DOMAIN'));
            return $redirectResponse;
        }

        // get the auth error if there is one
        $error = $authUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authUtils->getLastUsername();
        $response = $this->render('auth/login.html.twig', array(
            'last_username' => $lastUsername,
            'error' => $error,
        ));

        if ($targetPath || $ref) {
            //if we change path, set new cookies.
            $response->headers->setCookie(new Cookie('_auth_target_url', $url, time() + 300,
                '/', '.' . getenv('APP_DOMAIN')));
        }

        return $response;
    }


    public function loginOauth($type, ClientRegistry $clientRegistry, Request $request)
    {
        try {
            $client = $clientRegistry->getClient($type);
        } catch (\InvalidArgumentException $e) {
            throw new NotFoundHttpException();
        }

        $redirectResponse = $client->redirect([], [
            'redirect_uri' => "https://" . getenv('APP_DOMAIN') . $this->generateUrl('auth_login', ['type' => $type]),
        ]);

        $targetPath = $request->get('_target_path');
        if ($targetPath) {
            $url = ($request->isSecure() ? "https://" : "http://") . "shopdev:8000.pl/" . $targetPath;
            $redirectResponse->headers->setCookie(new Cookie('_auth_target_url', $url, 0, '/', '.' . getenv('APP_DOMAIN')));
        }

        return $redirectResponse;
    }

}
