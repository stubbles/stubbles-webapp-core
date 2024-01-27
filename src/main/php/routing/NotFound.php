<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing;
use stubbles\webapp\Request;
use stubbles\webapp\Response;
use stubbles\webapp\response\Error;

/**
 * Represents a missing resource.
 *
 * @since  2.2.0
 */
class NotFound extends AbstractResource
{
    public function requiresHttps(): bool
    {
        return false;
    }

    public function resolve(Request $request, Response $response): Error
    {
        return $response->notFound();
    }
}
