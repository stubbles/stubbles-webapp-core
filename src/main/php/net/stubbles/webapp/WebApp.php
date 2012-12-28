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
     */
    public function run()
    {
        $this->configureRouting($this->routing);
        $response = $this->responseNegotiator->negotiateMimeType($this->request, $this->routing);
        if (!$this->request->isCancelled()) {
            $route = $this->detectRoute($response);
            if (null !== $route) {
                if ($route->applyPreInterceptors($this->request, $response)) {
                    if ($route->process($this->request, $response)) {
                        $route->applyPostInterceptors($this->request, $response);
                    }
                }
            }
        }

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
     * retrieves route
     *
     * @param   Response  $response
     * @return  ProcessableRoute
     */
    private function detectRoute(Response $response)
    {
        if (!$this->routing->canFindRoute()) {
            $this->handleRouteMismatch($response);
            return null;
        }

        $route = $this->routing->findRoute();
        if ($route->switchToHttps()) {
            $response->redirect($route->getHttpsUri());
            return null;
        }

        if ($this->isAuthorized($route, $response)) {
            return $route;
        }

        return null;
    }

    /**
     * handles cases where no route can be found for a request
     *
     * The cases might be:
     *  - a request for a non-existing path, resulting in 404 Not Found
     *  - a request for an existing path with request method OPTIONS,
     *    resulting in 200 OK and appropriate response headers
     *  - a request for an existing path, but with a non-supported request
     *    method, resulting in 405 Method Not Allowed
     *
     * @param  Response  $response
     * @param  string[]  $allowedMethods
     */
    private function handleRouteMismatch(Response $response)
    {
        if (!$this->routing->canFindRouteWithAnyMethod()) {
            $response->notFound();
            return;
        }

        $allowedMethods = $this->routing->getAllowedMethods();
        if (!in_array('OPTIONS', $allowedMethods)) {
            $allowedMethods[] = 'OPTIONS';
        }

        if ($this->request->getMethod() === 'OPTIONS') {
            $response->addHeader('Allow', join(', ', $allowedMethods))
                     ->addHeader('Access-Control-Allow-Methods', join(', ', $allowedMethods));
            return;

        }

        $response->methodNotAllowed($this->request->getMethod(), $allowedMethods);
    }

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