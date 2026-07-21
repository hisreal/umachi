<?php
declare(strict_types=1);
namespace App\Core;
use RuntimeException;

class ValidationException extends RuntimeException
{
    public function __construct(string $message, private array $errors = []) { parent::__construct($message); }

    public function errors(): array { return $this->errors; }
}

