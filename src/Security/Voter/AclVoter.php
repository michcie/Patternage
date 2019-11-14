<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Entity\UserNetworkPermission;
use App\Service\NetworkResolver;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AclVoter extends Voter
{
    /** @var AccessDecisionManagerInterface */
    private $decisionManager;

    public function __construct(AccessDecisionManagerInterface $decisionManager)
    {
        $this->decisionManager = $decisionManager;
    }

    protected function supports($attribute, $subject)
    {
        $expl = explode('_', $attribute);
        return count($expl) > 0 && strtolower($expl[0]) != "role";
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        // ROLE_SUPER_ADMIN can do everything
        if ($this->decisionManager->decide($token, ["ROLE_SUPER_ADMIN"])) {
            return true;
        }
        return false;
    }
}
