<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Validation;

/**
 * Rule class
 * @package Quantum\Validation
 * @category Libraries
 * @method static bool required()
 * @method static bool email()
 * @method static bool creditCard()
 * @method static bool iban()
 * @method static bool name()
 * @method static bool streetAddress()
 * @method static bool phoneNumber()
 * @method static bool date(?string $format = null)
 * @method static bool starts(string $text)
 * @method static bool regex(string $pattern)
 * @method static bool jsonString()
 * @method static bool same(string $text)
 * @method static bool unique(string $className, string $columnName)
 * @method static bool exists(string $className, string $columnName)
 * @method static bool captcha()
 * @method static bool alpha()
 * @method static bool alphaNumeric()
 * @method static bool alphaDash()
 * @method static bool alphaSpace()
 * @method static bool numeric()
 * @method static bool integer()
 * @method static bool float()
 * @method static bool boolean()
 * @method static bool minNumeric(int $number)
 * @method static bool maxNumeric(int $number)
 * @method static bool fileSize(int $maxSize, ?int $minSize = null)
 * @method static bool fileMimeType(string ...$mimeTypes)
 * @method static bool fileExtension(string ...$extensions)
 * @method static bool imageDimensions(?int $width = null, ?int $height = null)
 * @method static bool contains(string $haystack)
 * @method static bool containsList(string ...$list)
 * @method static bool doesntContainsList(string ...$list)
 * @method static bool minLen(int $minLength)
 * @method static bool maxLen(int $maxLength)
 * @method static bool exactLen(int $exactLength)
 * @method static bool url()
 * @method static bool urlExists()
 * @method static bool ip()
 * @method static bool ipv4()
 * @method static bool ipv6()
 */
class Rule
{
    /**
     * @param array<int, mixed> $params
     * @return array<string, array<int, mixed>>
     */
    public static function __callStatic(string $name, array $params): array
    {
        return [$name => $params];
    }
}
