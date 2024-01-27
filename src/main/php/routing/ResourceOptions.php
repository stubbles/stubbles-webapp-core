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
/**
 * Denotes an answer to an OPTIONS request when no specific route for such
 * requests was configured.
 *
 * @since  2.2.0
 */
class ResourceOptions extends AbstractResource
{
    public function __construct(
        Injector $injector,
        CalledUri $calledUri,
        Interceptors $interceptors,
        SupportedMimeTypes $supportedMimeTypes,
        private MatchingRoutes $matchingRoutes
    ) {
        parent::__construct($injector, $calledUri, $interceptors, $supportedMimeTypes);
    }

    public function requiresHttps(): bool
    {
        return false;
    }

    public function resolve(Request $request, Response $response): null
    {
        $allowedMethods = $this->matchingRoutes->allowedMethods();
        if (!in_array(Http::OPTIONS, $allowedMethods)) {
            $allowedMethods[] = Http::OPTIONS;
        }

        $response->addHeader('Allow', join(', ', $allowedMethods))
            ->addHeader(
                'Access-Control-Allow-Methods',
                join(', ', $allowedMethods)
            );
        return null;
    }

}
