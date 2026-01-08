<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ExamSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $sessionName;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $sessionDate;

    #[ORM\Column(type: 'boolean')]
    private bool $published;

    #[ORM\Column(type: 'integer')]
    private int $capacityLimit;

    #[ORM\ManyToMany(targetEntity: Subject::class, inversedBy: 'examSessions')]
    private Collection $subjects;

    public function __construct()
    {
        $this->subjects = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSessionName(): string
    {
        return $this->sessionName;
    }

    public function setSessionName(string $sessionName): self
    {
        $this->sessionName = $sessionName;
        return $this;
    }

    public function getSessionDate(): \DateTimeInterface
    {
        return $this->sessionDate;
    }

    public function setSessionDate(\DateTimeInterface $sessionDate): self
    {
        $this->sessionDate = $sessionDate;
        return $this;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): self
    {
        $this->published = $published;
        return $this;
    }

    public function getCapacityLimit(): int
    {
        return $this->capacityLimit;
    }

    public function setCapacityLimit(int $capacityLimit): self
    {
        $this->capacityLimit = $capacityLimit;
        return $this;
    }

    /**
     * @return Collection<int, Subject>
     */
    public function getSubjects(): Collection
    {
        return $this->subjects;
    }

    public function addSubject(Subject $subject): self
    {
        if (!$this->subjects->contains($subject)) {
            $this->subjects->add($subject);
            $subject->addExamSession($this);
        }
        return $this;
    }

    public function removeSubject(Subject $subject): self
    {
        if ($this->subjects->removeElement($subject)) {
            $subject->removeExamSession($this);
        }
        return $this;
    }
}
