4.0.0, (2014-06-??)
-------------------

### BC breaks

   * removed namespace prefix `net`, base namespace is now `stubbles\webapp` only
   * removed `net\stubbles\webapp\UriPath::getArgument()`, deprecated since 3.3.0, use `net\stubbles\webapp\UriPath::readArgument()` instead
   * api rework:
     * deprecated `stubbles\webapp\response\Cookie::getName()`, use `stubbles\webapp\response\Cookie::name()` instead, will be removed with 5.0.0
     * deprecated `stubbles\webapp\response\Cookie::getValue()`, use `stubbles\webapp\response\Cookie::value()` instead, will be removed with 5.0.0
     * deprecated `stubbles\webapp\response\Cookie::getExpiration()`, use `stubbles\webapp\response\Cookie::expiration()` instead, will be removed with 5.0.0
     * deprecated `stubbles\webapp\response\Cookie::getPath()`, use `stubbles\webapp\response\Cookie::path()` instead, will be removed with 5.0.0
     * deprecated `stubbles\webapp\response\Cookie::getDomain()`, use `stubbles\webapp\response\Cookie::domain()` instead, will be removed with 5.0.0
     * deprecated `stubbles\webapp\response\Cookie::getDomain()`, use `stubbles\webapp\response\Cookie::domain()` instead, will be removed with 5.0.0

### Other changes

   * upgraded to stubbles/core 4.x and stubbles/input 3.x
   * added `net\stubbles\webapp\response\Response::headers()`


3.4.0, (2014-05-16)
-------------------

   * now requires PHP 5.4
   * added possibility to restrict global pre and post interceptors to certain pathes
   * added `net\stubbles\webapp\interceptor\AddAccessControlAllowOriginHeader`


3.3.0, (2014-02-18)
-------------------

   * implemented #41 `UriPath::getArgument()` should return `ValueReader`
      * deprecated `net\stubbles\webapp\UriPath::getArgument()`, will be removed with 4.0.0
      * added `net\stubbles\webapp\UriPath::readArgument()` which returns a `net\stubbles\input\ValueReader`


3.2.0, (2014-02-06)
-------------------

   * added possibility to have route specific formatters for mime types


3.1.0, (2014-01-21)
-------------------

   * implemented #35: enable annotations on processor classes to reduce route configuration:
      * `@RequiresHttps`
      * `@RequiresLogin`
      * `@RequiresRole`
   * Introduced fixed responses: a response is fixed when a final status has been set. A final status is set when one of the following methods is called:
      * `forbidden()`
      * `notFound()`
      * `methodNotAllowed()`
      * `notAcceptable()`
      * `internalServerError()`
      * `httpVersionNotSupported()`
     This replaces checks on whether the request was cancelled, this is not used any more.
   * uncatched exceptions from interceptors and processors are now logged via `net\stubbles\lang\errorhandler\ExceptionLogger`
   * `net\stubbles\webapp\Webapp::$request` can now be accessed by subclasses
   * added possibility to overwrite decision about ssl switch via `net\stubbles\webapp\Webapp::switchToHttps()`
   * upgraded stubbles/core to ~3.4


3.0.0, (2013-11-01)
-------------------

### BC breaks

   * interface `net\stubbles\webapp\AuthHandler` was replaced by `net\stubbles\webapp\auth\AuthHandler`

### Other changes

   * implemented #32: possibility to propagate an error in the auth system to the response


2.2.0, (2013-10-13)
-------------------

   * global interceptors (pre and post) are now called even if no suitable route could be found
   * uncatched exceptions from interceptors and processors are now turned into internal server errors


2.1.2, (2013-10-06)
-------------------

   * `net\stubbles\webapp\WebApp::run()` now returns response instance


2.1.1, (2013-09-17)
-------------------

   * added possibility to disable content negotation for mime types
      * added `net\stubbles\webapp\ConfigurableRoute::disableContentNegotiation()`
      * added `net\stubbles\webapp\Route::disableContentNegotiation()` and `net\stubbles\webapp\Route::isContentNegotationDisabled()`
      * added `net\stubbles\webapp\RoutingConfigurator::disableContentNegotiation()`
      * added `net\stubbles\webapp\Routing::disableContentNegotiation()` and `net\stubbles\webapp\Routing::isContentNegotationDisabled()`
   * fixed `net\stubbles\webapp\RoutingConfigurator::supportsMimeType()`


2.1.0, (2013-05-02)
-------------------

   * upgraded stubbles/core to ~3.0


2.0.0, (2013-02-06)
-------------------

   * Initial release.
