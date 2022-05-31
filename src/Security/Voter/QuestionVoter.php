<?php

namespace App\Security\Voter;

use App\Entity\Question;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class QuestionVoter extends Voter {
	public function __construct(private Security $security) {
	}

	protected function supports(string $attribute, $subject): bool {
		// https://symfony.com/doc/current/security/voters.html
		return in_array($attribute, ['EDIT'])
			&& $subject instanceof \App\Entity\Question;
	}

	protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool {
		/** @var User $user */
		$user = $token->getUser();
		// if the user is anonymous, do not grant access
		if (!$user instanceof UserInterface) {
			return false;
		}

		if (!$subject instanceof Question) {
			throw new \Exception('Wrong type somehow passed');
		}

		if ($this->security->isGranted('ROLE_ADMIN')) {
			return true;
		}

		return match ($attribute) {
			'EDIT' => $user === $subject->getOwner(),
			default => false,
		};
	}
}
