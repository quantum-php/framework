<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Validation;

use Quantum\Config\Exceptions\ConfigException;
use Quantum\Lang\Exceptions\LangException;
use Quantum\Loader\Exceptions\LoaderException;
use Quantum\Validation\Traits\Resource;
use Quantum\Di\Exceptions\DiException;
use Quantum\Validation\Traits\General;
use Quantum\Validation\Traits\Length;
use Quantum\Validation\Traits\Lists;
use Quantum\Validation\Traits\Type;
use Quantum\Validation\Traits\File;
use BadMethodCallException;
use ReflectionException;
use RuntimeException;
use Closure;

/**
 * Class Validator
 * @package Quantum\Validation
 */
class Validator
{
    use General;
    use Type;
    use File;
    use Lists;
    use Length;
    use Resource;

    /**
     * Rules
     * @var array<string, array<string, mixed>>
     */
    private array $rules = [];

    /**
     * Validation Errors
     * @var array<string, array<string, mixed>>
     */
    private array $errors = [];

    /**
     * Request data
     * @var array<string, mixed>
     */
    private array $data = [];

    /**
     * Custom validation callbacks
     * @var Closure[]
     */
    private array $customRules = [];

    /**
     * Add rules for a single field
     * @param array<int, array<string, mixed>> $rules Format: [['ruleName' => param], ...]
     */
    public function setRule(string $field, array $rules): void
    {
        foreach ($rules as $rule) {
            $ruleName = (string) key($rule);
            $ruleParam = current($rule);
            $this->setOrUpdateRule($field, $ruleName, $ruleParam);
        }
    }

    /**
     * Add multiple rules for multiple fields
     * @param array<string, array<int, array<string, mixed>>> $rules Format: ['field' => [ ['rule' => param], ... ], ...]
     */
    public function setRules(array $rules): void
    {
        foreach ($rules as $field => $fieldRules) {
            $this->setRule($field, $fieldRules);
        }
    }

    /**
     * Update a single rule for a field if exists
     * @param array<string, mixed> $rule Format: ['ruleName' => param]
     */
    public function updateRule(string $field, array $rule): void
    {
        $ruleName = (string) key($rule);
        $ruleParam = current($rule);
        $this->setOrUpdateRule($field, $ruleName, $ruleParam);
    }

    /**
     * Delete a rule or all rules for a given field
     * @param string|null $rule Specific rule to delete; if null deletes all rules for field
     */
    public function deleteRule(string $field, ?string $rule = null): void
    {
        if (!isset($this->rules[$field])) {
            return;
        }

        if ($rule !== null) {
            unset($this->rules[$field][$rule]);

            if (empty($this->rules[$field])) {
                unset($this->rules[$field]);
            }
        } else {
            unset($this->rules[$field]);
        }
    }

    /**
     * Flush all rules and errors
     */
    public function flushRules(): void
    {
        $this->rules = [];
        $this->flushErrors();
    }

    /**
     * Validate given data against defined rules
     * @param array<string, mixed> $data
     * @return bool True if valid, false otherwise
     */
    public function isValid(array $data): bool
    {
        $this->data = $data;
        $this->flushErrors();

        if ($this->rules === []) {
            return true;
        }

        foreach ($this->rules as $field => $_) {
            if (!array_key_exists($field, $data)) {
                $data[$field] = '';
            }
        }

        foreach ($data as $field => $value) {
            if (!isset($this->rules[$field])) {
                continue;
            }

            foreach ($this->rules[$field] as $rule => $params) {
                $ruleParams = is_array($params) ? $params : [$params];

                if (method_exists($this, $rule)) {
                    $isValid = $this->$rule($value, ...$ruleParams);
                } elseif (isset($this->customRules[$rule])) {
                    $isValid = $this->executeCustomRule($rule, $value, ...$ruleParams);
                } else {
                    throw new BadMethodCallException("Validation rule '{$rule}' not found.");
                }

                if (!$isValid) {
                    $this->addError($field, $rule, ...$ruleParams);
                }
            }
        }

        return $this->errors === [];
    }

    /**
     * Add a custom validation rule
     * @param string $rule Rule name
     * @param Closure $function Callback function with signature function($value, $param): bool
     */
    public function addRule(string $rule, Closure $function): void
    {
        if ($rule === '' || $rule === '0') {
            return;
        }

        $this->customRules[$rule] = $function;
    }

    /**
     * Gets validation errors with translations
     * @return array<string, array<int, string|null>>
     * @throws ConfigException|LoaderException|LangException|DiException|ReflectionException
     */
    public function getErrors(): array
    {
        if ($this->errors === []) {
            return [];
        }

        $messages = [];

        foreach ($this->errors as $field => $fieldErrors) {
            foreach ($fieldErrors as $rule => $param) {
                $translationParams = [t('common.' . $field)];
                if ($param !== null && $param !== '') {
                    $translationParams[] = $param;
                }

                $messages[$field][] = t("validation.$rule", $translationParams);
            }
        }

        return $messages;
    }

    /**
     * Adds an error for a field and rule
     * @param mixed|null $param
     */
    protected function addError(string $field, string $rule, $param = null): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }

        $this->errors[$field][$rule] = $param;
    }

    /**
     * Flush all errors
     */
    public function flushErrors(): void
    {
        $this->errors = [];
    }

    /**
     * Executes user defined rule
     * @param mixed $value
     * @param mixed ...$params
     */
    protected function executeCustomRule(string $rule, $value, ...$params): bool
    {
        $function = $this->customRules[$rule];

        if (!is_callable($function)) {
            throw new RuntimeException("Validation rule '{$rule}' is not callable.");
        }

        return (bool) $function($value, ...$params);
    }

    /**
     * @param mixed $ruleParam
     */
    private function setOrUpdateRule(string $field, string $ruleName, $ruleParam): void
    {
        if (!isset($this->rules[$field])) {
            $this->rules[$field] = [];
        }

        $this->rules[$field][$ruleName] = $ruleParam;
    }
}
