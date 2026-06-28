<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Validation\Traits;

use Quantum\Config\Exceptions\ConfigException;
use Quantum\Captcha\Factories\CaptchaFactory;
use Quantum\Model\Exceptions\ModelException;
use Quantum\App\Exceptions\BaseException;
use Quantum\Model\Factories\ModelFactory;
use Quantum\Di\Exceptions\DiException;
use Quantum\Model\DbModel;
use ReflectionException;
use DateTime;

/**
 * Trait General
 * @package Quantum\Validation
 */
trait General
{
    /**
     * Checks Field Required
     * @param mixed $value
     */
    protected function required($value): bool
    {
        return (string) $value !== '' && (string) $value !== '0';
    }

    /**
     * Checks Email
     * @param mixed $value
     */
    protected function email($value): bool
    {
        return filter_var((string) $value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Checks for a valid credit card number
     * @param mixed $value
     */
    protected function creditCard($value): bool
    {
        $value = (string) $value;
        $number = preg_replace('/\D/', '', $value) ?? '';
        $length = function_exists('mb_strlen') ? mb_strlen($number) : strlen($number);

        if ($length == 0) {
            return false;
        }

        $parity = $length % 2;
        $total = 0;

        for ($i = 0; $i < $length; ++$i) {
            $digit = (int) $number[$i];

            if ($i % 2 === $parity) {
                $digit *= 2;

                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $total += $digit;
        }

        return $total % 10 === 0;
    }

    /**
     * Checks for a valid IBAN
     * @param mixed $value
     */
    protected function iban($value): bool
    {
        $value = (string) $value;
        static $character = [
            'A' => 10, 'C' => 12, 'D' => 13, 'E' => 14, 'F' => 15, 'G' => 16,
            'H' => 17, 'I' => 18, 'J' => 19, 'K' => 20, 'L' => 21, 'M' => 22,
            'N' => 23, 'O' => 24, 'P' => 25, 'Q' => 26, 'R' => 27, 'S' => 28,
            'T' => 29, 'U' => 30, 'V' => 31, 'W' => 32, 'X' => 33, 'Y' => 34,
            'Z' => 35, 'B' => 11,
        ];

        if (!preg_match("/\A[A-Z]{2}\d{2} ?[A-Z\d]{4}( ?\d{4}){1,} ?\d{1,4}\z/", $value)) {
            return false;
        }

        $iban = str_replace(' ', '', $value);
        $iban = substr($iban, 4) . substr($iban, 0, 4);
        $iban = strtr($iban, $character);

        return bcmod($iban, '97') == 1;
    }

    /**
     * Checks for a valid format human name
     * @param mixed $value
     */
    protected function name($value): bool
    {
        return preg_match("/^([a-z \p{L} '-])+$/i", (string) $value) === 1;
    }

    /**
     * Checks that the provided string is a likely street address.
     * @param mixed $value
     */
    protected function streetAddress($value): bool
    {
        $value = (string) $value;
        $hasLetter = preg_match('/[a-zA-Z]/', $value);
        $hasDigit = preg_match('/\d/', $value);
        $hasSpace = preg_match('/\s/', $value);

        return $hasLetter && $hasDigit && $hasSpace;
    }

    /**
     * Validates the phone number // 555-555-5555 , 5555425555, 555 555 5555, 1(519) 555-4444, 1 (519) 555-4422, +1-555-555-5555
     * @param mixed $value
     */
    protected function phoneNumber($value): bool
    {
        $value = (string) $value;
        $regex = '/^(\+*\d[\s-]?)?[\(\[\s-]{0,2}?\d{3}[\)\]\s-]{0,2}?\d{3}[\s-]?\d{4}$/i';

        return preg_match($regex, $value) === 1;
    }

    /**
     * Determines if the provided input is a valid date
     * @param mixed $value
     */
    protected function date($value, ?string $format = null): bool
    {
        $value = (string) $value;
        if (!$format) {
            $timestamp = strtotime($value);

            if ($timestamp === false) {
                return false;
            }

            $cdate1 = date('Y-m-d', $timestamp);
            $cdate2 = date('Y-m-d H:i:s', $timestamp);

            return $cdate1 === $value || $cdate2 === $value;
        }

        $date = DateTime::createFromFormat($format, $value);

        return $date !== false && $value === $date->format($format);
    }

    /**
     * Ensures the value starts with a certain character / set of character
     * @param mixed $value
     */
    protected function starts($value, ?string $text = null): bool
    {
        return str_starts_with((string) $value, (string) $text);
    }

    /**
     * Custom regex validator
     * @param mixed $value
     */
    protected function regex($value, string $pattern): bool
    {
        return preg_match($pattern, (string) $value) === 1;
    }

    /**
     * Validates JSON string
     * @param mixed $value
     */
    protected function jsonString($value): bool
    {
        $value = htmlspecialchars_decode((string) $value, ENT_QUOTES);

        return is_object(json_decode($value));
    }

    /**
     * Validates same value for both fields
     * @param mixed $value
     */
    protected function same($value, string $otherField): bool
    {
        return $value == $this->data[$otherField];
    }

    /**
     * Validates uniqueness
     * @param mixed $value
     * @throws ModelException|BaseException|ReflectionException
     */
    protected function unique($value, string $className, string $columnName): bool
    {
        /** @var DbModel $model */
        $model = ModelFactory::get(ucfirst($className)); /** @phpstan-ignore argument.type, argument.templateType */

        $record = $model->findOneBy($columnName, $value);

        return $record === null || $record->isEmpty();
    }

    /**
     * Validates record existence
     * @param mixed $value
     * @throws ModelException|BaseException|ReflectionException
     */
    protected function exists($value, string $className, string $columnName): bool
    {
        /** @var DbModel $model */
        $model = ModelFactory::get(ucfirst($className)); /** @phpstan-ignore argument.type, argument.templateType */

        $record = $model->findOneBy($columnName, $value);

        return $record !== null && !$record->isEmpty();
    }

    /**
     * Check Captcha
     * @param mixed $value
     * @throws ConfigException|DiException|BaseException|ReflectionException
     */
    protected function captcha($value): bool
    {
        $captcha = CaptchaFactory::get();

        return $captcha->verify((string) $value);
    }
}
