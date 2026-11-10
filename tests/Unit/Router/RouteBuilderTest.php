<?php

namespace Quantum\Tests\Unit\Router;

use Quantum\Router\Exceptions\RouteException;
use Quantum\Router\RouteCollection;
use Quantum\Tests\Unit\AppTestCase;
use Quantum\Router\RouteBuilder;
use ReflectionClass;

class RouteBuilderTest extends AppTestCase
{
    public function testRouteBuilderBuildReturnsRouteCollection(): void
    {
        $builder = new RouteBuilder();

        $routes = $builder->build([
            'Web' => function (RouteBuilder $route): void {
                $route->get('users', 'UserController', 'index');
            },
        ], []);

        $this->assertInstanceOf(RouteCollection::class, $routes);

        $this->assertSame(1, $routes->count());
    }

    public function testRouteBuilderPutRegistersPutRoute(): void
    {
        $builder = new RouteBuilder();

        $routes = $builder->build([
            'Api' => function (RouteBuilder $route): void {
                $route->put('users', 'UserController', 'replace');
            },
        ], []);

        $route = $routes->all()[0];

        $this->assertSame(['PUT'], $route->getMethods());

        $this->assertSame('replace', $route->getAction());
    }

    public function testRouteBuilderDeleteRegistersDeleteRoute(): void
    {
        $builder = new RouteBuilder();

        $routes = $builder->build([
            'Api' => function (RouteBuilder $route): void {
                $route->delete('users', 'UserController', 'destroy');
            },
        ], []);

        $route = $routes->all()[0];

        $this->assertSame(['DELETE'], $route->getMethods());

        $this->assertSame('destroy', $route->getAction());
    }

    public function testRouteBuilderPatchRegistersPatchRouteWithControllerAction(): void
    {
        $builder = new RouteBuilder();

        $routes = $builder->build([
            'Api' => function (RouteBuilder $route): void {
                $route->patch('users', 'UserController', 'update');
            },
        ], []);

        $route = $routes->all()[0];

        $this->assertSame(['PATCH'], $route->getMethods());

        $this->assertStringEndsWith('UserController', (string) $route->getController());

        $this->assertSame('update', $route->getAction());
    }

    public function testRouteBuilderPatchRegistersPatchRouteWithClosure(): void
    {
        $builder = new RouteBuilder();

        $routes = $builder->build([
            'Api' => function (RouteBuilder $route): void {
                $route->patch('users', function (): void {
                });
            },
        ], []);

        $route = $routes->all()[0];

        $this->assertSame(['PATCH'], $route->getMethods());

        $this->assertTrue($route->isClosure());
    }

    public function testRouteBuilderBuildCollectsRoutesFromMultipleModules(): void
    {
        $builder = new RouteBuilder();

        $closures = [
            'Api' => function (RouteBuilder $route): void {
                $route->get('users', 'UsersController', 'index');
                $route->post('login', 'AuthController', 'login');
            },
            'Web' => function (RouteBuilder $route): void {
                $route->get('', 'HomeController', 'index');
            },
        ];

        $moduleConfigs = [
            'Api' => ['prefix' => 'api'],
            'Web' => ['prefix' => ''],
        ];

        $routes = $builder->build($closures, $moduleConfigs);

        $this->assertSame(3, $routes->count());

        $all = $routes->all();

        $this->assertSame('Api', $all[0]->getModule());

        $this->assertSame('/api/users', $all[0]->getPattern());

        $this->assertTrue($all[0]->allowsMethod('GET'));

        $this->assertSame('Api', $all[1]->getModule());

        $this->assertSame('/api/login', $all[1]->getPattern());

        $this->assertTrue($all[1]->allowsMethod('POST'));

        $this->assertSame('Web', $all[2]->getModule());

        $this->assertSame('/', $all[2]->getPattern());

        $this->assertTrue($all[2]->allowsMethod('GET'));
    }

    public function testRouteBuilderMissingModuleConfigFallsBackSafely(): void
    {
        $builder = new RouteBuilder();

        $routes = $builder->build(
            [
                'Test' => function (RouteBuilder $route): void {
                    $route->get('ping', 'PingController', 'index');
                },
            ],
            []
        );

        $this->assertSame(1, $routes->count());

        $route = $routes->all()[0];

        $this->assertSame('Test', $route->getModule());

        $this->assertSame('/ping', $route->getPattern());
    }

    public function testRouteBuilderPrefixIsAppliedToRoutes(): void
    {
        $builder = new RouteBuilder();

        $routes = $builder->build(
            [
                'Api' => function (RouteBuilder $route): void {
                    $route->get('status', 'StatusController', 'index');
                },
            ],
            [
                'Api' => ['prefix' => 'v1'],
            ]
        );

        $route = $routes->all()[0];

        $this->assertSame('/v1/status', $route->getPattern());

        $this->assertSame('Api', $route->getModule());
    }

    public function testRouteBuilderGroupAssignsGroupNameToRoutes(): void
    {
        $builder = new RouteBuilder();

        $routes = $builder->build(
            [
                'Web' => function (RouteBuilder $route): void {
                    $route->group('auth', function (RouteBuilder $route): void {
                        $route->get('dashboard', 'DashboardController', 'index');
                    });
                },
            ],
            []
        );

        $route = $routes->all()[0];

        $this->assertSame('auth', $route->getGroup());
    }

    public function testRouteBuilderNestedGroupsAreNotAllowed(): void
    {
        $this->expectException(RouteException::class);

        $builder = new RouteBuilder();

        $builder->build(
            [
                'Web' => function (RouteBuilder $route): void {
                    $route->group('one', function (RouteBuilder $route): void {
                        $route->group('two', function (): void {
                        });
                    });
                },
            ],
            []
        );
    }

    public function testRouteBuilderGroupMiddlewaresAreAppliedToRoutes(): void
    {
        $builder = new RouteBuilder();

        $routes = $builder->build(
            [
                'Web' => function (RouteBuilder $route): void {
                    $route->group('auth', function (RouteBuilder $route): void {
                        $route->get('profile', 'ProfileController', 'show');
                    })->middlewares(['Auth']);
                },
            ],
            []
        );

        $route = $routes->all()[0];

        $this->assertSame(['Auth'], $route->getMiddlewares());
    }

    public function testRouteBuilderRouteMiddlewaresPrependToGroupMiddlewares(): void
    {
        $builder = new RouteBuilder();

        $routes = $builder->build(
            [
                'Web' => function (RouteBuilder $route): void {
                    $route->group('auth', function (RouteBuilder $route): void {
                        $route
                            ->get('profile', 'ProfileController', 'show')
                            ->middlewares(['Editor']);
                    })->middlewares(['Auth']);
                },
            ],
            []
        );

        $route = $routes->all()[0];

        $this->assertSame(
            ['Auth', 'Editor'],
            $route->getMiddlewares()
        );
    }

    public function testRouteBuilderMiddlewaresMustBeCalledAfterRouteOrInsideGroup(): void
    {
        $this->expectException(RouteException::class);

        $builder = new RouteBuilder();

        $builder->build(
            [
                'Web' => function (RouteBuilder $route): void {
                    $route->middlewares(['Invalid']);
                },
            ],
            []
        );
    }

    public function testRouteBuilderMiddlewareOrderAcrossModulesGroupsAndStandaloneRoutes(): void
    {
        $builder = new RouteBuilder();

        $routes = $builder->build(
            [
                'Web' => function (RouteBuilder $route): void {
                    $route->group('auth', function (RouteBuilder $route): void {
                        $route->get('profile', 'ProfileController', 'show')->middlewares(['ProfileMw']);
                        $route->get('settings', 'SettingsController', 'show')->middlewares(['SettingsMw']);
                    })->middlewares(['AuthMw']);

                    $route->get('home', 'HomeController', 'index')->middlewares(['HomeMw']);
                },

                'Api' => function (RouteBuilder $route): void {
                    $route->group('api-auth', function (RouteBuilder $route): void {
                        $route->get('users', 'UsersController', 'index')->middlewares(['UsersMw1', 'UsersMw2']);
                        $route->post('login', 'AuthController', 'login')->middlewares(['LoginMw']);
                    })->middlewares(['ApiAuthMw']);

                    $route->get('status', 'StatusController', 'index')->middlewares(['StatusMw']);
                },
            ],
            []
        );

        $all = $routes->all();

        $this->assertSame(['AuthMw', 'ProfileMw'], $all[0]->getMiddlewares());

        $this->assertSame(['AuthMw', 'SettingsMw'], $all[1]->getMiddlewares());

        $this->assertSame(['HomeMw'], $all[2]->getMiddlewares());

        $this->assertSame(['ApiAuthMw', 'UsersMw1', 'UsersMw2'], $all[3]->getMiddlewares());

        $this->assertSame(['ApiAuthMw', 'LoginMw'], $all[4]->getMiddlewares());

        $this->assertSame(['StatusMw'], $all[5]->getMiddlewares());
    }

    public function testRouteBuilderRouteNameIsAssigned(): void
    {
        $builder = new RouteBuilder();

        $routes = $builder->build(
            [
                'Web' => function (RouteBuilder $route): void {
                    $route->get('home', 'HomeController', 'index')
                        ->name('home');
                },
            ],
            []
        );

        $this->assertSame('home', $routes->all()[0]->getName());
    }

    public function testRouteBuilderRouteNamesMustBeUnique(): void
    {
        $this->expectException(RouteException::class);

        $builder = new RouteBuilder();

        $builder->build(
            [
                'Web' => function (RouteBuilder $route): void {
                    $route->get('a', 'AController', 'a')->name('dup');
                    $route->get('b', 'BController', 'b')->name('dup');
                },
            ],
            []
        );
    }

    public function testRouteBuilderRouteNamesCanRepeatAcrossModules(): void
    {
        $builder = new RouteBuilder();

        $routes = $builder->build(
            [
                'Api' => function (RouteBuilder $route): void {
                    $route->get('posts', 'ApiPostController', 'index')->name('posts');
                },
                'Web' => function (RouteBuilder $route): void {
                    $route->get('posts', 'WebPostController', 'index')->name('posts');
                },
            ],
            [
                'Api' => ['prefix' => 'api'],
                'Web' => ['prefix' => ''],
            ]
        );

        $all = $routes->all();

        $this->assertSame('posts', $all[0]->getName());
        $this->assertSame('Api', $all[0]->getModule());

        $this->assertSame('posts', $all[1]->getName());
        $this->assertSame('Web', $all[1]->getModule());
    }

    public function testRouteBuilderNameMustBeCalledAfterRouteDefinition(): void
    {
        $this->expectException(RouteException::class);

        $builder = new RouteBuilder();

        $builder->build(
            [
                'Web' => function (RouteBuilder $route): void {
                    $route->name('invalid');
                },
            ],
            []
        );
    }

    public function testRouteBuilderCacheableAppliesToSingleRoute(): void
    {
        $builder = new RouteBuilder();

        $routes = $builder->build(
            [
                'Web' => function (RouteBuilder $route): void {
                    $route->get('page', 'PageController', 'show')
                        ->cacheable(true, 60);
                },
            ],
            []
        );

        $cache = $routes->all()[0]->getCache();

        $this->assertSame(true, $cache['enabled']);
        $this->assertSame(60, $cache['ttl']);
    }

    public function testRouteBuilderCacheableAppliesToGroupRoutes(): void
    {
        $builder = new RouteBuilder();

        $routes = $builder->build(
            [
                'Web' => function (RouteBuilder $route): void {
                    $route->group('cached', function (RouteBuilder $route): void {
                        $route->get('a', 'AController', 'a');
                        $route->get('b', 'BController', 'b');
                    })->cacheable(true, 120);
                },
            ],
            []
        );

        foreach ($routes->all() as $route) {
            $cache = $route->getCache();
            $this->assertSame(true, $cache['enabled']);
            $this->assertSame(120, $cache['ttl']);
        }
    }

    public function testRouteBuilderRateLimitAppliesToSingleRoute(): void
    {
        $builder = new RouteBuilder();

        $routes = $builder->build(
            [
                'Web' => function (RouteBuilder $route): void {
                    $route->get('api/posts', 'PostController', 'index')
                        ->rateLimit(100, 60);
                },
            ],
            []
        );

        $rateLimit = $routes->all()[0]->getRateLimit();

        $this->assertSame(100, $rateLimit['limit']);
        $this->assertSame(60, $rateLimit['interval']);
    }

    public function testRouteBuilderRateLimitAppliesToGroupRoutes(): void
    {
        $builder = new RouteBuilder();

        $routes = $builder->build(
            [
                'Web' => function (RouteBuilder $route): void {
                    $route->group('api', function (RouteBuilder $route): void {
                        $route->get('a', 'AController', 'a');
                        $route->get('b', 'BController', 'b');
                    })->rateLimit(50, 30);
                },
            ],
            []
        );

        foreach ($routes->all() as $item) {
            $rateLimit = $item->getRateLimit();
            $this->assertSame(50, $rateLimit['limit']);
            $this->assertSame(30, $rateLimit['interval']);
        }
    }

    public function testRouteBuilderRateLimitAppliedInsideActiveGroupCallback(): void
    {
        $builder = new RouteBuilder();

        $routes = $builder->build(
            [
                'Web' => function (RouteBuilder $route): void {
                    $route->group('api', function (RouteBuilder $route): void {
                        $route->get('a', 'AController', 'a');
                        $route->get('b', 'BController', 'b');
                        $route->rateLimit(25, 15);
                    });
                },
            ],
            []
        );

        foreach ($routes->all() as $item) {
            $rateLimit = $item->getRateLimit();
            $this->assertSame(25, $rateLimit['limit']);
            $this->assertSame(15, $rateLimit['interval']);
        }
    }

    public function testRouteBuilderAddRouteRequiresControllerAndAction(): void
    {
        $this->expectException(RouteException::class);

        $builder = new RouteBuilder();

        $builder->build(
            [
                'Web' => function (RouteBuilder $route): void {
                    $route->get('broken', 'OnlyController');
                },
            ],
            []
        );
    }

    public function testRouteBuilderResolvesShortControllerNameToFqcn(): void
    {
        $builder = new RouteBuilder();

        $routes = $builder->build(
            [
                'Test' => function (RouteBuilder $route): void {
                    $route->get('tests', 'TestController', 'tests');
                },
            ],
            []
        );

        $route = $routes->all()[0];

        $expected = \Quantum\Tests\_root\modules\Test\Controllers\TestController::class;

        $this->assertTrue(class_exists($expected), 'Expected test controller class to exist');

        $this->assertSame($expected, $route->getController());
    }

    public function testRouteBuilderDoesNotModifyExplicitFqcnController(): void
    {
        $builder = new RouteBuilder();

        $fqcn = \Quantum\Tests\_root\modules\Test\Controllers\TestController::class;

        $routes = $builder->build(
            [
                'Test' => function (RouteBuilder $route) use ($fqcn): void {
                    $route->get('tests', $fqcn, 'tests');
                },
            ],
            []
        );

        $route = $routes->all()[0];

        $this->assertTrue(class_exists($fqcn), 'Expected test controller class to exist');

        $this->assertSame($fqcn, $route->getController());
    }

    public function testRouteBuilderAddRouteRejectsEmptyMethods(): void
    {
        $this->expectException(RouteException::class);

        $builder = new RouteBuilder();
        $builder->build([
            'Web' => function (RouteBuilder $route): void {
                $route->add('test', '', 'TestController', 'index');
            },
        ], []);
    }

    public function testRouteBuilderShortControllerWithoutModuleThrows(): void
    {
        $this->expectException(RouteException::class);

        $builder = new RouteBuilder();

        $reflection = new ReflectionClass($builder);
        $method = $reflection->getMethod('addRoute');
        $method->setAccessible(true);

        $method->invoke(
            $builder,
            ['GET'],
            'test',
            'TestController',
            'index'
        );
    }

    public function testRouteBuilderMiddlewaresAppliedDirectlyToRoute(): void
    {
        $routes = (new RouteBuilder())->build([
            'Web' => function (RouteBuilder $route): void {
                $route->get('a', 'AController', 'a');
                $route->middlewares(['Direct']);
            },
        ], []);

        $this->assertSame(['Direct'], $routes->all()[0]->getMiddlewares());
    }

    public function testRouteBuilderCacheableMustFollowRouteOrGroup(): void
    {
        $this->expectException(RouteException::class);

        (new RouteBuilder())->build([
            'Web' => function (RouteBuilder $route): void {
                $route->cacheable(true);
            },
        ], []);
    }

    public function testRouteBuilderRateLimitMustFollowRouteOrGroup(): void
    {
        $this->expectException(RouteException::class);

        (new RouteBuilder())->build([
            'Web' => function (RouteBuilder $route): void {
                $route->rateLimit(100, 60);
            },
        ], []);
    }

    public function testRouteBuilderResolvePathTrimsSlashesCorrectly(): void
    {
        $routes = (new RouteBuilder())->build(
            [
                'Api' => function (RouteBuilder $route): void {
                    $route->get('/users/', 'UserController', 'index');
                },
            ],
            [
                'Api' => ['prefix' => '/v1/'],
            ]
        );

        $this->assertSame('/v1/users', $routes->all()[0]->getPattern());
    }

    public function testRouteBuilderClosureBasedRouteIsSupported(): void
    {
        $routes = (new RouteBuilder())->build([
            'Web' => function (RouteBuilder $route): void {
                $route->get('ping', fn (): string => 'pong');
            },
        ], []);

        $this->assertSame(1, $routes->count());
        $this->assertNull($routes->all()[0]->getController());
    }

    public function testRouteBuilderGroupedRoutesAreRegisteredIndividually(): void
    {
        $routes = (new RouteBuilder())->build([
            'Web' => function (RouteBuilder $route): void {
                $route->group('g', function (RouteBuilder $route): void {
                    $route->get('a', 'AController', 'a');
                    $route->get('b', 'BController', 'b');
                });
            },
        ], []);

        $this->assertCount(2, $routes->all());
        $this->assertSame('g', $routes->all()[0]->getGroup());
        $this->assertSame('g', $routes->all()[1]->getGroup());
    }

    public function testRouteBuilderPostGroupMiddlewaresThenRateLimitCanBeChained(): void
    {
        $routes = (new RouteBuilder())->build([
            'Web' => function (RouteBuilder $route): void {
                $route->group('g', function (RouteBuilder $route): void {
                    $route->get('a', 'AController', 'a');
                    $route->get('b', 'BController', 'b');
                })->middlewares(['Auth'])->rateLimit(10, 60);
            },
        ], []);

        foreach ($routes->all() as $route) {
            $this->assertSame(['Auth'], $route->getMiddlewares());
            $this->assertSame(['limit' => 10, 'interval' => 60], $route->getRateLimit());
        }
    }

    public function testRouteBuilderPostGroupRateLimitThenMiddlewaresCanBeChained(): void
    {
        $routes = (new RouteBuilder())->build([
            'Web' => function (RouteBuilder $route): void {
                $route->group('g', function (RouteBuilder $route): void {
                    $route->get('a', 'AController', 'a');
                    $route->get('b', 'BController', 'b');
                })->rateLimit(20, 30)->middlewares(['Auth']);
            },
        ], []);

        foreach ($routes->all() as $route) {
            $this->assertSame(['Auth'], $route->getMiddlewares());
            $this->assertSame(['limit' => 20, 'interval' => 30], $route->getRateLimit());
        }
    }

    public function testRouteBuilderPostGroupCacheableAndOtherModifiersCanBeChained(): void
    {
        $routes = (new RouteBuilder())->build([
            'Web' => function (RouteBuilder $route): void {
                $route->group('g', function (RouteBuilder $route): void {
                    $route->get('a', 'AController', 'a');
                    $route->get('b', 'BController', 'b');
                })->cacheable(true, 120)->rateLimit(50, 60)->middlewares(['Auth']);
            },
        ], []);

        foreach ($routes->all() as $route) {
            $cache = $route->getCache();
            $this->assertSame(true, $cache['enabled']);
            $this->assertSame(120, $cache['ttl']);
            $this->assertSame(['limit' => 50, 'interval' => 60], $route->getRateLimit());
            $this->assertSame(['Auth'], $route->getMiddlewares());
        }
    }
}
