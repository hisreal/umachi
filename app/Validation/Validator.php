<?php

declare(strict_types=1);

namespace App\Validation;

class Validator
{
    private array $errors = [];

    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $fieldRules) {
            foreach ((array) $fieldRules as $rule) {
                if ($rule === 'required' && trim((string) ($data[$field] ?? '')) === '') {
                    $this->errors[$field][] = 'This field is required.';
                }
            }
        }

        return $this->errors === [];
    }

    public function errors(): array
    {
        return $this->errors;
    }
}