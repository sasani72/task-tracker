<?php

namespace App\Exception;

use Symfony\Component\Uid\Uuid;

class TaskNotFoundException extends \RuntimeException
{
    public function __construct(Uuid|string $id)
    {
        $idString = $id instanceof Uuid ? $id->toRfc4122() : $id;
        parent::__construct("Task with ID '{$idString}' not found");
    }
} 