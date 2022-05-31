<?php

namespace App\Controller;

use App\Entity\Answer;
use App\Repository\AnswerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AnswerController extends BaseController {
	#[Route(path: '/answers/popular', name: 'app_popular_answers')]
	public function popularAnswers(AnswerRepository $answerRepository, Request $request): \Symfony\Component\HttpFoundation\Response {
		$answers = $answerRepository->findMostPopular(
			$request->get('q')
		);

		return $this->render('answer/popularAnswers.html.twig', [
			'answers' => $answers,
		]);
	}

	#[Route(path: '/answers/{id}/vote', methods: 'POST', name: 'answer_vote')]
	#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
	public function answerVote(Answer $answer, LoggerInterface $logger, Request $request, EntityManagerInterface $entityManager): \Symfony\Component\HttpFoundation\Response {
		$logger->info('{user} is voting on answer {answer}!', [
			'user' => $this->getUser()->getEmail(),
			'answer' => $answer->getId(),
		]);
		$data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
		$direction = $data['direction'] ?? 'up';
		// use real logic here to save this to the database
		if ($direction === 'up') {
			$logger->info('Voting up!');
			$answer->setVotes($answer->getVotes() + 1);
		} else {
			$logger->info('Voting down!');
			$answer->setVotes($answer->getVotes() - 1);
		}
		$entityManager->flush();

		return $this->json(['votes' => $answer->getVotes()]);
	}
}
