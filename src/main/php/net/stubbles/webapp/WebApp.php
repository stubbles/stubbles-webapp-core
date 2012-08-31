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
use net\stubbles\webapp\response\FormattingResponse;
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
        $calledUri = new UriRequest($this->request->getUri(), $this->request->getMethod());
        $routing   = new Routing($calledUri);
        $this->configureRouting($routing);
        $response = $this->responseNegotiator->negotiate($this->request, $routing);
        if (null === $response) {
            return;
        }

        $route = $this->detectRoute($routing, $calledUri, $response);
        if (null !== $route) {
            if ($this->applyPreInterceptors($routing->getPreInterceptors(), $response)) {
                $route->process($calledUri, $this->injector, $this->request, $response);
                if (!$this->request->isCancelled()) {
                    $this->applyPostInterceptors($routing->getPostInterceptors(), $response);
                }
            }
        }

        $response->send();
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
     * @param   Routing             $routing
     * @param   UriRequest          $calledUri
     * @param   FormattingResponse  $response
     * @return  Route
     */
    private function detectRoute(Routing $routing, UriRequest $calledUri, FormattingResponse $response)
    {
        if (!$routing->canFindRoute()) {
            $allowedMethods = $routing->getAllowedMethods();
            if (count($allowedMethods) === 0) {
                $response->setStatusCode(404)
                         ->writeNotFoundError();
            } else {
                $response->setStatusCode(405)
                         ->writeMethodNotAllowedError($this->request->getMethod(), $allowedMethods);
            }

            return null;
        }

        $route = $routing->findRoute();
        if (!$calledUri->isHttps() && $route->requiresHttps()) {
            $response->redirect($calledUri->toHttps());
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
     * @param   Route               $route
     * @param   FormattingResponse  $response
     * @return  bool
     */
    private function isAuthorized(Route $route, FormattingResponse $response)
    {
        if (!$route->requiresRole()) {
            return true;
        }

        if (null === $this->authHandler) {
            $response->setStatusCode(500)
                     ->writeInternalServerError('Requested route requires authorization, but no auth handler defined for application');
            return false;
        }

        if ($this->authHandler->isAuthorized($route->getRequiredRole())) {
            return true;
        }

        if ($this->authHandler->requiresLogin($route->getRequiredRole())) {
            $response->redirect($this->authHandler->getLoginUri());
        } else {
            $response->setStatusCode(403)
                     ->writeForbiddenError();
        }

        return false;
    }

    /**
     * apply pre interceptors
     *
     * Returns false if one of the pre interceptors cancels the request.
     *
     * @param   string|Closure      $preInterceptors
     * @param   FormattingResponse  $response
     * @return  bool
     */
    private function applyPreInterceptors(array $preInterceptors, FormattingResponse $response)
    {
        foreach ($preInterceptors as $interceptor) {
            if ($interceptor instanceof \Closure) {
                $interceptor($this->request, $response);
            } elseif (is_callable($interceptor)) {
                call_user_func_array($interceptor, array($this->request, $response));
            } else {
                $this->injector->getInstance($interceptor)
                               ->preProcess($this->request, $response);
            }

            if ($this->request->isCancelled()) {
                return false;
            }
        }

        return true;
    }

    /**
     * apply post interceptors
     *
     * @param   string|Closure      $postInterceptors
     * @param   FormattingResponse  $response
     */
    private function applyPostInterceptors(array $postInterceptors, FormattingResponse $response)
    {
        foreach ($postInterceptors as $interceptor) {
            if ($interceptor instanceof \Closure) {
                $interceptor($this->request, $response);
            } elseif (is_callable($interceptor)) {
                call_user_func_array($interceptor, array($this->request, $response));
            } else {
                $this->injector->getInstance($interceptor)
                               ->postProcess($this->request, $response);
            }

            if ($this->request->isCancelled()) {
                return;
            }
        }
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