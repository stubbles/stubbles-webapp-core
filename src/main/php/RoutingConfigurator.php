<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp;

use stubbles\peer\http\HttpUri;
use stubbles\webapp\htmlpassthrough\HtmlFilePassThrough;
use stubbles\webapp\interceptor\PostInterceptor;
use stubbles\webapp\interceptor\PreInterceptor;
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
     * @param  class-string<Target>|callable|Target  $target  code to be executed when the route is active
     */
    public function onGet(string $path, string|callable|Target $target): ConfigurableRoute;

    /**
     * reply with HTML file stored in pages path
     *
     * @param  class-string<Target>|callable|Target  $target  code to be executed when the route is active
     * @since  4.0.0
     */
    public function passThroughOnGet(
        string $path = '/[a-zA-Z0-9-_]+.html$',
        string|callable|Target $target = HtmlFilePassThrough::class
    ): ConfigurableRoute;

    /**
     * reply with API index overview
     *
     * @since  6.1.0
     */
    public function apiIndexOnGet(string $path): ConfigurableRoute;


    /**
     * reply with a redirect
     *
     * If the given $target is a string it is used in different ways:
     * - if the string starts with http it is assumed to be a complete uri
     * - else it is assumed to be a path within the application
     *
     * @since  6.1.0
     */
    public function redirectOnGet(
        string $path,
        string|HttpUri $target,
        int $statusCode = 302
    ): ConfigurableRoute;

    /**
     * reply with given class or callable for HEAD request on given path
     *
     * @param  class-string<Target>|callable|Target  $target  code to be executed when the route is active
     */
    public function onHead(string $path, string|callable|Target $target): ConfigurableRoute;

    /**
     * reply with given class or callable for POST request on given path
     *
     * @param  class-string<Target>|callable|Target  $target  code to be executed when the route is active
     */
    public function onPost(string $path, string|callable|Target $target): ConfigurableRoute;

    /**
     * reply with given class or callable for PUT request on given path
     *
     * @param  class-string<Target>|callable|Target  $target  code to be executed when the route is active
     */
    public function onPut(string $path, string|callable|Target $target): ConfigurableRoute;

    /**
     * reply with given class or callable for DELETE request on given path
     *
     * @param  class-string<Target>|callable|Target  $target  code to be executed when the route is active
     */
    public function onDelete(string $path, string|callable|Target $target): ConfigurableRoute;

    /**
     * reply with given class or callable for given request method(s) on given path
     *
     * If no request method(s) specified it replies to request methods GET, HEAD,
     * POST, PUT and DELETE.
     *
     * @param  class-string<Target>|callable|Target  $target  code to be executed when the route is active
     * @param  string|string[]                       $requestMethod  request method(s) this route is applicable for
     * @since  4.0.0
     */
    public function onAll(
        string $path,
        string|callable|Target $target,
        string|array|null $requestMethod = null
    ): ConfigurableRoute;

    /**
     * add a route definition
     */
    public function addRoute(Route $route): ConfigurableRoute;

    /**
     * pre intercept with given class or callable on all GET requests
     *
     * @param  class-string<PreInterceptor>|callable|PreInterceptor  $preInterceptor  pre interceptor to add
     */
    public function preInterceptOnGet(
        string|callable|PreInterceptor $preInterceptor,
        ?string $path = null
    ): self;

    /**
     * pre intercept with given class or callable on all HEAD requests
     *
     * @param  class-string<PreInterceptor>|callable|PreInterceptor  $preInterceptor  pre interceptor to add
     */
    public function preInterceptOnHead(
        string|callable|PreInterceptor $preInterceptor,
        ?string $path = null
    ): self;

    /**
     * pre intercept with given class or callable on all POST requests
     *
     * @param  class-string<PreInterceptor>|callable|PreInterceptor  $preInterceptor  pre interceptor to add
     */
    public function preInterceptOnPost(
        string|callable|PreInterceptor $preInterceptor,
        ?string $path = null
    ): self;

    /**
     * pre intercept with given class or callable on all PUT requests
     *
     * @param  class-string<PreInterceptor>|callable|PreInterceptor  $preInterceptor  pre interceptor to add
     */
    public function preInterceptOnPut(
        string|callable|PreInterceptor $preInterceptor,
        ?string $path = null
    ): self;

    /**
     * pre intercept with given class or callable on all DELETE requests
     *
     * @param  class-string<PreInterceptor>|callable|PreInterceptor  $preInterceptor  pre interceptor to add
     */
    public function preInterceptOnDelete(
        string|callable|PreInterceptor $preInterceptor,
        ?string $path = null
    ): self;

    /**
     * pre intercept with given class or callable on all requests
     *
     * @param  class-string<PreInterceptor>|callable|PreInterceptor  $preInterceptor  pre interceptor to add
     */
    public function preIntercept(
        string|callable|PreInterceptor $preInterceptor,
        ?string $path = null,
        ?string $requestMethod = null
    ): self;

    /**
     * post intercept with given class or callable on all GET requests
     *
     * @param  class-string<PostInterceptor>|callable|PostInterceptor  $postInterceptor  post interceptor to add
     */
    public function postInterceptOnGet(
        string|callable|PostInterceptor $postInterceptor,
        ?string $path = null
    ): self;

    /**
     * post intercept with given class or callable on all HEAD requests
     *
     * @param  class-string<PostInterceptor>|callable|PostInterceptor  $postInterceptor  post interceptor to add
     */
    public function postInterceptOnHead(
        string|callable|PostInterceptor $postInterceptor,
        ?string $path = null
    ): self;

    /**
     * post intercept with given class or callable on all POST requests
     *
     * @param  class-string<PostInterceptor>|callable|PostInterceptor  $postInterceptor  post interceptor to add
     */
    public function postInterceptOnPost(
        string|callable|PostInterceptor $postInterceptor,
        ?string $path = null
    ): self;

    /**
     * post intercept with given class or callable on all PUT requests
     *
     * @param  class-string<PostInterceptor>|callable|PostInterceptor  $postInterceptor  post interceptor to add
     */
    public function postInterceptOnPut(
        string|callable|PostInterceptor $postInterceptor,
        ?string $path = null
    ): self;

    /**
     * post intercept with given class or callable on all DELETE requests
     *
     * @param  class-string<PostInterceptor>|callable|PostInterceptor  $postInterceptor  post interceptor to add
     */
    public function postInterceptOnDelete(
        string|callable|PostInterceptor $postInterceptor,
        ?string $path = null
    ): self;

    /**
     * post intercept with given class or callable on all requests
     *
     * @param  class-string<PostInterceptor>|callable|PostInterceptor  $postInterceptor  post interceptor to add
     */
    public function postIntercept(
        string|callable|PostInterceptor $postInterceptor,
        ?string $path = null,
        ?string $requestMethod = null
    ): self;

    /**
     * sets a default mime type class for given mime type, but doesn't mark the mime type as supported for all routes
     *
     * @param  string                  $mimeType       mime type to set default class for
     * @param  class-string<Mimetype>  $mimeTypeClass  class to use
     * @since  5.1.0
     */
    public function setDefaultMimeTypeClass(string $mimeType, string $mimeTypeClass): self;

    /**
     * add a supported mime type
     *
     * @param  class-string<Mimetype>  $mimeTypeClass  special class to be used for given mime type on this route
     */
    public function supportsMimeType(string $mimeType, ?string $mimeTypeClass = null): self;

    /**
     * disables content negotation
     *
     * @since  2.1.1
     */
    public function disableContentNegotiation(): self;
}
