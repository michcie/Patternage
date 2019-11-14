<?php

namespace App\Repository;

use App\Entity\UserRemembermeToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Authentication\RememberMe\PersistentTokenInterface;
use Symfony\Component\Security\Core\Authentication\RememberMe\TokenProviderInterface;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;

class UserRemembermeTokenRepository extends ServiceEntityRepository implements TokenProviderInterface
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, UserRemembermeToken::class);
    }

    /**
     * {@inheritdoc}
     */
    public function loadTokenBySeries($series)
    {
        $t = $this->findOneBy(['series' => $series]);
        if ($t) {
            return $t;
        }
        throw new TokenNotFoundException('No token found.');
    }

    /**
     * {@inheritdoc}
     */
    public function deleteTokenBySeries($series)
    {
        $t = $this->findOneBy(['series' => $series]);
        if ($t) {
            $this->getEntityManager()->remove($t);
            $this->getEntityManager()->flush();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateToken($series, $tokenValue, \DateTime $lastUsed)
    {
        /** @var UserRemembermeToken $t */
        $t = $this->findOneBy(['series' => $series]);
        if (!$t) {
            throw new TokenNotFoundException('No token found.');
        }

        $t->setTokenValue($tokenValue);
        $t->setLastUsed($lastUsed);
        $t->setSeries($series);

        $this->getEntityManager()->persist($t);
        $this->getEntityManager()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function createNewToken(PersistentTokenInterface $token)
    {
        $t = new UserRemembermeToken();

        $t->setSeries($token->getSeries());
        $t->setLastUsed($token->getLastUsed());
        $t->setUsername($token->getUsername());
        $t->setClass($token->getClass());
        $t->setTokenValue($token->getTokenValue());

        $this->getEntityManager()->persist($t);
        $this->getEntityManager()->flush();
    }
}
