<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class RegistrationController extends AbstractController {
	#[Route(path: '/register', name: 'app_register')]
	public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, VerifyEmailHelperInterface $verifyEmailHelper, EntityManagerInterface $entityManager, MailerInterface $mailer): Response {
		$user = new User();
		$form = $this->createForm(RegistrationFormType::class, $user);
		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			// encode the plain password
			$user->setPassword(
				$userPasswordHasher->hashPassword(
					$user,
					$form->get('plainPassword')->getData()
				)
			);

			$entityManager->persist($user);
			$entityManager->flush();

			$signatureComponents = $verifyEmailHelper->generateSignature(
				'app_verify_email',
				$user->getId(),
				$user->getEmail(),
				['id' => $user->getId()]
			);

			$signedUrl = $signatureComponents->getSignedUrl();
			$this->sendVerificationEmail($mailer, $user, $signedUrl);
			$this->addFlash('success', sprintf(
				'Confirm your email - The verification was sent to %s',
				$user->getEmail()
			));

			return $this->redirectToRoute('app_homepage');
		}

		return $this->renderForm('registration/register.html.twig', [
			'registrationForm' => $form,
		]);
	}

	#[Route(path: '/verify', name: 'app_verify_email')]
	public function verifyUserEmail(Request $request, VerifyEmailHelperInterface $verifyEmailHelper, UserRepository $userRepository, EntityManagerInterface $entityManager): Response {
		$user = $userRepository->find($request->query->get('id'));
		if (!$user) {
			throw $this->createNotFoundException();
		}
		try {
			$verifyEmailHelper->validateEmailConfirmation(
				$request->getUri(),
				$user->getId(),
				$user->getEmail(),
			);
		} catch (VerifyEmailExceptionInterface $e) {
			$this->addFlash('error', $e->getReason());

			return $this->redirectToRoute('app_register');
		}
		$user->setIsVerified(true);
		$entityManager->flush();
		$this->addFlash('success', 'Account Verified! You can now log in.');

		return $this->redirectToRoute('app_login');
	}

	#[Route(path: '/verify/resend', name: 'app_verify_resend_email')]
	public function resendVerifyEmail(): Response {
		return $this->render('registration/resend_verify_email.html.twig');
	}

	private function sendVerificationEmail(MailerInterface $mailer, User $user, string $signedUrl) {
		$email = (new Email())
			->from('hello@example.com')
			->to($user->getEmail())
			//->cc('cc@example.com')
			//->bcc('bcc@example.com')
			//->replyTo('fabien@example.com')
			//->priority(Email::PRIORITY_HIGH)
			->subject('Verify your email on Cauldron Overflow!')
			->text('Please, follow the link to verify your email!')
			->html(sprintf('<a href="%s">%s</a>', $signedUrl, $signedUrl));
		$mailer->send($email);
	}
}
