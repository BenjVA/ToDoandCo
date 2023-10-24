<?php

namespace App\Security;

use App\Entity\Task;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class TaskVoter extends Voter
{
    public const TASK_DELETE = 'task_delete';

    public function __construct(private Security $security)
    {
    }

    /**
     * @param string $attribute
     * @param        $subject
     *
     * @return bool
     */
    protected function supports(string $attribute, $subject): bool
    {
        if ($attribute !== self::TASK_DELETE) {
            return false;
        }

        if (!$subject instanceof Task) {
            return false;
        }

        return true;
    }

    /**
     * @param string         $attribute
     * @param                $subject
     * @param TokenInterface $token
     *
     * @return bool
     */
    protected function voteOnAttribute(
        string $attribute,
        $subject,
        TokenInterface $token
    ): bool {
        $user = $token->getUser();

        if (!$user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }

        if ($attribute === self::TASK_DELETE) {
            // User can delete their own tasks
            if ($user === $subject->getUser()) {
                return true;
            }
            // Admin Users can delete tasks that are attributed to the "Anonymous" User
            if (
                $this->security->isGranted('ROLE_ADMIN')
                && $subject->getUser()->getUsername() === 'anon'
            ) {
                return true;
            }

            return false;
        }

        throw new \RuntimeException(sprintf('Unhandled attribute "%s"', $attribute));
    }
}
