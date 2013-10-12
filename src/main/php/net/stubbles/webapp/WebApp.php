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
use net\stubbles\ioc\App;
use net\stubbles\webapp\ioc\IoBindingModule;
use net\stubbles\webapp\response\Response;
use net\stubbles\webapp\response\ResponseNegotiator;
/**
 * Abstract base class for web applications.
 *
 * @since  1.7.0
 */
abstract class WebApp extends App
{
    /**
     * contains request data
     *
     * @type  WebRequest
     */
    private $request;
    /**
     * response negotiator
     *
     * @type  ResponseNegotiator
     */
    private $responseNegotiator;
    /**
     * build and contains routing information
     *
     * @type  Routing
     */
    private $routing;
    /**
     * auth handler to handle authorization requests
     *
     * @type  AuthHandler
     */
    private $authHandler;

    /**
     * constructor
     *
     * @param  WebRequest          $request             request data container
     * @param  ResponseNegotiator  $responseNegotiator  negoatiates based on request
     * @param  Routing             $routing
     * @Inject
     */
    public function __construct(WebRequest $request,
                                ResponseNegotiator $responseNegotiator,
                                Routing $routing)
    {
        $this->request            = $request;
        $this->responseNegotiator = $responseNegotiator;
        $this->routing            = $routing;
    }

    /**
     * sets auth handler
     *
     * @param   AuthHandler  $authHandler
     * @return  WebApp
     * @Inject(optional=true)
     */
    public function setAuthHandler(AuthHandler $authHandler)
    {
        $this->authHandler = $authHandler;
        return $this;
    }

    /**
     * runs the application
     *
     * @return  Response
     */
    public function run()
    {
        $this->configureRouting($this->routing);
        $route    = $this->routing->findRoute(new UriRequest($this->request->getUri(),
                                                             $this->request->getMethod()
                                              )
                    );
        $response = $this->responseNegotiator->negotiateMimeType($this->request, $route->getSupportedMimeTypes());
        if (!$this->request->isCancelled()) {
            if ($route->switchToHttps()) {
                $response->redirect($route->getHttpsUri());
            } elseif ($this->isAuthorized($route, $response)) {
                if ($route->applyPreInterceptors($this->request, $response)) {
                    if ($route->process($this->request, $response)) {
                        $route->applyPostInterceptors($this->request, $response);
                    }
                }
            }
        }

        $this->send($response);
        return $response;
    }

    /**
     * sends response
     *
     * @param  Response  $response
     */
    protected function send(Response $response)
    {
        if ($this->request->getMethod() === 'HEAD') {
            $response->sendHead();
        } else {
            $response->send();
        }
    }

    /**
     * configures routing for this web app
     *
     * @param  RoutingConfigurator  $routing
     */
    protected abstract function configureRouting(RoutingConfigurator $routing);

    /**
     * checks if request to given route is authorized
     *
     * @param   ProcessableRoute  $route
     * @param   Response          $response
     * @return  bool
     */
    private function isAuthorized(ProcessableRoute $route, Response $response)
    {
        if (!$route->requiresRole()) {
            return true;
        }

        if (null === $this->authHandler) {
            $response->internalServerError('Requested route requires authorization, but no auth handler defined for application');
            return false;
        }

        if ($this->authHandler->isAuthorized($route->getRequiredRole())) {
            return true;
        }

        if ($this->authHandler->requiresLogin($route->getRequiredRole())) {
            $response->redirect($this->authHandler->getLoginUri());
        } else {
            $response->forbidden();
        }

        return false;
    }

    /**
     * creates io binding module with session
     *
     * @param   string  $sessionName
     * @return  IoBindingModule
     */
    protected static function createIoBindingModuleWithSession($sessionName = 'PHPSESSID')
    {
        return IoBindingModule::createWithSession($sessionName);
    }

    /**
     * creates io binding module without session
     *
     * @return  IoBindingModule
     */
    protected static function createIoBindingModuleWithoutSession()
    {
        return IoBindingModule::createWithoutSession();
    }
}
?>