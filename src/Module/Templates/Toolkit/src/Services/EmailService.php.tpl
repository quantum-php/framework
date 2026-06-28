<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Modules\Toolkit\Services;

use Quantum\Paginator\Exceptions\PaginatorException;
use Quantum\Storage\Exceptions\FileSystemException;
use Quantum\Paginator\Factories\PaginatorFactory;
use Quantum\Paginator\Enums\PaginatorType;
use Quantum\App\Exceptions\BaseException;
use Quantum\Paginator\Paginator;
use Quantum\Service\Service;
use Quantum\Mailer\MailTrap;
use ReflectionException;
use Quantum\Di\Di;

/**
 * Class EmailService
 * @package Modules\Toolkit
 */
class EmailService extends Service
{
    private string $emailsDirectory;


    public function __construct()
    {
        $this->emailsDirectory = base_dir() . DS . 'shared' . DS . 'emails';
    }

    /**
     * @throws BaseException
     * @throws ReflectionException
     */
    public function getEmails(int $perPage, int $currentPage): Paginator
    {
        $mailTrap = Di::get(MailTrap::class);

        $emailFiles = fs()->listDirectory($this->emailsDirectory);

        $emails = [];

        foreach ($emailFiles as $emailFile) {
            try {
                $email = $mailTrap->parseMessage(fs()->fileName($emailFile));

                $emails[] = [
                    'id' => $this->getEmailId($email->getParsedMessageId()),
                    'subject' => $email->getParsedSubject(),
                    'recipient' => $email->getParsedToAddresses()[0] ?? null,
                    'timestamp' => $email->getParsedDate()
                ];


            } catch (FileSystemException $e) {
                continue;
            }
        }

        usort($emails, function ($a, $b) {
            return strtotime($b['timestamp']) <=> strtotime($a['timestamp']);
        });

        return $this->paginate($emails, $perPage, $currentPage);
    }

    /**
     * @throws BaseException
     */
    public function getEmail(string $emailId): MailTrap
    {
        $mailTrap = Di::get(MailTrap::class);

        return $mailTrap->parseMessage($emailId);
    }

    /**
     * @throws BaseException
     * @throws ReflectionException
     */
    public function deleteEmail(string $emailId): bool
    {
        return fs()->remove($this->emailsDirectory . DS . $emailId . '.eml');
    }
    private function getEmailId(string $messageId): string
    {
        preg_match('/<(.*?)@/', preg_quote($messageId), $matches);

        return $matches[1];
    }

    /**
     * @throws BaseException
     * @throws PaginatorException
     */
    private function paginate(array $data, int $perPage, int $currentPage): Paginator
    {
        return PaginatorFactory::create(PaginatorType::ARRAY, [
            "items" => $data,
            "perPage" => $perPage,
            "page" => $currentPage
        ]);
    }
}

