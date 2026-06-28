<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Modules\Toolkit\Controllers;

use Quantum\Database\Exceptions\DatabaseException;
use Modules\Toolkit\Services\DatabaseService;
use Quantum\Http\Response;
use Quantum\Http\Request;

/**
 * Class DatabaseController
 * @package Modules\Toolkit
 */
class DatabaseController extends BaseController
{
    private DatabaseService $databaseService;

    /**
     * Works before an action
     */
    public function __before()
    {
        $this->databaseService = service(DatabaseService::class);

        parent::__before();
    }

    public function list(): Response
    {
        $tables = $this->databaseService->getTables();

        $this->view->setParams([
            'title' => 'Database',
            'tables' => $tables,
        ]);

        return response()->html($this->view->render('pages/database/index'));
    }

    /**
     * @throws DatabaseException
     */
    public function single(Request $request): Response
    {
        $tableName = $request->get('table');
        $perPage = $request->get('per_page', self::ITEMS_PER_PAGE);
        $currentPage = $request->get('page', self::CURRENT_PAGE);

        $tableData = $this->databaseService->getTableData($tableName, $perPage, $currentPage);

        $this->view->setLayout('layouts/iframe');

        $this->view->setParams([
            'title' => 'Database',
            'tableName' => $tableName,
            'tableColumns' => $tableData['columns'],
            'tableData' => $tableData['data'],
            'pagination' => $tableData['pagination'],
        ]);

        return response()->html($this->view->render('pages/database/table'));
    }

    public function create(Request $request): Response
    {
        $tableName = $request->get('table');

        $newData = json_decode(htmlspecialchars_decode($request->get('data')), true);

        $this->databaseService->createTableRow($tableName, $newData);

        return redirect(get_referrer() ?? base_url());
    }

    public function update(Request $request): Response
    {
        $tableName = $request->get('table');
        $id = $request->get('rowId');
        $updatedData = json_decode(htmlspecialchars_decode($request->get('data')), true);

        $this->databaseService->updateTable($tableName, (int)$id, $updatedData);

        return redirect(base_url(true) . '/database/view?table=' . $tableName);
    }

    public function delete(Request $request): Response
    {
        $tableName = $request->get('tableName');
        $id = $request->get('id');

        $this->databaseService->deleteTableRow($tableName, $id);

        return redirect(base_url(true) . '/database/view?table=' . $tableName);
    }
}

