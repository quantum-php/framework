<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Modules\Toolkit\Controllers;

use Quantum\View\Factories\ViewFactory;
use Quantum\Asset\Asset;
use Quantum\View\View;

/**
 * Class BaseController
 * @package Modules\Toolkit
 */
class BaseController
{
    /**
     * Main layout
     */
    protected const LAYOUT = 'layouts/main';

    /**
     * Items per page
     */
    protected const ITEMS_PER_PAGE = 20;

    /**
     * Current page
     */
    protected const CURRENT_PAGE = 1;

    protected View $view;

    public function __before()
    {
        $this->view = ViewFactory::get();

        $this->view->setLayout(static::LAYOUT, [
            new Asset(Asset::CSS, 'Toolkit/css/materialize.min.css', null, -1, ['media="screen,projection"']),
            new Asset(Asset::CSS, 'Toolkit/css/toolkit.css'),
            new Asset(Asset::JS, 'Toolkit/js/jquery-3.7.1.min.js'),
            new Asset(Asset::JS, 'Toolkit/js/materialize.min.js'),
            new Asset(Asset::JS, 'Toolkit/js/toolkit.js')
        ]);
    }
}

