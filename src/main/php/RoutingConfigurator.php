<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp;
/**
 * Contains routing information and decides which route is applicable for given request.
 *
 * @since  2.0.0
 * @api
 */
interface RoutingConfigurator
{

    /**
     * reply with given class or callable for GET request on given path
     *
     * @param   string           $path
     * @param   string|callable  $callback
     * @return  ConfigurableRoute
     */
    public function onGet($path, $callback);

    /**
     * reply with given class or callable for HEAD request on given path
     *
     * @param   string           $path
     * @param   string|callable  $callback
     * @return  ConfigurableRoute
     */
    public function onHead($path, $callback);

    /**
     * reply with given class or callable for POST request on given path
     *
     * @param   string           $path
     * @param   string|callable  $callback
     * @return  ConfigurableRoute
     */
    public function onPost($path, $callback);

    /**
     * reply with given class or callable for PUT request on given path
     *
     * @param   string           $path
     * @param   string|callable  $callback
     * @return  ConfigurableRoute
     */
    public function onPut($path, $callback);

    /**
     * reply with given class or callable for DELETE request on given path
     *
     * @param   string           $path
     * @param   string|callable  $callback
     * @return  ConfigurableRoute
     */
    public function onDelete($path, $callback);

    /**
     * add a route definition
     *
     * @param   Route  $route
     * @return  ConfigurableRoute
     */
    public function addRoute(Route $route);

    /**
     * pre intercept with given class or callable on all GET requests
     *
     * @param   string|callable|PreInterceptor  $preInterceptor  pre interceptor to add
     * @param   string                          $path            optional  path for which pre interceptor should be executed
     * @return  RoutingConfigurator
     */
    public function preInterceptOnGet($preInterceptor, $path = null);

    /**
     * pre intercept with given class or callable on all HEAD requests
     *
     * @param   string|callable|PreInterceptor  $preInterceptor  pre interceptor to add
     * @param   string                          $path            optional  path for which pre interceptor should be executed
     * @return  RoutingConfigurator
     */
    public function preInterceptOnHead($preInterceptor, $path = null);

    /**
     * pre intercept with given class or callable on all POST requests
     *
     * @param   string|callable|PreInterceptor  $preInterceptor  pre interceptor to add
     * @param   string                          $path            optional  path for which pre interceptor should be executed
     * @return  RoutingConfigurator
     */
    public function preInterceptOnPost($preInterceptor, $path = null);

    /**
     * pre intercept with given class or callable on all PUT requests
     *
     * @param   string|callable|PreInterceptor  $preInterceptor  pre interceptor to add
     * @param   string                          $path            optional  path for which pre interceptor should be executed
     * @return  RoutingConfigurator
     */
    public function preInterceptOnPut($preInterceptor, $path = null);

    /**
     * pre intercept with given class or callable on all DELETE requests
     *
     * @param   string|callable|PreInterceptor  $preInterceptor  pre interceptor to add
     * @param   string                          $path            optional  path for which pre interceptor should be executed
     * @return  RoutingConfigurator
     */
    public function preInterceptOnDelete($preInterceptor, $path = null);

    /**
     * pre intercept with given class or callable on all requests
     *
     * @param   string|callable|PreInterceptor  $preInterceptor  pre interceptor to add
     * @param   string                          $path            optional  path for which pre interceptor should be executed
     * @param   string                          $requestMethod   optional  request method for which interceptor should be executed
     * @return  RoutingConfigurator
     * @throws  IllegalArgumentException
     */
    public function preIntercept($preInterceptor, $path = null, $requestMethod = null);

    /**
     * post intercept with given class or callable on all GET requests
     *
     * @param   string|callable|PostInterceptor  $postInterceptor  post interceptor to add
     * @param   string                           $path             optional  path for which post interceptor should be executed
     * @return  RoutingConfigurator
     */
    public function postInterceptOnGet($postInterceptor, $path = null);

    /**
     * post intercept with given class or callable on all HEAD requests
     *
     * @param   string|callable|PostInterceptor  $postInterceptor  post interceptor to add
     * @param   string                           $path             optional  path for which post interceptor should be executed
     * @return  RoutingConfigurator
     */
    public function postInterceptOnHead($postInterceptor, $path = null);

    /**
     * post intercept with given class or callable on all POST requests
     *
     * @param   string|callable|PostInterceptor  $postInterceptor  post interceptor to add
     * @param   string                           $path             optional  path for which post interceptor should be executed
     * @return  RoutingConfigurator
     */
    public function postInterceptOnPost($postInterceptor, $path = null);

    /**
     * post intercept with given class or callable on all PUT requests
     *
     * @param   string|callable|PostInterceptor  $postInterceptor  post interceptor to add
     * @param   string                           $path             optional  path for which post interceptor should be executed
     * @return  RoutingConfigurator
     */
    public function postInterceptOnPut($postInterceptor, $path = null);

    /**
     * post intercept with given class or callable on all DELETE requests
     *
     * @param   string|callable|PostInterceptor  $postInterceptor  post interceptor to add
     * @param   string                           $path             optional  path for which post interceptor should be executed
     * @return  RoutingConfigurator
     */
    public function postInterceptOnDelete($postInterceptor, $path = null);

    /**
     * post intercept with given class or callable on all requests
     *
     * @param   string|callable|PostInterceptor  $postInterceptor  post interceptor to add
     * @param   string                           $path             optional  path for which post interceptor should be executed
     * @param   string                           $requestMethod    optional  request method for which interceptor should be executed
     * @return  RoutingConfigurator
     * @throws  IllegalArgumentException
     */
    public function postIntercept($postInterceptor, $path = null, $requestMethod = null);

    /**
     * add a supported mime type
     *
     * @param   string  $mimeType
     * @return  RoutingConfigurator
     */
    public function supportsMimeType($mimeType);

    /**
     * disables content negotation
     *
     * @return  RoutingConfigurator
     * @since   2.1.1
     */
    public function disableContentNegotiation();
}
