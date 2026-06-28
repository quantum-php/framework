<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\Validation\Traits;

/**
 * Trait Resource
 * @package Quantum\Validation
 */
trait Resource
{
    /**
     * Checks for valid URL or subdomain
     */
    protected function url(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Checks to see if the url exists
     */
    protected function urlExists(string $value): bool
    {
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $host = parse_url(strtolower($value), PHP_URL_HOST);

        if (!$host) {
            return false;
        }

        if (function_exists('checkdnsrr') && function_exists('idn_to_ascii')) {
            $ascii = idn_to_ascii($host, 0, INTL_IDNA_VARIANT_UTS46);
            return $ascii !== false && checkdnsrr($ascii, 'A');
        } else {
            return gethostbyname($host) !== $host;
        }
    }

    /**
     * Checks for valid IP address
     */
    protected function ip(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Checks for valid IPv4 address
     */
    protected function ipv4(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * Checks for valid IPv6 address
     */
    protected function ipv6(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }
}
