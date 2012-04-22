<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\ioc;
use net\stubbles\ioc\Binder;
use net\stubbles\ioc\module\BindingModule;
use net\stubbles\lang\BaseObject;
use net\stubbles\webapp\UriConfigurator;
use net\stubbles\webapp\auth\AuthConfiguration;
/**
 * Binding module for web applications.
 *
 * @since  1.7.0
 */
class WebAppBindingModule extends BaseObject implements BindingModule
{
    /**
     * url configuration
     *
     * @type  UriConfigurator
     */
    private $uriConfigurator;
    /**
     * auth configuration
     *
     * @type  AuthConfigurator
     */
    private $authConfigurator;

    /**
     * constructor
     *
     * @param  UriConfigurator  $uriConfigurator
     */
    public function __construct(UriConfigurator $uriConfigurator)
    {
        $this->uriConfigurator = $uriConfigurator;
    }

    /**
     * static constructor
     *
     * @param   UriConfigurator      $uriConfig
     * @return  WebAppBindingModule
     */
    public static function create(UriConfigurator $uriConfig)
    {
        return new self($uriConfig);
    }

    /**
     * enable auth processor
     *
     * @return  AuthConfigurator
     */
    public function enableAuth()
    {
        $this->authConfigurator = new AuthConfiguration();
        return $this->authConfigurator;
    }

    /**
     * configure the binder
     *
     * @param  Binder  $binder
     */
    public function configure(Binder $binder)
    {
        if (null !== $this->authConfigurator) {
            $binder->bind('net\\stubbles\\webapp\\auth\\AuthConfiguration')
                   ->toInstance($this->authConfigurator);
        }

        $binder->bind('net\\stubbles\\webapp\\UriConfiguration')
               ->toInstance($this->uriConfigurator->getConfig());
        $binder->bindConstant('net.stubbles.webapp.resource.handler')
               ->to($this->uriConfigurator->getResourceHandler());
        $binder->bindConstant('net.stubbles.webapp.resource.mime.types')
               ->to($this->uriConfigurator->getResourceMimeTypes());
    }
}
?>