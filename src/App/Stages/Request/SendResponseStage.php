<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Stages\Request;

use Quantum\App\Contracts\RequestStageInterface;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\Di\Exceptions\DiException;
use Quantum\App\Adapters\Web\RequestContext;
use Quantum\Http\Response;
use Quantum\Config\Setup;
use ReflectionException;
use RuntimeException;
use Exception;

/**
 * Class SendResponseStage
 *
 * Sends the response of the request and always clears the request state afterwards,
 * even when sending fails. This stage never checks RequestContext::isStopped().
 *
 * @package Quantum\App
 */
class SendResponseStage implements RequestStageInterface
{
    /**
     * @throws ConfigException|DiException|ReflectionException|Exception
     */
    public function process(RequestContext $context): void
    {
        try {
            $response = $context->getResponse();

            if ($response === null) {
                throw new RuntimeException('The request has no response to send.');
            }

            $this->handleCors($response);
            $response->send();
        } finally {
            $this->cleanupRequestContext();
        }
    }

    /**
     * @throws ConfigException|DiException|ReflectionException
     */
    private function handleCors(Response $response): void
    {
        if (!config()->has('cors')) {
            config()->import(new Setup('config', 'cors'));
        }

        foreach (config()->get('cors') as $key => $value) {
            $response->setHeader($key, (string) $value);
        }
    }

    /**
     * Clears request-scoped state after the response has been sent.
     */
    private function cleanupRequestContext(): void
    {
        request()->setMatchedRoute(null);
        request()->flush();
    }
}
