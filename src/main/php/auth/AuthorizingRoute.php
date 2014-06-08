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
     * @type  AuthHandler
     */
    private $authHandler;
    /**
     * route configuration
     *
     * @type  Route
     */
    private $routeConfig;
    /**
     * actual route which requires auth
     *
     * @type  ProcessableRoute
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
     * @param  AuthHandler       $authHandler
     * @param  Route             $routeconfig
     * @param  ProcessableRoute  $actualRoute
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
    public function switchToHttps()
    {
        return $this->actualRoute->switchToHttps();
    }

    /**
     * returns https uri of current route
     *
     * @return  \stubbles\peer\http\HttpUri
     */
    public function getHttpsUri()
    {
        return $this->actualRoute->getHttpsUri();
    }

    /**
     * returns list of supported mime types
     *
     * @return  \stubbles\webapp\response\SupportedMimeTypes
     */
    public function getSupportedMimeTypes()
    {
        return $this->actualRoute->getSupportedMimeTypes();
    }

    /**
     * apply pre interceptors
     *
     * Returns false if one of the pre interceptors cancels the request.
     *
     * @param   WebRequest  $request    current request
     * @param   Response    $response   response to send
     * @return  bool
     */
    public function applyPreInterceptors(WebRequest $request, Response $response)
    {
        try {
            if (!$this->authHandler->isAuthenticated()) {
                $response->redirect($this->authHandler->getLoginUri());
                return false;
            }

            if (!$this->isAuthorized()) {
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
     * @return  bool
     */
    private function isAuthorized()
    {
        if ($this->routeConfig->requiresRole()) {
            return $this->authHandler->isAuthorized($this->routeConfig->getRequiredRole());
        }

        return true;
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
        if ($this->authorized) {
            return $this->actualRoute->process($request, $response);
        }

        return false;
    }

    /**
     * apply post interceptors
     *
     * @param   WebRequest  $request    current request
     * @param   Response    $response   response to send
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
