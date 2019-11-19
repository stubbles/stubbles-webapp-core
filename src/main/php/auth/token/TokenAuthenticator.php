<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\auth\token;
use stubbles\webapp\Request;
use stubbles\webapp\auth\AuthenticationProvider;
use stubbles\webapp\auth\InternalAuthProviderException;
use stubbles\webapp\auth\Token;
use stubbles\webapp\auth\User;
/**
 * Supports token handling for intranet users.
 *
 * @since  5.0.0
 */
class TokenAuthenticator implements AuthenticationProvider
{
    /**
     * store where tokens are saved
     *
     * @type  \stubbles\webapp\auth\token\TokenStore
     */
    private $tokenStore;
    /**
     * salt to be used for token generation
     *
     * @type  string
     */
    private $tokenSalt;
    /**
     * authentication provider which does actual login if no token or user found
     *
     * @type  AuthenticationProvider
     */
    private $loginProvider;

    /**
     * constructor
     *
     * @param  \stubbles\webapp\auth\token\TokenStore        $tokenStore
     * @param  string                                        $tokenSalt
     * @param  \stubbles\webapp\auth\AuthenticationProvider  $loginProvider
     * @Property{tokenSalt}('stubbles.webapp.auth.token.salt')
     * @Named{loginProvider}('stubbles.webapp.auth.token.loginProvider')
     */
    public function __construct(
            TokenStore $tokenStore,
            string $tokenSalt,
            AuthenticationProvider $loginProvider)
    {
        $this->tokenStore    = $tokenStore;
        $this->tokenSalt     = $tokenSalt;
        $this->loginProvider = $loginProvider;
    }

    /**
     * authenticates that the given request is valid
     *
     * @param   \stubbles\webapp\Request  $request
     * @return  \stubbles\webapp\auth\User|null
     * @throws  \stubbles\webapp\auth\InternalAuthProviderException
     */
    public function authenticate(Request $request): ?User
    {
        $token = $this->readToken($request);
        if (null === $token || $token->isEmpty()) {
            return $this->login($request);
        }

        try {
            $user = $this->tokenStore->findUserByToken($request, $token);
            return null === $user ? $this->login($request) : $user->setToken($token);
        } catch (\Exception $e) {
            throw new InternalAuthProviderException(
                    'Error while trying to find user by token: ' . $e->getMessage(),
                    $e
            );
        }
    }

    /**
     * performs login when token not found or invalid
     *
     * @param   \stubbles\webapp\Request $request
     * @return  \stubbles\webapp\auth\User|null
     * @throws  \stubbles\webapp\auth\InternalAuthProviderException
     */
    private function login(Request $request): ?User
    {
        $user = $this->loginProvider->authenticate($request);
        if (null !== $user) {
            try {
                $token = $user->createToken($this->tokenSalt);
                $this->tokenStore->store($request, $token, $user);
                return $user;
            } catch (\Exception $e) {
                throw new InternalAuthProviderException(
                        'Error while trying to store new token for user: ' . $e->getMessage(),
                        $e
                );
            }
        }

        return null;
    }

    /**
     * reads token from authorization header
     *
     * @param   \stubbles\webapp\Request  $request  current request
     * @return  \stubbles\webapp\auth\Token|null
     */
    private function readToken(Request $request): ?Token
    {
        if (!$request->hasRedirectHeader('HTTP_AUTHORIZATION')) {
            return null;
        }

        return $request->readRedirectHeader('HTTP_AUTHORIZATION')
                ->withFilter(TokenFilter::instance());
    }

    /**
     * returns login uri
     *
     * @param   \stubbles\webapp\Request  $request
     * @return  string|\stubbles\peer\http\HttpUri
     */
    public function loginUri(Request $request)
    {
        return $this->loginProvider->loginUri($request);
    }

    /**
     * returns a list of challenges to send in response's 401 WWW-Authenticate header for given request
     *
     * The method is called when the authenticate() method returns <null> and a
     * redirect to a login URI is not allowed for the resource, but a
     * 401 Unauthorized response should be send instead.
     *
     * @since   8.0.0
     * @param   \stubbles\webapp\Request  $request
     * @return  string[]  list of challenges for the WWW-Authenticate header, must at least contain one
     */
    public function challengesFor(Request $request): array
    {
        return $this->loginProvider->challengesFor($request);
    }
}
