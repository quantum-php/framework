<?php

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

const LEVEL_ATTRIBUTES = [
    'emergency' => [
        'class' => 'red-text text-darken-4',
        'icon' => 'report'
    ],
    'alert' => [
        'class' => 'red-text text-darken-3',
        'icon' => 'warning_amber'
    ],
    'critical' => [
        'class' => 'deep-orange-text text-darken-3',
        'icon' => 'priority_high'
    ],
    'error' => [
        'class' => 'red-text text-darken-2',
        'icon' => 'error'
    ],
    'warning' => [
        'class' => 'orange-text text-darken-2',
        'icon' => 'warning'
    ],
    'notice' => [
        'class' => 'yellow-text text-darken-2',
        'icon' => 'info'
    ],
    'info' => [
        'class' => 'blue-text text-lighten-1',
        'icon' => 'info'
    ],
    'debug' => [
        'class' => 'grey-text text-darken-1',
        'icon' => 'bug_report'
    ]
];

function getLevelIcon(string $level): string
{
    return LEVEL_ATTRIBUTES[strtolower($level)]['icon'];
}

function getLevelClass(string $level): string
{
    return LEVEL_ATTRIBUTES[strtolower($level)]['class'];
}

