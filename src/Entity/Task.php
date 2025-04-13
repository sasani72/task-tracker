<?php

namespace App\Entity;

use App\Enum\TaskStatus;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

final class Task
{
    #[Groups(['task'])]
    public readonly Uuid $id;
    
    #[Groups(['task'])]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    public readonly string $title;
    
    #[Groups(['task'])]
    #[Assert\NotBlank]
    public readonly string $description;
    
    #[Groups(['task'])]
    public readonly TaskStatus $status;
    
    #[Groups(['task'])]
    public readonly ?string $assigneeId;
    
    #[Groups(['task'])]
    public readonly \DateTimeImmutable $createdAt;

    public function __construct(
        string $title,
        string $description,
        ?string $assigneeId = null,
        ?TaskStatus $status = null,
        ?Uuid $id = null
    ) {
        $this->id = $id ?? Uuid::v4();
        $this->title = $title;
        $this->description = $description;
        $this->status = $status ?? TaskStatus::TODO;
        $this->assigneeId = $assigneeId;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getStatus(): TaskStatus
    {
        return $this->status;
    }

    public function getAssigneeId(): ?string
    {
        return $this->assigneeId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function withStatus(TaskStatus $newStatus): self
    {
        return new self(
            $this->title,
            $this->description,
            $this->assigneeId,
            $newStatus,
            $this->id
        );
    }

    public function withAssignee(?string $assigneeId): self
    {
        return new self(
            $this->title,
            $this->description,
            $assigneeId,
            $this->status,
            $this->id
        );
    }
} 