<?php
declare(strict_types=1);
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
 * Exception to be thrown if an upstream service an auth provider uses does not
 * behave as expected.
 *
 * @since  5.0.0
 */
class ExternalAuthProviderException extends AuthProviderException
{
    /**
     * constructor
     *
     * @param  string      $message
     * @param  \Exception  $cause    optional
     */
    public function __construct(string $message, \Exception $cause = null)
    {
        parent::__construct($message, $cause, AuthProviderException::EXTERNAL);

    }
}
