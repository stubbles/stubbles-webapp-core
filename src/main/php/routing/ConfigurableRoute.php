<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\routing;
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
     *
     * @param   string|callback|\stubbles\webapp\interceptor\PreInterceptor  $preInterceptor
     * @return  \stubbles\webapp\routing\ConfigurableRoute
     */
    public function preIntercept($preInterceptor);

    /**
     * add a post interceptor for this route
     *
     * @param   string|callback|\stubbles\webapp\interceptor\PostInterceptor  $postInterceptor
     * @return  \stubbles\webapp\routing\ConfigurableRoute
     */
    public function postIntercept($postInterceptor);

    /**
     * make route only available via ssl
     *
     * @return  \stubbles\webapp\routing\ConfigurableRoute
     */
    public function httpsOnly();

    /**
     * makes route only available if a user is logged in
     *
     * @return  \stubbles\webapp\routing\ConfigurableRoute
     * @since   3.0.0
     */
    public function withLoginOnly();

    /**
     * forbid the actual login
     *
     * Forbidding a login means that the user receives a 403 Forbidden response
     * in case he accesses a restricted resource but is not logged in yet.
     * Otherwise, he would just be redirected to the login uri of the
     * authentication provider.
     *
     * @return  \stubbles\webapp\routing\ConfigurableRoute
     * @since   5.0.0
     */
    public function forbiddenWhenNotAlreadyLoggedIn();

    /**
     * adds a role which is only available via ssl
     *
     * @param   string  $role
     * @return  \stubbles\webapp\routing\ConfigurableRoute
     */
    public function withRoleOnly($role);

    /**
     * add a mime type which this route supports
     *
     * @param   string  $mimeType
     * @return  \stubbles\webapp\routing\ConfigurableRoute
     */
    public function supportsMimeType($mimeType);

    /**
     * disables content negotation
     *
     * @return  \stubbles\webapp\routing\ConfigurableRoute
     * @since   2.1.1
     */
    public function disableContentNegotiation();

    /**
     * hides route in API index
     *
     * @return  \stubbles\webapp\routing\ConfigurableRoute
     * @since   6.1.0
     */
    public function excludeFromApiIndex();
}
