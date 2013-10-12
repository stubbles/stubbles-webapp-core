<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp;
use net\stubbles\input\web\WebRequest;
use net\stubbles\ioc\Injector;
use net\stubbles\webapp\response\Response;
use net\stubbles\webapp\response\SupportedMimeTypes;
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
     * @param  string[]            $allowedMethods
     * @param  UriRequest          $calledUri           actual called uri
     * @param  array               $preInterceptors     list of pre interceptors to be processed
     * @param  array               $postInterceptors    list of post interceptors to be processed
     * @param  Injector            $injector
     * @param  SupportedMimeTypes  $supportedMimeTypes
     */
    public function __construct(array $allowedMethods,
                                UriRequest $calledUri,
                                array $preInterceptors,
                                array $postInterceptors,
                                Injector $injector,
                                SupportedMimeTypes $supportedMimeTypes)
    {
        parent::__construct($calledUri,
                            $preInterceptors,
                            $postInterceptors,
                            $injector,
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
     * checks if access to this route required authorization
     *
     * @return  bool
     */
    public function requiresRole()
    {
        return false;
    }

    /**
     * checks whether this is an authorized request to this route
     *
     * @return  bool
     */
    public function getRequiredRole()
    {
        return null;
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
        $response->methodNotAllowed($request->getMethod(), $this->allowedMethods);
        return true;
    }

}