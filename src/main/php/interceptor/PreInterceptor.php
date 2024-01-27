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
 * interface for pre interceptors.
 *
 * Preinterceptors are called after all initializations have been done and
 * before processing of data starts.
 */
interface PreInterceptor
{
    public function preProcess(Request $request, Response $response): bool;
}
