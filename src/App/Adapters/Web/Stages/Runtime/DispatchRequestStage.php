<?php

declare(strict_types=1);

/**
 * Quantum PHP Framework
 * An open-source software development framework for PHP
 * @link https://quantumphp.io
 */

namespace Quantum\App\Adapters\Web\Stages\Runtime;

use Quantum\Middleware\Exceptions\MiddlewareException;
use Quantum\Config\Exceptions\ConfigException;
use Quantum\App\Contracts\StageInterface;
use Quantum\App\Exceptions\BaseException;
use Quantum\Middleware\MiddlewareManager;
use Quantum\App\Adapters\Web\WebContext;
use Quantum\Di\Exceptions\DiException;
use Quantum\Router\RouteDispatcher;
use Quantum\Router\MatchedRoute;
use Quantum\App\AppContext;
use Quantum\Http\Response;
use Quantum\Http\Request;
use ReflectionException;
use RuntimeException;

/**
 * Class DispatchRequestStage
 *
 * Produces the response of a matched request: the cached view if there is one, otherwise the
 * dispatched route, in both cases wrapped by the module and framework middlewares.
 *
 * @implements StageInterface<WebContext>
 * @package Quantum\App
 */
class DispatchRequestStage implements StageInterface
{
    public const BEFORE = 'web.dispatch.before';

    public const AFTER = 'web.dispatch.after';

    /**
     * @param WebContext $context
     * @throws MiddlewareException|BaseException|DiException|ReflectionException
     */
    public function process(AppContext $context): void
    {
        $matchedRoute = $context->getMatchedRoute();

        if ($matchedRoute === null) {
            throw new RuntimeException('Matched route is not set.');
        }

        $viewCache = $context->getViewCache();

        $terminal = function (Request $request) use ($viewCache, $matchedRoute): Response {
            $cached = $viewCache->getCachedResponse($request->getUri() ?? '');

            if ($cached !== null) {
                return $cached;
            }

            // The language segment is framework-owned: it is not passed to the action as a positional argument.
            // The language is read with current_lang(); an action argument named $lang still receives the segment,
            // deprecated, until 3.2.0.
            $languageParam = $this->languageSegmentParam($matchedRoute);

            return (new RouteDispatcher())->dispatch($matchedRoute, $request, $languageParam !== null ? [$languageParam => 'lang'] : []);
        };

        $context->setResponse((new MiddlewareManager($matchedRoute))->applyMiddlewares($context->getRequest(), $terminal));
    }

    /**
     * Gets the route param that holds the language URL segment (`lang.url_segment`), for example `_segment0`,
     * or null when the route has no param at that position. An invalid `lang.url_segment` also gives null, so the
     * route is dispatched as before; LangFactory still reports it when it reads the URL segment.
     * @throws DiException|ReflectionException
     */
    private function languageSegmentParam(MatchedRoute $matchedRoute): ?string
    {
        try {
            $param = '_segment' . (lang_url_segment() - 1);
        } catch (ConfigException) {
            return null;
        }

        return array_key_exists($param, $matchedRoute->getParams()) ? $param : null;
    }
}
