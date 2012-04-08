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
use net\stubbles\webapp\UriRequest;
/**
 * Contains auth configuration.
 *
 * @since  2.0.0
 */
class AuthConfiguration extends BaseObject
{
    /**
     * map of uris and roles
     *
     * @type  array
     */
    private $uriConfig = array();
    /**
     * list of uris which should be available with ssl only
     *
     * @type  string[]
     */
    private $sslUris   = array();

    /**
     * adds a role for given uri condition which is only available via ssl
     *
     * @api
     * @param   string  $uriCondition
     * @param   string  $role
     * @return  AuthConfiguration
     */
    public function addRole($uriCondition, $role)
    {
        $this->addNonSecureRole($uriCondition, $role);
        $this->sslUris[] = $uriCondition;
        return $this;
    }

    /**
     * adds a role for given uri condition which is available in non-ssl mode
     *
     * @api
     * @param   string  $uriCondition
     * @param   string  $role
     * @return  AuthConfiguration
     */
    public function addNonSecureRole($uriCondition, $role)
    {
        $this->uriConfig[$uriCondition] = $role;
        return $this;
    }

    /**
     * returns role required for given request
     *
     * If no role is required return value is null.
     *
     * @param   UriRequest  $uriRequest
     * @return  string
     */
    public function getRequiredRole(UriRequest $uriRequest)
    {
        foreach ($this->uriConfig as $uriCondition => $role) {
            if ($uriRequest->satisfies($uriCondition)) {
                return $role;
            }
        }

        return null;
    }

    /**
     * checks whether request requires ssl
     *
     * @param   UriRequest  $uriRequest
     * @return  bool
     */
    public function requiresSsl(UriRequest $uriRequest)
    {
        foreach ($this->sslUris as $uriCondition) {
            if ($uriRequest->satisfies($uriCondition)) {
                return true;
            }
        }

        return false;
    }
}
?>