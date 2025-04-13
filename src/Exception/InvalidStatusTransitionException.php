<?php

namespace App\Exception;

use App\Enum\TaskStatus;

class InvalidStatusTransitionException extends \RuntimeException
{
    public function __construct(TaskStatus $current, TaskStatus $new)
    {
        parent::__construct(
            sprintf(
                "Invalid status transition from '%s' to '%s'",
                $current->value,
                $new->value
            )
        );
    }
} 