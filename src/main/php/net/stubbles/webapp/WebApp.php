<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp;
use net\stubbles\ioc\App;
use net\stubbles\lang\BaseObject;
use net\stubbles\webapp\ioc\IoBindingModule;
use net\stubbles\webapp\ioc\WebAppBindingModule;
/**
 * Abstract base class for web applications.
 *
 * @since  1.7.0
 */
class WebApp extends App
{
    /**
     * front controller
     *
     * @type  WebAppFrontController
     */
    protected $webAppFrontController;

    /**
     * constructor
     *
     * @param  WebAppFrontController  $webAppFrontController
     * @Inject
     */
    public function  __construct(WebAppFrontController $webAppFrontController)
    {
        $this->webAppFrontController = $webAppFrontController;
    }

    /**
     * runs the application
     */
    public function run()
    {
        $this->webAppFrontController->process();
    }

    /**
     * creates io binding module with session
     *
     * @param   string  $sessionName
     * @return  IoBindingModule
     */
    protected static function createIoBindingModuleWithSession($sessionName = 'PHPSESSID')
    {
        return IoBindingModule::createWithSession($sessionName);
    }

    /**
     * creates io binding module without session
     *
     * @return  IoBindingModule
     */
    protected static function createIoBindingModuleWithoutSession()
    {
        return IoBindingModule::createWithoutSession();
    }

    /**
     * creates web app binding module
     *
     * @param   UriConfigurator  $uriConfigurator
     * @return  WebAppBindingModule
     */
    protected static function createWebAppBindingModule(UriConfigurator $uriConfigurator)
    {
        return WebAppBindingModule::create($uriConfigurator);
    }

    /**
     * creates uri configurator with xml processor as default
     *
     * @param   string|Closure  $defaultProcessor  class name of fallback processor
     * @return  UriConfigurator
     */
    protected static function createUriConfigurator($defaultProcessor)
    {
        return UriConfigurator::create($defaultProcessor);
    }

    /**
     * creates uri configurator with xml processor as default
     *
     * @return  UriConfigurator
     */
    protected static function createRestUriConfigurator()
    {
        return UriConfigurator::createWithRestProcessorAsDefault();
    }
}
?>