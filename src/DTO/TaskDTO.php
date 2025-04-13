<?php

namespace App\DTO;

use App\Enum\TaskStatus;
use Symfony\Component\Validator\Constraints as Assert;

final class TaskDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 255)]
        public readonly string $title,
        
        #[Assert\NotBlank]
        public readonly string $description,
        
        public readonly ?string $assigneeId = null,
        
        public readonly TaskStatus $status = TaskStatus::TODO
    ) {
    }
} 