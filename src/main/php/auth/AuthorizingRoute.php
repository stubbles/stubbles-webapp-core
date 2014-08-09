<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\auth;
use stubbles\input\web\WebRequest;
use stubbles\webapp\ProcessableRoute;
use stubbles\webapp\Route;
use stubbles\webapp\response\Response;
/**
 * Description of AuthorizingRoute
 *
 * @since  3.0.0
 */
class AuthorizingRoute implements ProcessableRoute
{
    /**
     * auth handler
     *
     * @type  \stubbles\webapp\auth\AuthHandler
     */
    private $authHandler;
    /**
     * route configuration
     *
     * @type  \stubbles\webapp\Route
     */
    private $routeConfig;
    /**
     * actual route which requires auth
     *
     * @type  \stubbles\webapp\ProcessableRoute
     */
    private $actualRoute;
    /**
     * switch whether access to route is authorized
     *
     * @type  bool
     */
    private $authorized  = false;

    /**
     * constructor
     *
     * @param  \stubbles\webapp\auth\AuthHandler  $authHandler
     * @param  \stubbles\webapp\Route             $routeconfig
     * @param  \stubbles\webapp\ProcessableRoute  $actualRoute
     */
    public function __construct(AuthHandler $authHandler, Route $routeconfig, ProcessableRoute $actualRoute)
    {
        $this->authHandler = $authHandler;
        $this->routeConfig = $routeconfig;
        $this->actualRoute = $actualRoute;
    }

    /**
     * checks whether switch to https is required
     *
     * @return  bool
     */
    public function requiresHttps()
    {
        return $this->actualRoute->requiresHttps();
    }

    /**
     * returns https uri of current route
     *
     * @return  \stubbles\peer\http\HttpUri
     */
    public function httpsUri()
    {
        return $this->actualRoute->httpsUri();
    }

    /**
     * returns list of supported mime types
     *
     * @return  \stubbles\webapp\response\SupportedMimeTypes
     */
    public function supportedMimeTypes()
    {
        return $this->actualRoute->supportedMimeTypes();
    }

    /**
     * apply pre interceptors
     *
     * Returns false if one of the pre interceptors cancels the request.
     *
     * @param   \stubbles\input\web\WebRequest      $request   current request
     * @param   \stubbles\webapp\response\Response  $response  response to send
     * @return  bool
     */
    public function applyPreInterceptors(WebRequest $request, Response $response)
    {
        try {
            if (!$this->authHandler->isAuthenticated($request)) {
                $response->redirect($this->authHandler->loginUri($request));
                return false;
            }

            if (!$this->isAuthorized($request)) {
                $response->forbidden();
                return false;
            }

            $this->authorized = true;
            return $this->actualRoute->applyPreInterceptors($request, $response);
        } catch (AuthHandlerException $ahe) {
            if ($ahe->isInternal()) {
                $response->internalServerError($ahe->getMessage());
            } else {
                $response->setStatusCode($ahe->getCode())
                         ->write($ahe->getMessage());
            }
        }

        return false;
    }

    /**
     * checks whether authorization is sufficient
     *
     * @param   \stubbles\input\web\WebRequest  $request
     * @return  bool
     */
    private function isAuthorized(WebRequest $request)
    {
        if ($this->routeConfig->requiresRole()) {
            return $this->authHandler->isAuthorized($request, $this->routeConfig->requiredRole());
        }

        return true;
    }

    /**
     * creates processor instance
     *
     * @param   \stubbles\input\web\WebRequest      $request   current request
     * @param   \stubbles\webapp\response\Response  $response  response to send
     * @return  bool
     */
    public function process(WebRequest $request, Response $response)
    {
        if ($this->authorized) {
            return $this->actualRoute->process($request, $response);
        }

        return false;
    }

    /**
     * apply post interceptors
     *
     * @param   \stubbles\input\web\WebRequest      $request   current request
     * @param   \stubbles\webapp\response\Response  $response  response to send
     * @return  bool
     */
    public function applyPostInterceptors(WebRequest $request, Response $response)
    {
        if ($this->authorized) {
            return $this->actualRoute->applyPostInterceptors($request, $response);
        }

        return false;
    }
}
