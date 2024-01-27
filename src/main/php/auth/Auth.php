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
     * login provider to use if authentication provider has no own means of login
     *
     * @var  class-string<AuthenticationProvider>
     */
    private ?string $loginProvider = null;
    /**
     * @var  class-string<TokenStore>
     */
    private ?string $tokenStore = null;
    private bool $enableSessionCaching = false;

    /**
     * constructor
     *
     * @param  class-string<AuthenticationProvider>  $authenticationProvider
     * @param  class-string<AuthorizationProvider>   $authorizationProvider
     */
    public function __construct(
        private string $authenticationProvider,
        private ?string $authorizationProvider = null
    ) { }

    /**
     * factory method
     *
     * @param   class-string<AuthenticationProvider>  $authenticationProvider
     * @param   class-string<AuthorizationProvider>   $authorizationProvider
     */
    public static function with(
        string $authenticationProvider,
        string $authorizationProvider = null
    ): self {
        return new self($authenticationProvider, $authorizationProvider);
    }

    /**
     * @param   class-string<TokenStore>              $tokenStore             class which stores tokens
     * @param   class-string<AuthenticationProvider>  $loginProvider          login provider as token authenticator has no login means
     * @param   class-string<AuthorizationProvider>   $authorizationProvider 
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
     */
    public function enableSessionCaching(): self
    {
        $this->enableSessionCaching = true;
        return $this;
    }

    public function configure(Binder $binder, string $projectPath = null): void
    {
        if ($this->enableSessionCaching) {
            $this->configureWithSessionCaching($binder);
        } else {
            $this->configureWithoutSessionCaching($binder);
        }

        if (null !== $this->tokenStore) {
            $this->configureTokenStore($binder);
        }
    }

    private function configureWithSessionCaching(Binder $binder): void
    {
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
    }

    private function configureWithoutSessionCaching(Binder $binder): void
    {
        $binder->bind(AuthenticationProvider::class)
            ->to($this->authenticationProvider);
        if (null !== $this->authorizationProvider) {
            $binder->bind(AuthorizationProvider::class)
                ->to($this->authorizationProvider);
        }
    }

    private function configureTokenStore(Binder $binder): void
    {
        $binder->bind(AuthenticationProvider::class)
            ->named('stubbles.webapp.auth.token.loginProvider')
            ->to($this->loginProvider);
        $binder->bind(TokenStore::class)
            ->to($this->tokenStore);
    }
}
