<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\auth;
use stubbles\ioc\Binder;
use stubbles\ioc\module\BindingModule;
use stubbles\webapp\auth\session\CachingAuthenticationProvider;
use stubbles\webapp\auth\session\CachingAuthorizationProvider;
use stubbles\webapp\auth\token\TokenAuthenticator;
use stubbles\webapp\auth\token\TokenStore;
/**
 * Binds authentication and authorization providers.
 *
 * @since  5.0.0
 */
class Auth implements BindingModule
{
    /**
     * class name of authentication provider to use
     *
     * @var  class-string<AuthenticationProvider>
     */
    private $authenticationProvider;
    /**
     * login provider to use of authentication provider has no own means of login
     *
     * @var  class-string<AuthenticationProvider>
     */
    private $loginProvider;
    /**
     * class which stores tokens
     *
     * @var  class-string<TokenStore>
     */
    private $tokenStore;
    /**
     * class name of authorization provider to use
     *
     * @var  class-string<AuthorizationProvider>|null
     */
    private $authorizationProvider;
    /**
     * switch whether to enable session caching or not
     *
     * @var  bool
     */
    private $enableSessionCaching = false;

    /**
     * constructor
     *
     * @param  class-string<AuthenticationProvider>  $authenticationProvider
     * @param  class-string<AuthorizationProvider>   $authorizationProvider   optional
     */
    public function __construct(string $authenticationProvider, string $authorizationProvider = null)
    {
        $this->authenticationProvider = $authenticationProvider;
        $this->authorizationProvider  = $authorizationProvider;
    }

    /**
     * factory method
     *
     * @param   class-string<AuthenticationProvider>  $authenticationProvider
     * @param   class-string<AuthorizationProvider>   $authorizationProvider   optional
     * @return  self
     */
    public static function with(
            string $authenticationProvider,
            string $authorizationProvider = null
    ): self {
        return new self($authenticationProvider, $authorizationProvider);
    }

    /**
     *
     * @param   class-string<TokenStore>              $tokenStore             class which stores tokens
     * @param   class-string<AuthenticationProvider>  $loginProvider          login provider to use because token authenticator has no own means of a login
     * @param   class-string<AuthorizationProvider>   $authorizationProvider  optional
     * @return  self
     */
    public static function usingTokens(
            string $tokenStore,
            string $loginProvider,
            string $authorizationProvider = null
    ): self {
        $self = new self(TokenAuthenticator::class, $authorizationProvider);
        $self->loginProvider = $loginProvider;
        $self->tokenStore    = $tokenStore;
        return $self;
    }

    /**
     * enables session caching of authentication and authorization information
     *
     * @return  self
     */
    public function enableSessionCaching(): self
    {
        $this->enableSessionCaching = true;
        return $this;
    }

    /**
     * configure the binder
     *
     * @param  \stubbles\ioc\Binder  $binder
     * @param  string                $projectPath  optional  project base path
     */
    public function configure(Binder $binder, string $projectPath = null): void
    {
        if ($this->enableSessionCaching) {
            $binder->bind(AuthenticationProvider::class)
                   ->to(CachingAuthenticationProvider::class);
            $binder->bind(AuthenticationProvider::class)
               ->named('original')
               ->to($this->authenticationProvider);
            if (null !== $this->authorizationProvider) {
                $binder->bind(AuthorizationProvider::class)
                   ->to(CachingAuthorizationProvider::class);
                $binder->bind(AuthorizationProvider::class)
                       ->named('original')
                       ->to($this->authorizationProvider);
            }
        } else {
            $binder->bind(AuthenticationProvider::class)
               ->to($this->authenticationProvider);
            if (null !== $this->authorizationProvider) {
                $binder->bind(AuthorizationProvider::class)
                       ->to($this->authorizationProvider);
            }
        }

        if (null !== $this->tokenStore) {
            $binder->bind(AuthenticationProvider::class)
                   ->named('stubbles.webapp.auth.token.loginProvider')
                   ->to($this->loginProvider);
            $binder->bind(TokenStore::class)
                   ->to($this->tokenStore);
        }
    }
}
