<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing;
use stubbles\peer\http\HttpUri;
use stubbles\webapp\Request;
use stubbles\webapp\Response;
/**
 * Contains logic to process the route.
 *
 * @since  2.0.0
 */
interface UriResource
{
    /**
     * checks whether switch to https is required
     */
    public function requiresHttps(): bool;

    /**
     * returns https uri of current route
     */
    public function httpsUri(): HttpUri;

    /**
     * negotiates proper mime type for given request
     *
     * @since  6.0.0
     */
    public function negotiateMimeType(Request $request, Response $response): bool;

    /**
     * returns list of supported mime types
     *
     * @return  string[]
     */
    public function supportedMimeTypes(): array;

    /**
     * apply pre interceptors
     *
     * Returns false if one of the pre interceptors cancels the request.
     */
    public function applyPreInterceptors(Request $request, Response $response): bool;

    /**
     * returns the resource data model
     */
    public function resolve(Request $request, Response $response): mixed;

    /**
     * apply post interceptors
     */
    public function applyPostInterceptors(Request $request, Response $response): bool;
}
