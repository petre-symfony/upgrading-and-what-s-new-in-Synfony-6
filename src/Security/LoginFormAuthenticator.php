<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Guard\PasswordAuthenticatedInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator {
	use TargetPathTrait;

	public const LOGIN_ROUTE = 'app_login';

	public function __construct(private SessionInterface $session, private EntityManagerInterface $entityManager, private UrlGeneratorInterface $urlGenerator, private CsrfTokenManagerInterface $csrfTokenManager, private UserPasswordHasherInterface $passwordHasher) {
	}

	public function authenticate(Request $request): Passport {
		$email = $request->request->get('email');
		$password = $request->request->get('password');

		return new Passport(
			new UserBadge($email)
		);
	}

	public function getCredentials(Request $request) {
		$credentials = [
			'email' => $request->request->get('email'),
			'password' => $request->request->get('password'),
			'csrf_token' => $request->request->get('_csrf_token'),
		];
		$this->session->set(
			Security::LAST_USERNAME,
			$credentials['email']
		);

		return $credentials;
	}

	public function getUser($credentials, UserProviderInterface $userProvider) {
		$token = new CsrfToken('authenticate', $credentials['csrf_token']);
		if (!$this->csrfTokenManager->isTokenValid($token)) {
			throw new InvalidCsrfTokenException();
		}

		$user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $credentials['email']]);

		if (!$user) {
			throw new UserNotFoundException('Email could not be found.');
		}

		return $user;
	}

	public function checkCredentials($credentials, UserInterface $user) {
		return $this->passwordHasher->isPasswordValid($user, $credentials['password']);
	}

	/**
	 * Used to upgrade (rehash) the user's password automatically over time.
	 */
	public function getPassword($credentials): ?string {
		return $credentials['password'];
	}

	public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewalName): ?Response {
		if ($targetPath = $this->getTargetPath($this->session, $firewalName)) {
			return new RedirectResponse($targetPath);
		}

		return new RedirectResponse($this->urlGenerator->generate('app_homepage'));
	}

	public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response {
		if ($exception instanceof AccountNotVerifiedAuthenticationException) {
			$targetUrl = $this->urlGenerator->generate('app_verify_resend_email');

			return new RedirectResponse($targetUrl);
		}

		return parent::onAuthenticationFailure($request, $exception);
	}

	protected function getLoginUrl(Request $request): string {
		return $this->urlGenerator->generate(self::LOGIN_ROUTE);
	}
}
