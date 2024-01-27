<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\interceptor;
use stubbles\webapp\Request;
use stubbles\webapp\Response;
/**
 * Interface for post interceptors.
 *
 * Postinterceptors are called after all data processing is done. They can change
 * the response or add additional data to the response.
 */
interface PostInterceptor
{
    public function postProcess(Request $request, Response $response): bool;
}
