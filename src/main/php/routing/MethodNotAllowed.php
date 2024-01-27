<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing;
use stubbles\ioc\Injector;
use stubbles\peer\http\Http;
use stubbles\webapp\Request;
use stubbles\webapp\Response;
use stubbles\webapp\response\Error;

/**
 * Represents a resource which was accessed with a not suitable method.
 *
 * @since  2.2.0
 */
class MethodNotAllowed extends AbstractResource
{
    /**
     * @param  string[]  $allowedMethods
     */
    public function __construct(
        Injector $injector,
        CalledUri $calledUri,
        Interceptors $interceptors,
        SupportedMimeTypes $supportedMimeTypes,
        private array $allowedMethods
    ) {
        parent::__construct($injector, $calledUri, $interceptors, $supportedMimeTypes);
        if (!in_array(Http::OPTIONS, $this->allowedMethods)) {
            $this->allowedMethods[] = Http::OPTIONS;
        }
    }

    public function requiresHttps(): bool
    {
        return false;
    }

    public function resolve(Request $request, Response $response): Error
    {
        return $response->methodNotAllowed($request->method(), $this->allowedMethods);
    }

}
