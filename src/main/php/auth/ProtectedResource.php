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
use stubbles\ioc\Injector;
use stubbles\webapp\Request;
use stubbles\webapp\Response;
use stubbles\webapp\auth\ioc\RolesProvider;
use stubbles\webapp\auth\ioc\UserProvider;
use stubbles\webapp\response\Error;
use stubbles\webapp\routing\Resource;
/**
 * Description of AuthorizingRoute
 *
 * @since  3.0.0
 */
class ProtectedResource implements Resource
{
    /**
     * route configuration
     *
     * @type  \stubbles\webapp\auth\AuthConstraint
     */
    private $authConstraint;
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
     *
     * @type  \stubbles\webapp\response\Error
     */
    private $model;

    /**
     * constructor
     *
     * @param  \stubbles\webapp\auth\AuthConstraint       $authConstraint
     * @param  \stubbles\webapp\routing\Resource  $actualRoute
     * @param  \stubbles\ioc\Injector                     $injector
     */
    public function __construct(
            AuthConstraint $authConstraint,
            Resource $actualRoute,
            Injector $injector)
    {
        $this->authConstraint = $authConstraint;
        $this->actualRoute    = $actualRoute;
        $this->injector       = $injector;
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
     * negotiates proper mime type for given request
     *
     * @param   \stubbles\webapp\Request   $request
     * @param   \stubbles\webapp\Response  $response  response to send
     * @return  bool
     * @since   6.0.0
     */
    public function negotiateMimeType(Request $request, Response $response)
    {
        return $this->actualRoute->negotiateMimeType($request, $response);
    }

    /**
     * returns list of supported mime types
     *
     * @return  string[]
     */
    public function supportedMimeTypes()
    {
        return $this->actualRoute->supportedMimeTypes();
    }

    /**
     * apply pre interceptors
     *
     * @param   \stubbles\webapp\Request   $request   current request
     * @param   \stubbles\webapp\Response  $response  response to send
     * @return  bool
     */
    public function applyPreInterceptors(Request $request, Response $response)
    {
        if ($this->isAuthorized($request, $response)) {
            return $this->actualRoute->applyPreInterceptors($request, $response);
        }

        return false;
    }

    /**
     * checks if request is authorized
     *
     * @param   \stubbles\webapp\Request   $request   current request
     * @param   \stubbles\webapp\Response  $response  response to send
     * @return  bool
     */
    private function isAuthorized(Request $request, Response $response)
    {
        $this->authorized = false;
        $user = $this->authenticate($request, $response);
        if (null !== $user && $this->authConstraint->requiresRoles()) {
            $roles = $this->roles($response, $user);
            if (null !== $roles && $this->authConstraint->satisfiedByRoles(
                    RolesProvider::store($roles))) {
                $this->authorized = true;
            } elseif (null !== $roles) {
                $this->model = $response->forbidden();
            }
        } elseif (null !== $user) {
             $this->authorized = true;
        }

        return $this->authorized;
    }

    /**
     * checks whether request is authenticated
     *
     * @param   \stubbles\webapp\Request   $request   current request
     * @param   \stubbles\webapp\Response  $response  response to send
     * @return  \stubbles\webapp\auth\User
     */
    private function authenticate(Request $request, Response $response)
    {
        $authenticationProvider = $this->injector->getInstance('stubbles\webapp\auth\AuthenticationProvider');
        try {
            $user = $authenticationProvider->authenticate($request);
            if (null == $user && $this->authConstraint->loginAllowed()) {
                $response->redirect($authenticationProvider->loginUri($request));
            } elseif (null == $user) {
                $this->model = $response->forbidden();
                return null;
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
     * @param   \stubbles\webapp\Response   $response  response to send
     * @param   \stubbles\webapp\auth\User  $user
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
     * sets proper response status code depending on exception
     *
     * @param  \stubbles\webapp\auth\AuthProviderException  $ahe
     * @param  \stubbles\webapp\Response                    $response
     */
    private function handleAuthProviderException(AuthProviderException $ahe, Response $response)
    {
        if ($ahe->isInternal()) {
            $this->model = $response->internalServerError($ahe);
        } else {
            $response->setStatusCode($ahe->getCode());
            $this->model = new Error($ahe->getMessage());
        }
    }

    /**
     * creates processor instance
     *
     * @param   \stubbles\webapp\Request   $request   current request
     * @param   \stubbles\webapp\Response  $response  response to send
     * @return  mixed
     */
    public function data(Request $request, Response $response)
    {
        if ($this->authorized) {
            return $this->actualRoute->data($request, $response);
        }

        return $this->model;
    }

    /**
     * apply post interceptors
     *
     * @param   \stubbles\webapp\Request   $request   current request
     * @param   \stubbles\webapp\Response  $response  response to send
     * @return  bool
     */
    public function applyPostInterceptors(Request $request, Response $response)
    {
        if ($this->authorized) {
            return $this->actualRoute->applyPostInterceptors($request, $response);
        }

        return false;
    }
}
