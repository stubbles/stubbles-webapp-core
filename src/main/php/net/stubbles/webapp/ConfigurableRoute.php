<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp;
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
     * @param   string|\Closure  $preInterceptor
     * @return  ConfigurableRoute
     */
    public function preIntercept($preInterceptor);

    /**
     * add a post interceptor for this route
     *
     * @param   string|\Closure  $postInterceptor
     * @return  ConfigurableRoute
     */
    public function postIntercept($postInterceptor);

    /**
     * make route only available via ssl
     *
     * @return  ConfigurableRoute
     */
    public function httpsOnly();

    /**
     * adds a role which is only available via ssl
     *
     * @param   string  $role
     * @return  ConfigurableRoute
     */
    public function withRoleOnly($role);

    /**
     * add a mime type which this route supports
     *
     * @param   string  $mimeType
     * @return  ConfigurableRoute
     */
    public function supportsMimeType($mimeType);
}
?>