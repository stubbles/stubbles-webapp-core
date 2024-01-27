<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\auth\token;

use Exception;
use stubbles\peer\http\HttpUri;
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
     * @Property{tokenSalt}('stubbles.webapp.auth.token.salt')
     * @Named{loginProvider}('stubbles.webapp.auth.token.loginProvider')
     */
    public function __construct(
            private TokenStore $tokenStore,
            private string $tokenSalt,
            private AuthenticationProvider $loginProvider
    ) { }

    /**
     * authenticates that the given request is valid
     *
     * @throws  InternalAuthProviderException
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
        } catch (Exception $e) {
            throw new InternalAuthProviderException(
                'Error while trying to find user by token: ' . $e->getMessage(),
                $e
            );
        }
    }

    /**
     * performs login when token not found or invalid
     *
     * @throws  InternalAuthProviderException
     */
    private function login(Request $request): ?User
    {
        $user = $this->loginProvider->authenticate($request);
        if (null !== $user) {
            try {
                $token = $user->createToken($this->tokenSalt);
                $this->tokenStore->store($request, $token, $user);
                return $user;
            } catch (Exception $e) {
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
     */
    private function readToken(Request $request): ?Token
    {
        if (!$request->hasRedirectHeader('HTTP_AUTHORIZATION')) {
            return null;
        }

        return $request->readRedirectHeader('HTTP_AUTHORIZATION')
            ->withFilter(TokenFilter::instance());
    }

    public function loginUri(Request $request): string|HttpUri
    {
        return $this->loginProvider->loginUri($request);
    }

    /**
     * @since   8.0.0
     * @return  string[]
     */
    public function challengesFor(Request $request): array
    {
        return $this->loginProvider->challengesFor($request);
    }
}
