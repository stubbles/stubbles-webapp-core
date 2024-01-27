<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\auth\session;

use stubbles\peer\http\HttpUri;
use stubbles\webapp\Request;
use stubbles\webapp\auth\AuthenticationProvider;
use stubbles\webapp\auth\User;
use stubbles\webapp\session\Session;
/**
 * Authentication provider which caches the user within the session.
 *
 * @since  5.0.0
 */
class CachingAuthenticationProvider implements AuthenticationProvider
{
    /**
     * @Named{authenticationProvider}('original')
     */
    public function __construct(
        private Session $session,
        private AuthenticationProvider $authenticationProvider
    ) { }

    public function authenticate(Request $request): ?User
    {
        if ($this->session->hasValue(User::SESSION_KEY)) {
            return $this->session->value(User::SESSION_KEY);
        }

        $user = $this->authenticationProvider->authenticate($request);
        if (null === $user) {
            return null;
        }

        $this->session->putValue(User::SESSION_KEY, $user);
        return $user;
    }

    public function loginUri(Request $request): string|HttpUri
    {
        return $this->authenticationProvider->loginUri($request);
    }

    /**
     * @since   8.0.0
     * @return  string[]
     */
    public function challengesFor(Request $request): array
    {
        return $this->authenticationProvider->challengesFor($request);
    }
}
