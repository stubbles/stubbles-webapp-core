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
use net\stubbles\ioc\Injector;
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
     * injector instance
     *
     * @type  Injector
     */
    private $injector;
    /**
     * auth handler to handle authorization requests
     *
     * @type  AuthHandler
     */
    private $authHandler;

    /**
     * constructor
     *
     * @param  WebRequest          $request    request data container
     * @param  ResponseNegotiator  $response   response container
     * @param  Injector            $injector
     * @Inject
     */
    public function __construct(WebRequest $request,
                                ResponseNegotiator $response,
                                Injector $injector)
    {
        $this->request            = $request;
        $this->responseNegotiator = $response;
        $this->injector           = $injector;
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
        $routing = new Routing(new UriRequest($this->request->getUri(), $this->request->getMethod()));
        $this->configureRouting($routing);
        $response = $this->responseNegotiator->negotiate($this->request, $routing);
        if (!$this->request->isCancelled()) {
            $route = $this->detectRoute($routing, $response);
            if (null !== $route) {
                if ($route->applyPreInterceptors($this->injector, $this->request, $response)) {
                    if ($route->process($this->injector, $this->request, $response)) {
                        $route->applyPostInterceptors($this->injector, $this->request, $response);
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
     * @param   Routing     $routing
     * @param   Response    $response
     * @return  ProcessableRoute
     */
    private function detectRoute(Routing $routing, Response $response)
    {
        if (!$routing->canFindRoute()) {
            $allowedMethods = $routing->getAllowedMethods();
            if (count($allowedMethods) === 0) {
                $response->notFound();
            } else {
                $response->methodNotAllowed($this->request->getMethod(), $allowedMethods);
            }

            return null;
        }

        $route = $routing->findRoute();
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