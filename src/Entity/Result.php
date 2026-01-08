<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Result
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'float')]
    private float $scoreValue;

    #[ORM\Column(type: 'boolean')]
    private bool $passed;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $gradedAt;

    #[ORM\Column(type: 'integer')]
    private int $attemptNumber;

    #[ORM\Column(length: 255)]
    private string $remarks;

    #[ORM\ManyToOne(inversedBy: 'results')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'results')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Subject $subject = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getScoreValue(): float
    {
        return $this->scoreValue;
    }

    public function setScoreValue(float $scoreValue): self
    {
        $this->scoreValue = $scoreValue;
        return $this;
    }

    public function isPassed(): bool
    {
        return $this->passed;
    }

    public function setPassed(bool $passed): self
    {
        $this->passed = $passed;
        return $this;
    }

    public function getGradedAt(): \DateTimeInterface
    {
        return $this->gradedAt;
    }

    public function setGradedAt(\DateTimeInterface $gradedAt): self
    {
        $this->gradedAt = $gradedAt;
        return $this;
    }

    public function getAttemptNumber(): int
    {
        return $this->attemptNumber;
    }

    public function setAttemptNumber(int $attemptNumber): self
    {
        $this->attemptNumber = $attemptNumber;
        return $this;
    }

    public function getRemarks(): string
    {
        return $this->remarks;
    }

    public function setRemarks(string $remarks): self
    {
        $this->remarks = $remarks;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getSubject(): ?Subject
    {
        return $this->subject;
    }

    public function setSubject(?Subject $subject): self
    {
        $this->subject = $subject;
        return $this;
    }
}
