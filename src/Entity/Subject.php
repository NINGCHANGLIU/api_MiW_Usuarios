<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

#[ORM\Entity]
class Subject
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $subjectCode;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(type: 'integer')]
    private int $credits;

    #[ORM\Column(type: 'boolean')]
    private bool $active;

    #[ORM\OneToMany(mappedBy: 'subject', targetEntity: Result::class, orphanRemoval: true)]
    #[Serializer\Exclude]
    private Collection $results;

    #[ORM\ManyToMany(mappedBy: 'subjects', targetEntity: ExamSession::class)]
    #[Serializer\Exclude]
    private Collection $examSessions;

    public function __construct()
    {
        $this->results = new ArrayCollection();
        $this->examSessions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubjectCode(): string
    {
        return $this->subjectCode;
    }

    public function setSubjectCode(string $subjectCode): self
    {
        $this->subjectCode = $subjectCode;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getCredits(): int
    {
        return $this->credits;
    }

    public function setCredits(int $credits): self
    {
        $this->credits = $credits;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @return Collection<int, Result>
     */
    public function getResults(): Collection
    {
        return $this->results;
    }

    public function addResult(Result $result): self
    {
        if (!$this->results->contains($result)) {
            $this->results->add($result);
            $result->setSubject($this);
        }
        return $this;
    }

    public function removeResult(Result $result): self
    {
        if ($this->results->removeElement($result)) {
            if ($result->getSubject() === $this) {
                $result->setSubject(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, ExamSession>
     */
    public function getExamSessions(): Collection
    {
        return $this->examSessions;
    }

    public function addExamSession(ExamSession $examSession): self
    {
        if (!$this->examSessions->contains($examSession)) {
            $this->examSessions->add($examSession);
        }
        return $this;
    }

    public function removeExamSession(ExamSession $examSession): self
    {
        $this->examSessions->removeElement($examSession);
        return $this;
    }
}
