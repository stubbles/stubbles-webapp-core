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
     * @param   string|callable  $preInterceptor
     * @return  RoutingConfigurator
     */
    public function preInterceptOnGet($preInterceptor);

    /**
     * pre intercept with given class or callable on all HEAD requests
     *
     * @param   string|callable  $preInterceptor
     * @return  RoutingConfigurator
     */
    public function preInterceptOnHead($preInterceptor);

    /**
     * pre intercept with given class or callable on all POST requests
     *
     * @param   string|callable  $preInterceptor
     * @return  RoutingConfigurator
     */
    public function preInterceptOnPost($preInterceptor);

    /**
     * pre intercept with given class or callable on all PUT requests
     *
     * @param   string|callable  $preInterceptor
     * @return  RoutingConfigurator
     */
    public function preInterceptOnPut($preInterceptor);

    /**
     * pre intercept with given class or callable on all DELETE requests
     *
     * @param   string|callable  $preInterceptor
     * @return  RoutingConfigurator
     */
    public function preInterceptOnDelete($preInterceptor);

    /**
     * pre intercept with given class or callable on all requests
     *
     * @param   string|Closure  $preInterceptor  pre interceptor to add
     * @param   string          $requestMethod   request method for which interceptor should be executed
     * @return  RoutingConfigurator
     */
    public function preIntercept($preInterceptor, $requestMethod = null);

    /**
     * post intercept with given class or callable on all GET requests
     *
     * @param   string|callable  $postInterceptor
     * @return  RoutingConfigurator
     */
    public function postInterceptOnGet($postInterceptor);

    /**
     * post intercept with given class or callable on all HEAD requests
     *
     * @param   string|callable  $postInterceptor
     * @return  RoutingConfigurator
     */
    public function postInterceptOnHead($postInterceptor);

    /**
     * post intercept with given class or callable on all POST requests
     *
     * @param   string|callable  $postInterceptor
     * @return  RoutingConfigurator
     */
    public function postInterceptOnPost($postInterceptor);

    /**
     * post intercept with given class or callable on all PUT requests
     *
     * @param   string|callable  $postInterceptor
     * @return  RoutingConfigurator
     */
    public function postInterceptOnPut($postInterceptor);

    /**
     * post intercept with given class or callable on all DELETE requests
     *
     * @param   string|callable  $postInterceptor
     * @return  RoutingConfigurator
     */
    public function postInterceptOnDelete($postInterceptor);

    /**
     * post intercept with given class or callable on all requests
     *
     * @param   string|Closure  $postInterceptor  post interceptor to add
     * @param   string          $requestMethod    request method for which interceptor should be executed
     * @return  RoutingConfigurator
     */
    public function postIntercept($postInterceptor, $requestMethod = null);
}
?>