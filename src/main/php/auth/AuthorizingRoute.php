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
use stubbles\ioc\Injector;
use stubbles\webapp\auth\ioc\RolesProvider;
use stubbles\webapp\auth\ioc\UserProvider;
use stubbles\webapp\response\Response;
use stubbles\webapp\routing\ProcessableRoute;
use stubbles\webapp\routing\Route;
/**
 * Description of AuthorizingRoute
 *
 * @since  3.0.0
 */
class AuthorizingRoute implements ProcessableRoute
{
    /**
     * route configuration
     *
     * @type  \stubbles\webapp\routing\Route
     */
    private $routeConfig;
    /**
     * actual route which requires auth
     *
     * @type  \stubbles\webapp\routing\ProcessableRoute
     */
    private $actualRoute;
    /**
     * provider which delivers authentication
     *
     * @type  \stubbles\ioc\Injector
     */
    private $injector;
    /**
     * switch whether access to route is authorized
     *
     * @type  bool
     */
    private $authorized  = false;

    /**
     * constructor
     *
     * @param  \stubbles\webapp\routing\Route             $routeconfig
     * @param  \stubbles\webapp\routing\ProcessableRoute  $actualRoute
     * @param  \stubbles\ioc\Injector                     $injector
     */
    public function __construct(
            Route $routeconfig,
            ProcessableRoute $actualRoute,
            Injector $injector)
    {
        $this->routeConfig = $routeconfig;
        $this->actualRoute = $actualRoute;
        $this->injector    = $injector;
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
     * @param   \stubbles\input\web\WebRequest      $request   current request
     * @param   \stubbles\webapp\response\Response  $response  response to send
     * @return  bool
     */
    public function applyPreInterceptors(WebRequest $request, Response $response)
    {
        if ($this->isAuthorized($request, $response)) {
            return $this->actualRoute->applyPreInterceptors($request, $response);
        }

        return false;
    }

    /**
     * checks if request is authorized
     *
     * @param   \stubbles\input\web\WebRequest      $request   current request
     * @param   \stubbles\webapp\response\Response  $response  response to send
     * @return  bool
     */
    private function isAuthorized(WebRequest $request, Response $response)
    {
        $this->authorized = false;
        $user = $this->authenticate($request, $response);
        if (null !== $user && $this->routeConfig->requiresRoles()) {
            if ($this->routeConfig->satisfiedByRoles(
                    RolesProvider::store($this->roles($response, $user)))
                ) {
                $this->authorized = true;
            } else {
                $response->forbidden();
            }
        } elseif (null !== $user) {
             $this->authorized = true;
        }

        return $this->authorized;
    }

    /**
     * checks whether request is authenticated
     *
     * @param   \stubbles\input\web\WebRequest      $request   current request
     * @param   \stubbles\webapp\response\Response  $response  response to send
     * @return  \stubbles\webapp\auth\User
     */
    private function authenticate(WebRequest $request, Response $response)
    {
        $authenticationProvider = $this->injector->getInstance('stubbles\webapp\auth\AuthenticationProvider');
        try {
            $user = $authenticationProvider->authenticate($request);
            if (null == $user) {
                $response->redirect($authenticationProvider->loginUri($request));
            }

            return UserProvider::store($user);
        } catch (AuthProviderException $ahe) {
            $this->handleAuthProviderException($ahe, $response);
            return null;
        }
    }

    /**
     * checks whether expected role is given
     *
     * @param   \stubbles\webapp\response\Response  $response  response to send
     * @param   \stubbles\webapp\auth\User          $user
     * @return  \stubbles\webapp\auth\Roles
     */
    private function roles(Response $response, User $user)
    {
        try {
            return $this->injector->getInstance('stubbles\webapp\auth\AuthorizationProvider')
                                  ->roles($user);
        } catch (AuthProviderException $ahe) {
            $this->handleAuthProviderException($ahe, $response);
            return null;
        }
    }

    /**
     *
     * @param  \stubbles\webapp\auth\AuthProviderException  $ahe
     * @param  \stubbles\webapp\response\Response          $response
     */
    private function handleAuthProviderException(AuthProviderException $ahe, Response $response)
    {
        if ($ahe->isInternal()) {
            $response->internalServerError($ahe->getMessage());
        } else {
            $response->setStatusCode($ahe->getCode())
                     ->write($ahe->getMessage());
        }
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
