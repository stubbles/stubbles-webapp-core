<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\auth;
use net\stubbles\lang\BaseObject;
use net\stubbles\lang\exception\RuntimeException;
use net\stubbles\webapp\Processor;
use net\stubbles\webapp\ProcessorException;
use net\stubbles\webapp\UriRequest;
use net\stubbles\webapp\response\Response;
/**
 * Processor to handle authentication and authorization on websites.
 */
class AuthProcessor extends BaseObject implements Processor
{
    /**
     * decorated processor instance
     *
     * @type  Processor
     */
    private $processor;
    /**
     * the created response
     *
     * @type  Response
     */
    private $response;
    /**
     * auth configuration
     *
     * @type  AuthConfiguration
     */
    private $authConfig;
    /**
     * auth handler
     *
     * @type  AuthHandler
     */
    private $authHandler;
    /**
     * switch whether the processor's process() method can be called
     *
     * @type  bool
     */
    private $authorized = false;

    /**
     * constructor
     *
     * @param  Processor          $processor
     * @param  Response           $response
     * @param  AuthConfiguration  $authConfig
     * @param  AuthHandler        $authHandler
     */
    public function __construct(Processor $processor,
                                Response $response,
                                AuthConfiguration $authConfig,
                                AuthHandler $authHandler)
    {
        $this->processor   = $processor;
        $this->response    = $response;
        $this->authConfig  = $authConfig;
        $this->authHandler = $authHandler;
    }

    /**
     * operations to be done before the request is processed
     *
     * @param   UriRequest  $uriRequest
     * @return  Processor
     * @throws  ProcessorException
     * @throws  RuntimeException
     */
    public function startup(UriRequest $uriRequest)
    {
        $requiredRole = $this->authConfig->getRequiredRole($uriRequest);
        if (null !== $requiredRole && !$this->authHandler->userHasRole($requiredRole)) {
            if (!$this->authHandler->hasUser() && $this->authHandler->requiresLogin($requiredRole)) {
                $this->response->redirect($this->authHandler->getLoginUri());
            } elseif ($this->authHandler->hasUser()) {
                throw new ProcessorException(403, 'Forbidden');
            } else {
                throw new RuntimeException('Role is required but there is no user and the role requires no login - most likely the auth handler is errounous.');
            }
        } else {
            $this->authorized = true;
            $this->processor->startup($uriRequest);
        }

        return $this;
    }

    /**
     * checks whether the current request forces ssl or not
     *
     * @param   UriRequest  $uriRequest
     * @return  bool
     */
    public function requiresSsl(UriRequest $uriRequest)
    {
        if (false === $this->authorized) {
            return false;
        }

        if ($this->authConfig->requiresSsl($uriRequest)) {
            return true;
        }

        return $this->processor->requiresSsl($uriRequest);
    }

    /**
     * processes the request
     *
     * @return  Processor
     */
    public function process()
    {
        if (true === $this->authorized) {
            $this->processor->process();
        }

        return $this;
    }

    /**
     * operations to be done after the request was processed
     *
     * @return  Processor
     */
    public function cleanup()
    {
        if (true === $this->authorized) {
            $this->processor->cleanup();
        }

        return $this;
    }
}
?>