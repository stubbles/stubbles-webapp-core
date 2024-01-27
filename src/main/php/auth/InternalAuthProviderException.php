<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\auth;

use Throwable;

/**
 * Exception to be thrown if an auth provider runs into a problem which is not
 * caused by an upstream service.
 *
 * @since  5.0.0
 */
class InternalAuthProviderException extends AuthProviderException
{
    public function __construct(string $message, Throwable $cause = null)
    {
        parent::__construct($message, $cause, AuthProviderException::INTERNAL);
    }
}
