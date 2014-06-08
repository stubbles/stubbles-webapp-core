<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp;
use stubbles\input\web\WebRequest;
use stubbles\webapp\interceptor\Interceptors;
use stubbles\webapp\response\Response;
use stubbles\webapp\response\SupportedMimeTypes;
/**
 * Processable route which denotes a 405 Method Not Allowed route.
 *
 * @since  2.2.0
 */
class MethodNotAllowedRoute extends AbstractProcessableRoute
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
     * @param  UriRequest          $calledUri           actual called uri
     * @param  Interceptors        $interceptors
     * @param  SupportedMimeTypes  $supportedMimeTypes
     * @param  string[]            $allowedMethods
     */
    public function __construct(UriRequest $calledUri,
                                Interceptors $interceptors,
                                SupportedMimeTypes $supportedMimeTypes,
                                array $allowedMethods)
    {
        parent::__construct($calledUri,
                            $interceptors,
                            $supportedMimeTypes
        );
        $this->allowedMethods = $allowedMethods;
        if (!in_array('OPTIONS', $this->allowedMethods)) {
            $this->allowedMethods[] = 'OPTIONS';
        }
    }

    /**
     * checks whether switch to https is required
     *
     * @return  bool
     */
    public function switchToHttps()
    {
        return false;
    }

    /**
     * creates processor instance
     *
     * @param   WebRequest  $request    current request
     * @param   Response    $response   response to send
     * @return  bool
     */
    public function process(WebRequest $request, Response $response)
    {
        $response->methodNotAllowed($request->method(), $this->allowedMethods);
        return true;
    }

}