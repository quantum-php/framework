<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Modules\Toolkit\Controllers;

use Quantum\App\Exceptions\BaseException;
use Quantum\Service\Exceptions\ServiceException;
use Modules\Toolkit\Services\EmailService;
use Quantum\Di\Exceptions\DiException;
use Quantum\Http\Response;
use Quantum\Http\Request;
use ReflectionException;

/**
 * Class EmailsController
 * @package Modules\Toolkit
 */
class EmailsController extends BaseController
{
    public EmailService $emailService;

    public function __before()
    {
        $this->emailService = service(EmailService::class);

        parent::__before();
    }

    public function list(Request $request): Response
    {
        $perPage = $request->get('per_page', self::ITEMS_PER_PAGE);
        $currentPage = $request->get('page', self::CURRENT_PAGE);

        $data = $this->emailService->getEmails($perPage, $currentPage);

        $this->view->setParams([
            'title' => 'Emails',
            'emails' => $data->data(),
            'pagination' => $data
        ]);

        return response()->html($this->view->render('pages/email/index'));
    }

    public function single(string $emailId): Response
    {
        $email = $this->emailService->getEmail($emailId);

        return response()->html(quoted_printable_decode($email->getParsedBody()));
    }

    public function delete(string $emailId): Response
    {
        $this->emailService->deleteEmail($emailId);

        return redirect(base_url(true) . '/emails');
    }
}

