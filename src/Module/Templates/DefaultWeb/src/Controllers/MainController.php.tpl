<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace {{MODULE_NAMESPACE}}\Controllers;

use Quantum\View\Factories\ViewFactory;
use Quantum\Http\Response;
use Quantum\Asset\Asset;

/**
 * Class MainController
 * @package Modules\Web
 */
class MainController
{

    /**
     * Main layout
     */
    const LAYOUT = 'layouts/main';

    /**
     * Works before an action
     */
    public function __before(ViewFactory $view)
    {
        $view->setLayout(static::LAYOUT, [
            new Asset(Asset::CSS, 'shared/css/materialize.min.css', null, -1, ['media="screen,projection"']),
            new Asset(Asset::CSS, '{{MODULE_NAME}}/css/custom.css')
        ]);
    }

    /**
     * Action - display home page
     */
    public function index(ViewFactory $view): Response
    {
        $view->setParams([
            'title' => config()->get('app.name'),
        ]);

        return response()->html($view->render('index'));
    }
}

