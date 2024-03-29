<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing;

use stubbles\webapp\interceptor\PostInterceptor;
use stubbles\webapp\interceptor\PreInterceptor;

/**
 * Represents information about a route that can be called.
 *
 * @since  2.0.0
 * @api
 */
interface ConfigurableRoute
{
    /**
     * add a pre interceptor for this route
     */
    public function preIntercept(
        string|callable|PreInterceptor $preInterceptor
    ): self;

    /**
     * add a post interceptor for this route
     */
    public function postIntercept(
        string|callable|PostInterceptor $postInterceptor
    ): self;

    /**
     * make route only available via ssl
     */
    public function httpsOnly(): self;

    /**
     * makes route only available if a user is logged in
     *
     * @since  3.0.0
     */
    public function withLoginOnly(): self;

    /**
     * when user is not logged in respond with 401 Unauthorized
     *
     * Otherwise, the user would just be redirected to the login uri of the
     * authentication provider.
     *
     * @since  8.0.0
     */
    public function sendChallengeWhenNotLoggedIn(): self;

    /**
     * forbid the actual login
     *
     * @deprecated  use sendChallengeWhenNotLoggedIn() instead, will be removed with 9.0.0
     * @since  5.0.0
     */
    public function forbiddenWhenNotAlreadyLoggedIn(): self;

    /**
     * adds a role which is only available via ssl
     */
    public function withRoleOnly(string $role): self;

    /**
     * add a mime type which this route supports
     *
     * @param  string  $mimeType
     * @param  string  $class     special class to be used for given mime type on this route
     */
    public function supportsMimeType(string $mimeType, ?string $class = null): self;

    /**
     * disables content negotation
     *
     * @since  2.1.1
     */
    public function disableContentNegotiation(): self;

    /**
     * hides route in API index
     *
     * @since  6.1.0
     */
    public function excludeFromApiIndex(): self;
}
