<?php

namespace App\Security\Authenticator;

use App\Entity\User;
use Cocur\Slugify\SlugifyInterface;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\SocialAuthenticator;
use League\OAuth2\Client\Provider\FacebookUser;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class OauthConnectAuthenticator extends SocialAuthenticator
{
    use TargetPathTrait;

    private $clientRegistry;
    private $em;
    private $slugify;

    public function __construct(SlugifyInterface $slugify, ClientRegistry $clientRegistry, EntityManagerInterface $em)
    {
        $this->clientRegistry = $clientRegistry;
        $this->slugify = $slugify;
        $this->em = $em;
    }

    /**
     * Returns a response that directs the user to authenticate.
     *
     * This is called when an anonymous request accesses a resource that
     * requires authentication. The job of this method is to return some
     * response that "helps" the user start into the authentication process.
     *
     * Examples:
     *  A) For a form login, you might redirect to the login page
     *      return new RedirectResponse('/login');
     *  B) For an API token authentication system, you return a 401 response
     *      return new Response('Auth header required', 401);
     *
     * @param Request $request The request that resulted in an AuthenticationException
     * @param AuthenticationException $authException The exception that started the authentication process
     *
     * @return Response
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
    }

    /**
     * Does the authenticator support the given Request?
     *
     * If this returns false, the authenticator will be skipped.
     *
     * @param Request $request
     *
     * @return bool
     */
    public function supports(Request $request)
    {
        return $request->get('_route') == "auth_login" && in_array($request->get('type'), ['facebook', 'google']); // only check for auth_login with type
    }

    /**
     * Get the authentication credentials from the request and return them
     * as any type (e.g. an associate array).
     *
     * Whatever value you return here will be passed to getUser() and checkCredentials()
     *
     * For example, for a form login, you might:
     *
     *      return array(
     *          'username' => $request->request->get('_username'),
     *          'password' => $request->request->get('_password'),
     *      );
     *
     * Or for an API token that's on a header, you might use:
     *
     *      return array('api_key' => $request->headers->get('X-API-TOKEN'));
     *
     * @param Request $request
     *
     * @return mixed Any non-null value
     *
     * @throws \UnexpectedValueException If null is returned
     */
    public function getCredentials(Request $request)
    {
        $token = null;
        $exception = null;
        try {
            $client = $this->clientRegistry->getClient($request->get('type'));
            $token = $this->fetchAccessToken($client);

        } catch (\Exception $e) {
            $exception = $e;
            $this->saveAuthenticationErrorToSession($request, new AuthenticationException($e->getMessage()));
        }
        return [
            'type' => $request->get('type'),
            'token' => $token,
            'exception' => $exception,
        ];
    }

    public function bindOauthUserToUser($type, User $user, ResourceOwnerInterface $oauthUser)
    {
        $oauthuserdata = $this->readDataFromOauthUser($oauthUser);

        if ($type == 'facebook' && ($user->getFacebookId() != $oauthuserdata['id'] || $user->getFacebookName() != $oauthuserdata['name'] || $user->getFacebookAvatar() != $oauthuserdata['avatar'])) {
            $user->setFacebookId($oauthuserdata['id']);
            $user->setFacebookName($oauthuserdata['name']);
            $user->setFacebookAvatar($oauthuserdata['avatar']);
        }
        if ($type == 'google' && ($user->getGoogleId() != $oauthuserdata['id'] || $user->getGoogleName() != $oauthuserdata['name'] || $user->getGoogleAvatar() != $oauthuserdata['avatar'])) {
            $user->setGoogleId($oauthuserdata['id']);
            $user->setGoogleName($oauthuserdata['name']);
            $user->setGoogleAvatar($oauthuserdata['avatar']);
        }
        return $oauthuserdata;
    }

    /**
     * Return a UserInterface object based on the credentials.
     *
     * The *credentials* are the return value from getCredentials()
     *
     * You may throw an AuthenticationException if you wish. If you return
     * null, then a UsernameNotFoundException is thrown for you.
     *
     * @param mixed $credentials
     * @param UserProviderInterface $userProvider
     *
     * @throws AuthenticationException
     *
     * @return UserInterface|null
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if ($credentials['exception']) {
            return null;
        }

        $client = $this->clientRegistry->getClient($credentials['type']);
        $oauthuser = $client->fetchUserFromToken($credentials['token']);

        $oauthuserdata = $this->readDataFromOauthUser($oauthuser);

        // find user by email or oauth id

        /** @var User $user */
        $user = $this->em->getRepository(User::class)->createQueryBuilder('u')
            ->andWhere('u.email = :email OR u.' . $credentials['type'] . 'Id = :oauthid')
            ->setParameter(':email', $oauthuserdata['email'] ? $oauthuserdata['email'] : "jhr3qo48g73h98738")
            ->setParameter(':oauthid', $oauthuserdata['id'])
            ->getQuery()
            ->getOneOrNullResult();

        if ($user) {
            $this->bindOauthUserToUser($credentials['type'], $user, $oauthuser);
            $this->em->persist($user);
            $this->em->flush();
            return $user;
        }

        // register new user
        $user = new User();
        $this->bindOauthUserToUser($credentials['type'], $user, $oauthuser);
        $user->setEmail($oauthuserdata['email']);
        if (!$oauthuserdata['email']) {
            $user->setEmailConfirmed(false);
        }
        $user->setActive(true);
        $user->setUsername($this->generateUniqueUsername($oauthuserdata['name']));

        $this->em->persist($user);
        $this->em->flush();

//        $this->actionLogger->logUser($user, "register", [
//            'source' => $credentials['type'],
//        ]);

        return $user;
    }

    protected function generateUniqueUsername($name)
    {
        $base = $this->slugify->slugify($name);

        if (strlen($base) < 4) {
            $base = "user";
        }

        $i = 0;
        while (true) {
            $username = $base . ($i == 0 ? '' : $i);

            $user = $this->em->getRepository(User::class)->findOneBy([
                'username' => $username
            ]);

            if (!$user) {
                return $username;
            }

            $i++;
        }
    }

    protected function readDataFromOauthUser($oauthuser)
    {
        if ($oauthuser instanceof GoogleUser) {
            return [
                'email' => $oauthuser->getEmail(),
                'name' => $oauthuser->getFirstName() . ' ' . $oauthuser->getLastName(),
                'avatar' => $oauthuser->getAvatar(),
                'id' => $oauthuser->getId(),
            ];
        }
        if ($oauthuser instanceof FacebookUser) {
            return [
                'email' => $oauthuser->getEmail(),
                'name' => $oauthuser->getFirstName() . ' ' . $oauthuser->getLastName(),
                'avatar' => $oauthuser->getPictureUrl(),
                'id' => $oauthuser->getId(),
            ];
        }
        return null;
    }

    /**
     * Called when authentication executed, but failed (e.g. wrong username password).
     *
     * This should return the Response sent back to the user, like a
     * RedirectResponse to the login page or a 403 response.
     *
     * If you return null, the request will continue, but the user will
     * not be authenticated. This is probably not what you want to do.
     *
     * @param Request $request
     * @param AuthenticationException $exception
     *
     * @return Response|null
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return null;
    }

    /**
     * Called when authentication executed and was successful!
     *
     * This should return the Response sent back to the user, like a
     * RedirectResponse to the last page they visited.
     *
     * If you return null, the current request will continue, and the user
     * will be authenticated. This makes sense, for example, with an API.
     *
     * @param Request $request
     * @param TokenInterface $token
     * @param string $providerKey The provider (i.e. firewall) key
     *
     * @return Response|null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return null;
    }

    public function supportsRememberMe()
    {
        return false;
    }
}