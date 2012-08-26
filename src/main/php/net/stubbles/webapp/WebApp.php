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
     * response container
     *
     * @type  Response
     */
    private $response;
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
     * @param  WebRequest  $request    request data container
     * @param  Response    $response   response container
     * @param  Injector    $injector
     * @Inject
     */
    public function __construct(WebRequest $request,
                                Response $response,
                                Injector $injector)
    {
        $this->request   = $request;
        $this->response  = $response;
        $this->injector  = $injector;
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
        if (!$this->request->isCancelled()) {
            $calledUri = new UriRequest($this->request->getUri(), $this->request->getMethod());
            $routing   = new Routing($calledUri);
            $this->configureRouting($routing);
            $route = $routing->getRoute();
            if (null === $route) {
                if (!$routing->hasRouteForPath()) {
                    $this->response->setStatusCode(404);
                } else {
                    $this->response->setStatusCode(405)
                                   ->addHeader('Allow', join(', ', $routing->getAllowedMethods()));
                }

                $this->request->cancel();
            } elseif ($route->requiresAuth() && null === $this->authHandler) {
                $this->response->setStatusCode(500)
                               ->write('Requested route requires a role, but no auth handler defined for application');
                $this->request->cancel();
            } elseif ($route->requiresAuth() && !$route->isAuthorized($this->authHandler)) {
                if ($route->requiresLogin($this->authHandler)) {
                    $this->response->redirect($this->authHandler->getLoginUri());
                } else {
                    $this->response->setStatusCode(403);
                }

                $this->request->cancel();
            } elseif (!$calledUri->isHttps() && $route->requiresHttps()) {
                $this->response->redirect($calledUri->toHttps());
                $this->request->cancel();
            } elseif ($this->applyPreInterceptors($routing->getPreInterceptors())) {
                $route->process($this->injector, $this->request, $this->response);
                if (!$this->request->isCancelled()) {
                    $this->applyPostInterceptors($routing->getPostInterceptors());
                }
            }
        }

        $this->response->send();
    }

    /**
     * configures routing for this web app
     *
     * @param  RoutingConfigurator  $routing
     */
    protected abstract function configureRouting(RoutingConfigurator $routing);

    /**
     * apply pre interceptors
     *
     * Returns false if one of the pre interceptors cancels the request.
     *
     * @param   string|Closure  $preInterceptors
     * @return  bool
     */
    private function applyPreInterceptors(array $preInterceptors)
    {
        foreach ($preInterceptors as $interceptor) {
            if ($interceptor instanceof \Closure) {
                $interceptor($this->request, $this->response);
            } elseif (is_callable($interceptor)) {
                call_user_func_array($interceptor, array($this->request, $this->response));
            } else {
                $this->injector->getInstance($interceptor)
                               ->preProcess($this->request, $this->response);
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
     * @param   string|Closure  $postInterceptors
     */
    private function applyPostInterceptors(array $postInterceptors)
    {
        foreach ($postInterceptors as $interceptor) {
            if ($interceptor instanceof \Closure) {
                $interceptor($this->request, $this->response);
            } elseif (is_callable($interceptor)) {
                call_user_func_array($interceptor, array($this->request, $this->response));
            } else {
                $this->injector->getInstance($interceptor)
                               ->postProcess($this->request, $this->response);
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