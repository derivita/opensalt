<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ManageAdditionalFieldVoter extends Voter
{
    use RoleCheckTrait;

    public const MANAGE = 'manage';

    public const ADDITIONAL_FIELDS = 'additional_fields';

    /**
     * {@inheritdoc}
     */
    protected function supports(string $attribute, $subject): bool
    {
        return (self::MANAGE === $attribute) && (self::ADDITIONAL_FIELDS === $subject);
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        return $this->roleChecker->isSuperUser($token);
    }
}
