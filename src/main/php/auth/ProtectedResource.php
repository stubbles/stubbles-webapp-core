<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\auth;
use stubbles\ioc\Injector;
use stubbles\peer\http\HttpUri;
use stubbles\webapp\Request;
use stubbles\webapp\Response;
use stubbles\webapp\response\Error;
use stubbles\webapp\routing\UriResource;
/**
 * Description of AuthorizingRoute
 *
 * @since  3.0.0
 */
class ProtectedResource implements UriResource
{
    /**
     * switch whether access to route is authorized
     */
    private bool $authorized  = false;
    private ?Error $error = null;

    public function __construct(
        private AuthConstraint $authConstraint,
        private UriResource $actualResource,
        private Injector $injector
    ) { }

    /**
     * checks whether switch to https is required
     */
    public function requiresHttps(): bool
    {
        return $this->actualResource->requiresHttps();
    }

    /**
     * returns https uri of current route
     */
    public function httpsUri(): HttpUri
    {
        return $this->actualResource->httpsUri();
    }

    /**
     * negotiates proper mime type for given request
     *
     * @since   6.0.0
     */
    public function negotiateMimeType(Request $request, Response $response): bool
    {
        return $this->actualResource->negotiateMimeType($request, $response);
    }

    /**
     * returns list of supported mime types
     *
     * @return  string[]
     */
    public function supportedMimeTypes(): array
    {
        return $this->actualResource->supportedMimeTypes();
    }

    /**
     * apply pre interceptors
     *
     * Pre interceptors for actual resource are only applied when request is
     * authorized.
     */
    public function applyPreInterceptors(Request $request, Response $response): bool
    {
        if ($this->isAuthorized($request, $response)) {
            return $this->actualResource->applyPreInterceptors($request, $response);
        }

        return true;
    }

    private function isAuthorized(Request $request, Response $response): bool
    {
        $this->authorized = false;
        $user = $this->authenticate($request, $response);
        if (null !== $user && $this->authConstraint->requiresRoles()) {
            $roles = $this->roles($response, $user);
            if (null !== $roles && $this->authConstraint->satisfiedByRoles($roles)) {
                $request->associate(new Identity($user, $roles));
                $this->authorized = true;
            } elseif (null !== $roles) {
                $this->error = $response->forbidden();
            }
        } elseif (null !== $user) {
            $request->associate(new Identity($user, Roles::none()));
            $this->authorized = true;
        }

        return $this->authorized;
    }

    private function authenticate(Request $request, Response $response): ?User
    {
        /** @var  AuthenticationProvider  $authenticationProvider */
        $authenticationProvider = $this->injector->getInstance(AuthenticationProvider::class);
        try {
            $user = $authenticationProvider->authenticate($request);
            if (null === $user && $this->authConstraint->redirectToLogin()) {
                $response->redirect($authenticationProvider->loginUri($request));
            } elseif (null ===   $user) {
                $this->error = $response->unauthorized($authenticationProvider->challengesFor($request));
            }

            return $user;
        } catch (AuthProviderException $ahe) {
            $this->handleAuthProviderException($ahe, $response);
            return null;
        }
    }

    private function roles(Response $response, User $user): ?Roles
    {
        try {
            /** @var  AuthorizationProvider  $authorizationProvider */
            $authorizationProvider = $this->injector->getInstance(AuthorizationProvider::class);
            return $authorizationProvider->roles($user);
        } catch (AuthProviderException $ahe) {
            $this->handleAuthProviderException($ahe, $response);
            return null;
        }
    }

    /**
     * sets proper response status code depending on exception
     */
    private function handleAuthProviderException(
        AuthProviderException $ahe,
        Response $response
    ): void {
        if ($ahe->isInternal()) {
            $this->error = $response->internalServerError($ahe);
        } else {
            $response->setStatusCode($ahe->getCode());
            $this->error = new Error($ahe->getMessage());
        }
    }

    /**
     * creates processor instance
     *
     * Resolving of actual resource is only done when request is authorized.
     */
    public function resolve(Request $request, Response $response): mixed
    {
        if ($this->authorized) {
            return $this->actualResource->resolve($request, $response);
        }

        return $this->error;
    }

    /**
     * apply post interceptors
     *
     * Post interceptors of actual resource are applied independent of whether
     * request was authorized or not.
     */
    public function applyPostInterceptors(Request $request, Response $response): bool
    {
        return $this->actualResource->applyPostInterceptors($request, $response);
    }
}
