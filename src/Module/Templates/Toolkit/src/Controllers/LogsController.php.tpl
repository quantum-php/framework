<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Modules\Toolkit\Controllers;

use Quantum\Service\Exceptions\ServiceException;
use Quantum\App\Exceptions\BaseException;
use Modules\Toolkit\Services\LogsService;
use Quantum\Di\Exceptions\DiException;
use Quantum\Http\Response;
use Quantum\Http\Request;
use ReflectionException;

/**
 * Class LogsController
 * @package Modules\Toolkit
 */
class LogsController extends BaseController
{
    public LogsService $logsService;

    public function __before()
    {
        $this->logsService = service(LogsService::class);

        parent::__before();
    }

    public function list(): Response
    {
        $filteredLogFiles = $this->logsService->getLogFiles();

        $this->view->setParams([
            'title' => 'Logs',
            'logFiles' => $filteredLogFiles,
        ]);

        return response()->html($this->view->render('pages/logs/index'));
    }

    public function single(Request $request): Response
    {
        $logFile = $request->get('logFile');
        $perPage = $request->get('per_page', self::ITEMS_PER_PAGE);
        $currentPage = $request->get('page', self::CURRENT_PAGE);

        $parsedLogs = $this->logsService->getLogEntries($logFile, $perPage, $currentPage);

        $this->view->setLayout('layouts/iframe');

        $this->view->setParams([
            'title' => 'Logs',
            'logData' => $parsedLogs->data(),
            'pagination' => $parsedLogs,
        ]);

        return response()->html($this->view->render('pages/logs/log'));
    }
}

