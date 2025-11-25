<?php

namespace LightLogger\Validator;

/**
 * Validates log entries against project schema.
 */
class LogValidator
{
    private array $errors = [];

    /**
     * Validate log data against core requirements and project schema.
     */
    public function validate(array $logData, ?array $schema): bool
    {
        $this->errors = [];

        // Validate core fields
        $this->validateCoreFields($logData);

        // If schema is defined, validate data field
        if ($schema !== null && !empty($schema['fields'])) {
            $this->validateDataField($logData, $schema);
        }

        return empty($this->errors);
    }

    /**
     * Get validation errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Validate core required fields.
     */
    private function validateCoreFields(array $logData): void
    {
        // Check timestamp
        if (!isset($logData['timestamp'])) {
            $this->errors[] = ['field' => 'timestamp', 'error' => 'Timestamp is required'];
        } elseif (!$this->isValidTimestamp($logData['timestamp'])) {
            $this->errors[] = ['field' => 'timestamp', 'error' => 'Invalid timestamp format. Use ISO 8601 format'];
        }

        // Check level
        if (!isset($logData['level'])) {
            $this->errors[] = ['field' => 'level', 'error' => 'Level is required'];
        } elseif (!$this->isValidLevel($logData['level'])) {
            $this->errors[] = [
                'field' => 'level',
                'error' => 'Invalid level. Allowed values: debug, info, warning, error, critical',
            ];
        }

        // Check title
        if (!isset($logData['title'])) {
            $this->errors[] = ['field' => 'title', 'error' => 'Title is required'];
        } elseif (!is_string($logData['title']) || trim($logData['title']) === '') {
            $this->errors[] = ['field' => 'title', 'error' => 'Title must be a non-empty string'];
        }
    }

    /**
     * Validate data field against schema.
     */
    private function validateDataField(array $logData, array $schema): void
    {
        if (!isset($logData['data'])) {
            // Check if any required fields exist
            $hasRequired = false;
            foreach ($schema['fields'] as $fieldDef) {
                if (!empty($fieldDef['required'])) {
                    $hasRequired = true;
                    break;
                }
            }

            if ($hasRequired) {
                $this->errors[] = ['field' => 'data', 'error' => 'Data field is required when schema has required fields'];
            }
            return;
        }

        $data = $logData['data'];

        if (!is_array($data)) {
            $this->errors[] = ['field' => 'data', 'error' => 'Data must be an object'];
            return;
        }

        // Validate each field in schema
        foreach ($schema['fields'] as $fieldDef) {
            $fieldName = $fieldDef['name'];
            $fieldExists = array_key_exists($fieldName, $data);

            // Check required
            if (!empty($fieldDef['required']) && !$fieldExists) {
                $this->errors[] = [
                    'field' => "data.{$fieldName}",
                    'error' => "Field '{$fieldName}' is required",
                ];
                continue;
            }

            // If field doesn't exist and not required, skip validation
            if (!$fieldExists) {
                continue;
            }

            $value = $data[$fieldName];

            // Validate type
            $this->validateFieldType($fieldName, $value, $fieldDef['type']);

            // Validate rules if present
            if (isset($fieldDef['validation']) && !empty($fieldDef['validation'])) {
                $this->validateFieldRules($fieldName, $value, $fieldDef['type'], $fieldDef['validation']);
            }
        }
    }

    /**
     * Validate field type.
     */
    private function validateFieldType(string $fieldName, $value, string $expectedType): void
    {
        $valid = match ($expectedType) {
            'string' => is_string($value),
            'number' => is_int($value) || is_float($value),
            'boolean' => is_bool($value),
            'array' => is_array($value) && array_is_list($value),
            'object' => is_array($value) && !array_is_list($value),
            'datetime' => is_string($value) && $this->isValidTimestamp($value),
            default => false,
        };

        if (!$valid) {
            $actualType = $this->getActualType($value);
            $this->errors[] = [
                'field' => "data.{$fieldName}",
                'error' => "Expected type '{$expectedType}', got '{$actualType}'",
            ];
        }
    }

    /**
     * Validate field validation rules.
     */
    private function validateFieldRules(string $fieldName, $value, string $type, array $rules): void
    {
        // String validations
        if ($type === 'string' && is_string($value)) {
            if (isset($rules['min_length']) && strlen($value) < $rules['min_length']) {
                $this->errors[] = [
                    'field' => "data.{$fieldName}",
                    'error' => "Minimum length is {$rules['min_length']} characters",
                ];
            }

            if (isset($rules['max_length']) && strlen($value) > $rules['max_length']) {
                $this->errors[] = [
                    'field' => "data.{$fieldName}",
                    'error' => "Maximum length is {$rules['max_length']} characters",
                ];
            }

            if (isset($rules['pattern']) && !preg_match('/' . $rules['pattern'] . '/', $value)) {
                $this->errors[] = [
                    'field' => "data.{$fieldName}",
                    'error' => "Value does not match required pattern",
                ];
            }

            if (isset($rules['enum']) && is_array($rules['enum']) && !in_array($value, $rules['enum'], true)) {
                $allowed = implode(', ', $rules['enum']);
                $this->errors[] = [
                    'field' => "data.{$fieldName}",
                    'error' => "Value must be one of: {$allowed}",
                ];
            }
        }

        // Number validations
        if ($type === 'number' && (is_int($value) || is_float($value))) {
            if (isset($rules['min']) && $value < $rules['min']) {
                $this->errors[] = [
                    'field' => "data.{$fieldName}",
                    'error' => "Minimum value is {$rules['min']}",
                ];
            }

            if (isset($rules['max']) && $value > $rules['max']) {
                $this->errors[] = [
                    'field' => "data.{$fieldName}",
                    'error' => "Maximum value is {$rules['max']}",
                ];
            }
        }
    }

    /**
     * Check if timestamp is valid ISO 8601 format.
     */
    private function isValidTimestamp(string $timestamp): bool
    {
        $dt = \DateTime::createFromFormat(\DateTime::ATOM, $timestamp);
        return $dt !== false;
    }

    /**
     * Check if level is valid.
     */
    private function isValidLevel(string $level): bool
    {
        return in_array($level, ['debug', 'info', 'warning', 'error', 'critical'], true);
    }

    /**
     * Get actual type of value for error messages.
     */
    private function getActualType($value): string
    {
        if (is_array($value)) {
            return array_is_list($value) ? 'array' : 'object';
        }
        return gettype($value);
    }
}
