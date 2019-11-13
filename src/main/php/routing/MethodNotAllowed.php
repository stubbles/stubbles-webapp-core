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
 * Represents a resource which was accessed with a not suitable method.
 *
 * @since  2.2.0
 */
class MethodNotAllowed extends AbstractResource
{
    /**
     * list of actually allowed request methods
     *
     * @type  string[]
     */
    private $allowedMethods;

    /**
     * constructor
     *
     * @param  \stubbles\ioc\Injector                       $injector
     * @param  \stubbles\webapp\routing\CalledUri           $calledUri           actual called uri
     * @param  \stubbles\webapp\interceptor\Interceptors    $interceptors
     * @param  \stubbles\webapp\routing\SupportedMimeTypes  $supportedMimeTypes
     * @param  string[]                                     $allowedMethods
     */
    public function __construct(
            Injector $injector,
            CalledUri $calledUri,
            Interceptors $interceptors,
            SupportedMimeTypes $supportedMimeTypes,
            array $allowedMethods
    ) {
        parent::__construct($injector, $calledUri, $interceptors, $supportedMimeTypes);
        $this->allowedMethods = $allowedMethods;
        if (!in_array(Http::OPTIONS, $this->allowedMethods)) {
            $this->allowedMethods[] = Http::OPTIONS;
        }
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
     * @return  \stubbles\webapp\response\Error
     */
    public function resolve(Request $request, Response $response)
    {
        return $response->methodNotAllowed(
                $request->method(),
                $this->allowedMethods
        );
    }

}
