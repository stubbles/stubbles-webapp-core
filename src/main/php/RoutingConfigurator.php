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
use stubbles\webapp\routing\Route;
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
     * @param   string                                      $path      path this route is applicable for
     * @param   string|callable|\stubbles\webapp\Processor  $callback  code to be executed when the route is active
     * @return  \stubbles\webapp\routing\ConfigurableRoute
     */
    public function onGet($path, $callback);

    /**
     * reply with HTML file stored in pages path
     *
     * @param   string                                      $path      optional  path this route is applicable for
     * @param   string|callable|\stubbles\webapp\Processor  $callback  optional  code to be executed when the route is active
     * @return  \stubbles\webapp\routing\ConfigurableRoute
     * @since   4.0.0
     */
    public function passThroughOnGet($path = '/*\.html$', $callback = 'stubbles\webapp\processor\HtmlFilePassThrough');

    /**
     * reply with given class or callable for HEAD request on given path
     *
     * @param   string                                      $path      path this route is applicable for
     * @param   string|callable|\stubbles\webapp\Processor  $callback  code to be executed when the route is active
     * @return  \stubbles\webapp\routing\ConfigurableRoute
     */
    public function onHead($path, $callback);

    /**
     * reply with given class or callable for POST request on given path
     *
     * @param   string                                      $path      path this route is applicable for
     * @param   string|callable|\stubbles\webapp\Processor  $callback  code to be executed when the route is active
     * @return  \stubbles\webapp\routing\ConfigurableRoute
     */
    public function onPost($path, $callback);

    /**
     * reply with given class or callable for PUT request on given path
     *
     * @param   string                                      $path      path this route is applicable for
     * @param   string|callable|\stubbles\webapp\Processor  $callback  code to be executed when the route is active
     * @return  \stubbles\webapp\routing\ConfigurableRoute
     */
    public function onPut($path, $callback);

    /**
     * reply with given class or callable for DELETE request on given path
     *
     * @param   string                                      $path      path this route is applicable for
     * @param   string|callable|\stubbles\webapp\Processor  $callback  code to be executed when the route is active
     * @return  \stubbles\webapp\routing\ConfigurableRoute
     */
    public function onDelete($path, $callback);

    /**
     * reply with given class or callable for given request method(s) on given path
     *
     * If no request method(s) specified it replies to request methods GET, HEAD,
     * POST, PUT and DELETE.
     *
     * @param   string                                      $path           path this route is applicable for
     * @param   string|callable|\stubbles\webapp\Processor  $callback       code to be executed when the route is active
     * @param   string|string[]                             $requestMethod  optional  request method(s) this route is applicable for
     * @return  \stubbles\webapp\ConfigurableRoute
     * @since   4.0.0
     */
    public function onAll($path, $callback, $requestMethod = null);

    /**
     * add a route definition
     *
     * @param   \stubbles\webapp\Route  $route
     * @return  \stubbles\webapp\ConfigurableRoute
     */
    public function addRoute(Route $route);

    /**
     * pre intercept with given class or callable on all GET requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PreInterceptor  $preInterceptor  pre interceptor to add
     * @param   string                                                       $path            optional  path for which pre interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function preInterceptOnGet($preInterceptor, $path = null);

    /**
     * pre intercept with given class or callable on all HEAD requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PreInterceptor  $preInterceptor  pre interceptor to add
     * @param   string                                                       $path            optional  path for which pre interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function preInterceptOnHead($preInterceptor, $path = null);

    /**
     * pre intercept with given class or callable on all POST requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PreInterceptor  $preInterceptor  pre interceptor to add
     * @param   string                                                       $path            optional  path for which pre interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function preInterceptOnPost($preInterceptor, $path = null);

    /**
     * pre intercept with given class or callable on all PUT requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PreInterceptor  $preInterceptor  pre interceptor to add
     * @param   string                                                       $path            optional  path for which pre interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function preInterceptOnPut($preInterceptor, $path = null);

    /**
     * pre intercept with given class or callable on all DELETE requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PreInterceptor  $preInterceptor  pre interceptor to add
     * @param   string                                                       $path            optional  path for which pre interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function preInterceptOnDelete($preInterceptor, $path = null);

    /**
     * pre intercept with given class or callable on all requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PreInterceptor  $preInterceptor  pre interceptor to add
     * @param   string                                                       $path            optional  path for which pre interceptor should be executed
     * @param   string                                                       $requestMethod   optional  request method for which interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     * @throws  \stubbles\lang\exception\IllegalArgumentException
     */
    public function preIntercept($preInterceptor, $path = null, $requestMethod = null);

    /**
     * post intercept with given class or callable on all GET requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PostInterceptor  $postInterceptor  post interceptor to add
     * @param   string                                                        $path             optional  path for which post interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function postInterceptOnGet($postInterceptor, $path = null);

    /**
     * post intercept with given class or callable on all HEAD requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PostInterceptor  $postInterceptor  post interceptor to add
     * @param   string                                                        $path             optional  path for which post interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function postInterceptOnHead($postInterceptor, $path = null);

    /**
     * post intercept with given class or callable on all POST requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PostInterceptor  $postInterceptor  post interceptor to add
     * @param   string                                                        $path             optional  path for which post interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function postInterceptOnPost($postInterceptor, $path = null);

    /**
     * post intercept with given class or callable on all PUT requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PostInterceptor  $postInterceptor  post interceptor to add
     * @param   string                                                        $path             optional  path for which post interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function postInterceptOnPut($postInterceptor, $path = null);

    /**
     * post intercept with given class or callable on all DELETE requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PostInterceptor  $postInterceptor  post interceptor to add
     * @param   string                                                        $path             optional  path for which post interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function postInterceptOnDelete($postInterceptor, $path = null);

    /**
     * post intercept with given class or callable on all requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PostInterceptor  $postInterceptor  post interceptor to add
     * @param   string                                                        $path             optional  path for which post interceptor should be executed
     * @param   string                                                        $requestMethod    optional  request method for which interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     * @throws  IllegalArgumentException
     */
    public function postIntercept($postInterceptor, $path = null, $requestMethod = null);

    /**
     * sets a default mime type class for given mime type, but doesn't mark the mime type as supported for all routes
     *
     * @param   string  $mimeType       mime type to set default class for
     * @param   string  $mimeTypeClass  class to use
     * @return  \stubbles\webapp\routing\Routing
     * @since   5.1.0
     */
    public function setDefaultMimeTypeClass($mimeType, $mimeTypeClass);

    /**
     * add a supported mime type
     *
     * @param   string  $mimeType
     * @param   string  $mimeTypeClass  optional  special class to be used for given mime type on this route
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function supportsMimeType($mimeType, $mimeTypeClass = null);

    /**
     * disables content negotation
     *
     * @return  \stubbles\webapp\RoutingConfigurator
     * @since   2.1.1
     */
    public function disableContentNegotiation();
}
