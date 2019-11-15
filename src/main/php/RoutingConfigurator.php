<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp;
use stubbles\webapp\htmlpassthrough\HtmlFilePassThrough;
use stubbles\webapp\routing\ConfigurableRoute;
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
     * @param   string                                    $path   path this route is applicable for
     * @param   string|callable|\stubbles\webapp\Target  $target  code to be executed when the route is active
     * @return  \stubbles\webapp\routing\ConfigurableRoute
     */
    public function onGet(string $path, $target): ConfigurableRoute;

    /**
     * reply with HTML file stored in pages path
     *
     * @param   string                                   $path    optional  path this route is applicable for
     * @param   string|callable|\stubbles\webapp\Target  $target  optional  code to be executed when the route is active
     * @return  \stubbles\webapp\routing\ConfigurableRoute
     * @since   4.0.0
     */
    public function passThroughOnGet(
            string $path = '/[a-zA-Z0-9-_]+.html$',
            $target = HtmlFilePassThrough::class
    ): ConfigurableRoute;

    /**
     * reply with API index overview
     *
     * @param   string  $path
     * @return  \stubbles\webapp\routing\ConfigurableRoute
     * @since   6.1.0
     */
    public function apiIndexOnGet(string $path): ConfigurableRoute;


    /**
     * reply with a redirect
     *
     * If the given $target is a string it is used in different ways:
     * - if the string starts with http it is assumed to be a complete uri
     * - else it is assumed to be a path within the application
     *
     * @param   string                              $path        path this route is applicable for
     * @param   string|\stubbles\peer\http\HttpUri  $target      path or uri to redirect to
     * @param   int                                 $statusCode  optional  status code for redirect, defaults to 302
     * @return  \stubbles\webapp\routing\ConfigurableRoute
     * @since   6.1.0
     */
    public function redirectOnGet(string $path, $target, int $statusCode = 302): ConfigurableRoute;

    /**
     * reply with given class or callable for HEAD request on given path
     *
     * @param   string                                   $path    path this route is applicable for
     * @param   string|callable|\stubbles\webapp\Target  $target  code to be executed when the route is active
     * @return  \stubbles\webapp\routing\ConfigurableRoute
     */
    public function onHead(string $path, $target): ConfigurableRoute;

    /**
     * reply with given class or callable for POST request on given path
     *
     * @param   string                                   $path    path this route is applicable for
     * @param   string|callable|\stubbles\webapp\Target  $target  code to be executed when the route is active
     * @return  \stubbles\webapp\routing\ConfigurableRoute
     */
    public function onPost(string $path, $target): ConfigurableRoute;

    /**
     * reply with given class or callable for PUT request on given path
     *
     * @param   string                                   $path    path this route is applicable for
     * @param   string|callable|\stubbles\webapp\Target  $target  code to be executed when the route is active
     * @return  \stubbles\webapp\routing\ConfigurableRoute
     */
    public function onPut(string $path, $target): ConfigurableRoute;

    /**
     * reply with given class or callable for DELETE request on given path
     *
     * @param   string                                   $path    path this route is applicable for
     * @param   string|callable|\stubbles\webapp\Target  $target  code to be executed when the route is active
     * @return  \stubbles\webapp\routing\ConfigurableRoute
     */
    public function onDelete(string $path, $target): ConfigurableRoute;

    /**
     * reply with given class or callable for given request method(s) on given path
     *
     * If no request method(s) specified it replies to request methods GET, HEAD,
     * POST, PUT and DELETE.
     *
     * @param   string                                   $path           path this route is applicable for
     * @param   string|callable|\stubbles\webapp\Target  $target         code to be executed when the route is active
     * @param   string|string[]                          $requestMethod  optional  request method(s) this route is applicable for
     * @return  \stubbles\webapp\routing\ConfigurableRoute
     * @since   4.0.0
     */
    public function onAll(string $path, $target, $requestMethod = null): ConfigurableRoute;

    /**
     * add a route definition
     *
     * @param   \stubbles\webapp\routing\Route  $route
     * @return  \stubbles\webapp\routing\ConfigurableRoute
     */
    public function addRoute(Route $route): ConfigurableRoute;

    /**
     * pre intercept with given class or callable on all GET requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PreInterceptor  $preInterceptor  pre interceptor to add
     * @param   string                                                       $path            optional  path for which pre interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function preInterceptOnGet($preInterceptor, string $path = null): self;

    /**
     * pre intercept with given class or callable on all HEAD requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PreInterceptor  $preInterceptor  pre interceptor to add
     * @param   string                                                       $path            optional  path for which pre interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function preInterceptOnHead($preInterceptor, string $path = null): self;

    /**
     * pre intercept with given class or callable on all POST requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PreInterceptor  $preInterceptor  pre interceptor to add
     * @param   string                                                       $path            optional  path for which pre interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function preInterceptOnPost($preInterceptor, string $path = null): self;

    /**
     * pre intercept with given class or callable on all PUT requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PreInterceptor  $preInterceptor  pre interceptor to add
     * @param   string                                                       $path            optional  path for which pre interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function preInterceptOnPut($preInterceptor, string $path = null): self;

    /**
     * pre intercept with given class or callable on all DELETE requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PreInterceptor  $preInterceptor  pre interceptor to add
     * @param   string                                                       $path            optional  path for which pre interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function preInterceptOnDelete($preInterceptor, string $path = null): self;

    /**
     * pre intercept with given class or callable on all requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PreInterceptor  $preInterceptor  pre interceptor to add
     * @param   string                                                       $path            optional  path for which pre interceptor should be executed
     * @param   string                                                       $requestMethod   optional  request method for which interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function preIntercept($preInterceptor, string $path = null, string $requestMethod = null): self;

    /**
     * post intercept with given class or callable on all GET requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PostInterceptor  $postInterceptor  post interceptor to add
     * @param   string                                                        $path             optional  path for which post interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function postInterceptOnGet($postInterceptor, string $path = null): self;

    /**
     * post intercept with given class or callable on all HEAD requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PostInterceptor  $postInterceptor  post interceptor to add
     * @param   string                                                        $path             optional  path for which post interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function postInterceptOnHead($postInterceptor, string $path = null): self;

    /**
     * post intercept with given class or callable on all POST requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PostInterceptor  $postInterceptor  post interceptor to add
     * @param   string                                                        $path             optional  path for which post interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function postInterceptOnPost($postInterceptor, string $path = null): self;

    /**
     * post intercept with given class or callable on all PUT requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PostInterceptor  $postInterceptor  post interceptor to add
     * @param   string                                                        $path             optional  path for which post interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function postInterceptOnPut($postInterceptor, string $path = null): self;

    /**
     * post intercept with given class or callable on all DELETE requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PostInterceptor  $postInterceptor  post interceptor to add
     * @param   string                                                        $path             optional  path for which post interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function postInterceptOnDelete($postInterceptor, string $path = null): self;

    /**
     * post intercept with given class or callable on all requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PostInterceptor  $postInterceptor  post interceptor to add
     * @param   string                                                        $path             optional  path for which post interceptor should be executed
     * @param   string                                                        $requestMethod    optional  request method for which interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function postIntercept($postInterceptor, string $path = null, string $requestMethod = null): self;

    /**
     * sets a default mime type class for given mime type, but doesn't mark the mime type as supported for all routes
     *
     * @param   string  $mimeType       mime type to set default class for
     * @param   string  $mimeTypeClass  class to use
     * @return  \stubbles\webapp\RoutingConfigurator
     * @since   5.1.0
     */
    public function setDefaultMimeTypeClass(string $mimeType, $mimeTypeClass): self;

    /**
     * add a supported mime type
     *
     * @param   string  $mimeType
     * @param   string  $mimeTypeClass  optional  special class to be used for given mime type on this route
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function supportsMimeType(string $mimeType, $mimeTypeClass = null): self;

    /**
     * disables content negotation
     *
     * @return  \stubbles\webapp\RoutingConfigurator
     * @since   2.1.1
     */
    public function disableContentNegotiation(): self;
}
