<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\auth;
/**
 * Exception to be thrown if an auth provider runs into a problem which is not
 * caused by an upstream service.
 *
 * @since  5.0.0
 */
class InternalAuthProviderException extends AuthProviderException
{
    /**
     * constructor
     *
     * @param  string      $message
     * @param  \Exception  $cause    optional
     */
    public function __construct($message, \Exception $cause = null)
    {
        parent::__construct($message, $cause, AuthProviderException::INTERNAL);

    }
}
