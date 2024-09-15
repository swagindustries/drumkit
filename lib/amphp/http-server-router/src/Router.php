<?php declare(strict_types=1);

namespace Amp\Http\Server;

use Amp\Cache\LocalCache;
use Amp\ForbidCloning;
use Amp\ForbidSerialization;
use Amp\Http\HttpStatus;
use Amp\Http\Server\RequestHandler\ClosureRequestHandler;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Psr\Log\LoggerInterface;
use function FastRoute\simpleDispatcher;

final class Router implements RequestHandler
{
    use ForbidCloning;
    use ForbidSerialization;

    private const DEFAULT_CACHE_SIZE = 512;

    private bool $running = false;

    private ?Dispatcher $routeDispatcher = null;

    private ?RequestHandler $fallback = null;

    /** @var list<array{string, string, RequestHandler}> */
    private array $routes = [];

    /** @var list<Middleware> */
    private array $middlewares = [];

    private string $prefix = "/";

    /** @var LocalCache<array{int, RequestHandler, array<string, string>}> */
    private readonly LocalCache $cache;

    /**
     * @param positive-int $cacheSize Maximum number of route matches to cache.
     */
    public function __construct(
        HttpServer $httpServer,
        private readonly LoggerInterface $logger,
        private readonly ErrorHandler $errorHandler,
        int $cacheSize = self::DEFAULT_CACHE_SIZE,
    ) {
        $httpServer->onStart($this->onStart(...));
        $httpServer->onStop($this->onStop(...));

        /** @psalm-suppress DocblockTypeContradiction */
        if ($cacheSize <= 0) {
            throw new \ValueError("The number of cache entries must be greater than zero");
        }

        $this->cache = new LocalCache($cacheSize);
    }

    /**
     * Route a request and dispatch it to the appropriate handler.
     */
    public function handleRequest(Request $request): Response
    {
        if (!$this->running) {
            throw new \Error('HTTP server has not been started so the router has not been built');
        }

        if (!$this->routeDispatcher) {
            if ($this->fallback !== null) {
                return $this->fallback->handleRequest($request);
            }

            return $this->notFound($request);
        }

        $method = $request->getMethod();
        $path = $request->getUri()->getPath();

        $toMatch = "{$method}\0{$path}";

        if (null === $match = $this->cache->get($toMatch)) {
            $match = $this->routeDispatcher->dispatch($method, $path);
            $this->cache->set($toMatch, $match);
        }

        switch ($match[0]) {
            case Dispatcher::FOUND:
                /**
                 * @var RequestHandler $requestHandler
                 * @var array<string, string> $routeArgs
                 */
                [, $requestHandler, $routeArgs] = $match;
                $request->setAttribute(self::class, \array_map(\rawurldecode(...), $routeArgs));

                return $requestHandler->handleRequest($request);

            case Dispatcher::NOT_FOUND:
                if ($this->fallback !== null) {
                    return $this->fallback->handleRequest($request);
                }

                return $this->notFound($request);

            case Dispatcher::METHOD_NOT_ALLOWED:
                return $this->methodNotAllowed($match[1], $request);

            default:
                // @codeCoverageIgnoreStart
                throw new \UnexpectedValueException(
                    "Encountered unexpected dispatcher code: " . $match[0]
                );
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Merge another router's routes into this router.
     *
     * @param self $router Router to merge.
     */
    public function merge(self $router): void
    {
        if ($this->running) {
            throw new \Error("Cannot merge routers after the server has started");
        }

        foreach ($router->routes as [$method, $path, $requestHandler]) {
            $path = \ltrim($router->prefix, "/") . $path;
            $requestHandler = Middleware\stackMiddleware($requestHandler, ...$router->middlewares);
            $this->routes[] = [$method, $path, $requestHandler];
        }
    }

    /**
     * Prefix all currently defined routes with a given prefix.
     *
     * If this method is called multiple times, the second prefix will be before the first prefix and so on.
     *
     * @param string $prefix Path segment to prefix, leading and trailing slashes will be normalized.
     */
    public function prefix(string $prefix): void
    {
        if ($this->running) {
            throw new \Error("Cannot alter routes after the server has started");
        }

        $prefix = \trim($prefix, "/");

        if ($prefix !== "") {
            $this->prefix = "/" . $prefix . $this->prefix;
        }
    }

    /**
     * Define an application route.
     *
     * Matched URI route arguments are made available to request handlers as a request attribute
     * which may be accessed with:
     *
     *     $request->getAttribute(Router::class)
     *
     * Route URIs ending in "/?" (without the quotes) allow a URI match with or without
     * the trailing slash. Temporary redirects are used to redirect to the canonical URI
     * (with a trailing slash) to avoid search engine duplicate content penalties.
     *
     * @param string $method The HTTP method verb for which this route applies.
     * @param string $uri The string URI.
     * @param RequestHandler $requestHandler Request handler invoked on a route match.
     *
     * @throws \Error If the server has started, or if $method is empty.
     */
    public function addRoute(
        string $method,
        string $uri,
        RequestHandler $requestHandler
    ): void {
        if ($this->running) {
            throw new \Error(
                "Cannot add routes once the server has started"
            );
        }

        if ($method === "") {
            throw new \Error(
                __METHOD__ . "() requires a non-empty string HTTP method at Argument 1"
            );
        }

        $this->routes[] = [$method, \ltrim($uri, "/"), $requestHandler];
    }

    /**
     * Adds a middleware instance that is applied to every route, but will not be applied to the fallback request
     * handler.
     *
     * All middlewares are called in the order they're passed, so the first middleware is the outer middleware.
     *
     * @throws \Error If the server has started.
     */
    public function addMiddleware(Middleware $middleware): void
    {
        if ($this->running) {
            throw new \Error("Cannot add middleware after the server has started");
        }

        $this->middlewares[] = $middleware;
    }

    /**
     * Specifies an instance of {@see RequestHandler} that is used if no routes match.
     *
     * If no fallback is given, a 404 response is returned from {@see handleRequest()} when no matching routes are
     * found.
     *
     * @throws \Error If the server has started.
     */
    public function setFallback(RequestHandler $requestHandler): void
    {
        if ($this->running) {
            throw new \Error("Cannot add fallback request handler after the server has started");
        }

        $this->fallback = $requestHandler;
    }

    /**
     * Create a response if no routes matched and no fallback has been set.
     */
    private function notFound(Request $request): Response
    {
        return $this->errorHandler->handleError(HttpStatus::NOT_FOUND, null, $request);
    }

    /**
     * Create a response if the requested method is not allowed for the matched path.
     *
     * @param string[] $methods
     */
    private function methodNotAllowed(array $methods, Request $request): Response
    {
        $response = $this->errorHandler->handleError(HttpStatus::METHOD_NOT_ALLOWED, null, $request);
        $response->setHeader("allow", \implode(", ", $methods));
        return $response;
    }

    private function onStart(): void
    {
        if ($this->running) {
            throw new \Error("Router already started");
        }

        $this->running = true;

        if (!$this->routes) {
            $this->logger->notice("No routes registered");
            return;
        }

        $this->routeDispatcher = simpleDispatcher(function (RouteCollector $rc): void {
            $redirectHandler = new ClosureRequestHandler(static function (Request $request): Response {
                $uri = $request->getUri();
                $path = \rtrim($uri->getPath(), '/');

                if ($uri->getQuery() !== "") {
                    $redirectTo = $path . "?" . $uri->getQuery();
                } else {
                    $redirectTo = $path;
                }

                return new Response(HttpStatus::PERMANENT_REDIRECT, [
                    "location" => $redirectTo,
                    "content-type" => "text/plain; charset=utf-8",
                ], "Canonical resource location: {$path}");
            });

            foreach ($this->routes as [$method, $uri, $requestHandler]) {
                $requestHandler = Middleware\stackMiddleware($requestHandler, ...$this->middlewares);
                $uri = $this->prefix . $uri;

                // Special-case, otherwise we redirect just to the same URI again
                if ($uri === "/?") {
                    $uri = "/";
                }

                if (\str_ends_with($uri, "/?")) {
                    $canonicalUri = \substr($uri, 0, -2);
                    $redirectUri = \substr($uri, 0, -1);

                    $rc->addRoute($method, $canonicalUri, $requestHandler);
                    $rc->addRoute($method, $redirectUri, $redirectHandler);
                } else {
                    $rc->addRoute($method, $uri, $requestHandler);
                }
            }
        });
    }

    private function onStop(): void
    {
        $this->routeDispatcher = null;
        $this->running = false;
    }
}
