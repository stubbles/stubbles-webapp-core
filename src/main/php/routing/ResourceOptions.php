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
    /**
     * list of actually allowed request methods
     *
     * @var  \stubbles\webapp\routing\MatchingRoutes
     */
    private $matchingRoutes;

    /**
     * constructor
     *
     * @param  \stubbles\ioc\Injector                       $injector
     * @param  \stubbles\webapp\routing\CalledUri           $calledUri           actual called uri
     * @param  \stubbles\webapp\routing\Interceptors        $interceptors
     * @param  \stubbles\webapp\routing\SupportedMimeTypes  $supportedMimeTypes
     * @param  \stubbles\webapp\routing\MatchingRoutes      $matchingRoutes
     */
    public function __construct(
            Injector $injector,
            CalledUri $calledUri,
            Interceptors $interceptors,
            SupportedMimeTypes $supportedMimeTypes,
            MatchingRoutes $matchingRoutes)
    {
        parent::__construct($injector, $calledUri, $interceptors, $supportedMimeTypes);
        $this->matchingRoutes = $matchingRoutes;
    }

    /**
     * checks whether switch to https is required
     *
     * @return  bool
     */
    public function requiresHttps(): bool
    {
        return false;
    }

    /**
     * creates processor instance
     *
     * @param   \stubbles\webapp\Request   $request   current request
     * @param   \stubbles\webapp\Response  $response  response to send
     * @return  void
     */
    public function resolve(Request $request, Response $response)
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
    }

}
