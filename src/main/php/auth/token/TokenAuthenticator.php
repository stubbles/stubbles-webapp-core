<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\auth\token;
use stubbles\webapp\auth\AuthenticationProvider;
use stubbles\webapp\auth\Token;
use stubbles\input\web\WebRequest;
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
     * @Inject
     * @Property{tokenSalt}('stubbles.webapp.auth.token.salt')
     * @Named{loginProvider}('stubbles.webapp.auth.token.loginProvider')
     */
    public function __construct(
            TokenStore $tokenStore,
            $tokenSalt,
            AuthenticationProvider $loginProvider)
    {
        $this->tokenStore    = $tokenStore;
        $this->tokenSalt     = $tokenSalt;
        $this->loginProvider = $loginProvider;
    }

    /**
     * authenticates that the given request is valid
     *
     * @param   \stubbles\input\web\WebRequest  $request
     * @return  \stubbles\webapp\auth\User
     */
    public function authenticate(WebRequest $request)
    {
        $token = $this->readToken($request);
        if (null === $token) {
            $user = $this->loginProvider->authenticate($request);
            if (null !== $user) {
                $this->tokenStore->store(
                        Token::create($user, $this->tokenSalt),
                        $user
                );
                return $user;
            }

            return null;
        } elseif ($token->isEmpty()) {
            return null;
        }

        return $this->tokenStore->findUserByToken($request, $token);
    }

    /**
     * reads token from authorization header
     *
     * @param   \stubbles\input\web\WebRequest  $request  current request
     * @return  \stubbles\webapp\auth\Token
     */
    private function readToken(WebRequest $request)
    {
        if (!$request->hasRedirectHeader('HTTP_AUTHORIZATION')) {
            return null;
        }

        return $request->readRedirectHeader('HTTP_AUTHORIZATION')->withFilter(TokenFilter::instance());
    }

    /**
     * returns login uri
     *
     * @param   \stubbles\input\web\WebRequest  $request
     * @return  string|\stubbles\peer\http\HttpUri
     */
    public function loginUri(WebRequest $request)
    {
        return $this->loginProvider->loginUri($request);
    }
}
