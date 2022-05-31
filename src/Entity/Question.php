<?php

namespace App\Entity;

use App\Repository\AnswerRepository;
use App\Repository\QuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: QuestionRepository::class)]
class Question {
	use TimestampableEntity;

	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column()]
	private ?int $id = null;

	#[ORM\Column()]
	private ?string $name = null;
	
	#[Gedmo\Slug(fields: ['name'])]
	#[ORM\Column(length: 100, unique: true)]
	private ?string $slug = null;

	#[ORM\Column(type: 'text')]
	private ?string $question = null;

	#[ORM\Column(nullable: true)]
	private ?\DateTime $askedAt = null;

	#[ORM\Column(type: 'integer')]
	private int $votes = 0;

	#[ORM\OneToMany(targetEntity: Answer::class, mappedBy: 'question', fetch: 'EXTRA_LAZY')]
	#[ORM\OrderBy(['createdAt' => 'DESC'])]
	private Collection $answers;

	#[ORM\OneToMany(targetEntity: QuestionTag::class, mappedBy: 'question')]
	private Collection $questionTags;

	#[ORM\ManyToOne(inversedBy: 'questions')]
	#[ORM\JoinColumn(nullable: false)]
	private ?User $owner = null;

	public function __construct() {
		$this->answers = new ArrayCollection();
		$this->questionTags = new ArrayCollection();
	}

	public function getId(): ?int {
		return $this->id;
	}

	public function getName(): ?string {
		return $this->name;
	}

	public function setName(string $name): self {
		$this->name = $name;

		return $this;
	}

	public function getSlug(): ?string {
		return $this->slug;
	}

	public function setSlug(string $slug): self {
		$this->slug = $slug;

		return $this;
	}

	public function getQuestion(): ?string {
		return $this->question;
	}

	public function setQuestion(string $question): self {
		$this->question = $question;

		return $this;
	}

	public function getAskedAt(): ?\DateTimeInterface {
		return $this->askedAt;
	}

	public function setAskedAt(?\DateTimeInterface $askedAt): self {
		$this->askedAt = $askedAt;

		return $this;
	}

	public function getVotes(): int {
		return $this->votes;
	}

	public function getVotesString(): string {
		$prefix = $this->getVotes() >= 0 ? '+' : '-';

		return sprintf('%s %d', $prefix, abs($this->getVotes()));
	}

	public function setVotes(int $votes): self {
		$this->votes = $votes;

		return $this;
	}

	public function upVote(): self {
		++$this->votes;

		return $this;
	}

	public function downVote(): self {
		--$this->votes;

		return $this;
	}

	/**
	 * @return Collection|Answer[]
	 */
	public function getAnswers(): Collection {
		return $this->answers;
	}

	public function getApprovedAnswers(): Collection {
		return $this->answers->matching(AnswerRepository::createApprovedCriteria());
	}

	public function addAnswer(Answer $answer): self {
		if (!$this->answers->contains($answer)) {
			$this->answers[] = $answer;
			$answer->setQuestion($this);
		}

		return $this;
	}

	public function removeAnswer(Answer $answer): self {
		if ($this->answers->removeElement($answer)) {
			// set the owning side to null (unless already changed)
			if ($answer->getQuestion() === $this) {
				$answer->setQuestion(null);
			}
		}

		return $this;
	}

	/**
	 * @return Collection|QuestionTag[]
	 */
	public function getQuestionTags(): Collection {
		return $this->questionTags;
	}

	public function addQuestionTag(QuestionTag $questionTag): self {
		if (!$this->questionTags->contains($questionTag)) {
			$this->questionTags[] = $questionTag;
			$questionTag->setQuestion($this);
		}

		return $this;
	}

	public function removeQuestionTag(QuestionTag $questionTag): self {
		if ($this->questionTags->removeElement($questionTag)) {
			// set the owning side to null (unless already changed)
			if ($questionTag->getQuestion() === $this) {
				$questionTag->setQuestion(null);
			}
		}

		return $this;
	}

	public function getOwner(): ?User {
		return $this->owner;
	}

	public function setOwner(?User $owner): self {
		$this->owner = $owner;

		return $this;
	}
}
