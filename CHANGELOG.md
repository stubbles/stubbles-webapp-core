5.0.0 (2014-08-??)
------------------

### BC breaks

   * completely reworked how authentication and authorization works
     * removed `stubbles\webapp\auth\AuthHandler`
     * renamed `stubbles\webapp\auth\AuthHandlerException` to `stubbles\webapp\auth\AuthProviderException`
     * added `stubbles\webapp\auth\AuthenticationProvider`
     * added `stubbles\webapp\auth\AuthorizationProvider`
     * added `stubbles\webapp\auth\Roles`
     * added `stubbles\webapp\auth\Token`
     * added `stubbles\webapp\auth\User`
     * added `stubbles\webapp\auth\token\TokenAuthenticator`
     * added `stubbles\webapp\auth\token\TokenStore`
     * added `stubbles\webapp\ioc\Auth` to enable new auth bindings
     * added support for annotation `@RolesAware` which can be set on processors in case they don't need a specific role but access to the roles of a user in general
   * removed all methods deprecated with 4.0.0 (see below)
   * changed all thrown stubbles/core exceptions to those recommended with stubbles/core 5.0.0

### Other changes

   * upgraded stubbles/core to 5.0
   * upgraded stubbles/input to 4.0
   * ensured compatibility with stubbles/webapp-session 5.0


4.0.0 (2014-07-31)
------------------

### BC breaks

   * removed namespace prefix `net`, base namespace is now `stubbles\webapp` only
   * removed all classes in namespace `stubbles\webapp\session`, can now be found in separate package stubbles/webapp-session
    * removed `stubbles\webapp\Webapp::createIoBindingModuleWithSession()`
    * removed `stubbles\webapp\ioc\IoBindingModule::createWithSession()`
    * deprecated `stubbles\webapp\Webapp::createIoBindingModuleWithoutSession()`, use `stubbles\webapp\Webapp::createIoBindingModule()` instead, will be removed with 5.0.0
    * deprecated `stubbles\webapp\ioc\IoBindingModule::createWithoutSession()`, will be removed with 5.0.0
    * deprecated `stubbles\webapp\ioc\IoBindingModule::setSessionCreator()`, can now be passed optionally to it's constructor, will be removed with 5.0.0
   * removed `net\stubbles\webapp\UriPath::getArgument()`, deprecated since 3.3.0, use `net\stubbles\webapp\UriPath::readArgument()` instead
   * `net\stubbles\webapp\UriPath::readArgument()` does not accept default values, use `defaultingTo()` of returned `stubbles\input\ValueReader` instead
   * changed `stubbles\webapp\response\format\Formatter::format()` to receive `stubbles\webapp\response\Headers` as second parameter
   * api rework: replaced some constructs with better ones, all deprecated will be removed with 5.0.0
     * deprecated `stubbles\webapp\UriPath::getMatched()`, use `stubbles\webapp\UriPath::configured()` instead
     * deprecated `stubbles\webapp\UriPath::getRemaining()`, use `stubbles\webapp\UriPath::remaining()` instead
     * deprecated `stubbles\webapp\UriRequest::fromString()`, use `new stubbles\webapp\UriRequest()` instead
     * deprecated `stubbles\webapp\response\Cookie::getName()`, use `stubbles\webapp\response\Cookie::name()` instead
     * deprecated `stubbles\webapp\response\Cookie::getValue()`, use `stubbles\webapp\response\Cookie::value()` instead
     * deprecated `stubbles\webapp\response\Cookie::getExpiration()`, use `stubbles\webapp\response\Cookie::expiration()` instead
     * deprecated `stubbles\webapp\response\Cookie::getPath()`, use `stubbles\webapp\response\Cookie::path()` instead
     * deprecated `stubbles\webapp\response\Cookie::getDomain()`, use `stubbles\webapp\response\Cookie::domain()` instead
     * deprecated `stubbles\webapp\response\Cookie::getDomain()`, use `stubbles\webapp\response\Cookie::domain()` instead
   * constructor of `stubbles\webapp\response\WebResponse` now accepts correct http version strings only according to RFC 7230
   * `stubbles\webapp\Webapp::run()` does not send the response on its own any more, calling code has to send the returned response itself

### Other changes

   * upgraded to stubbles/core 4.x and stubbles/input 3.x
   * added `net\stubbles\webapp\response\Response::headers()`
   * fixed bug with route selection when no method restriction was set on a route
   * added `net\stubbles\webapp\RoutingConfigurator::onAll()`
   * added `stubbles\webapp\UriPath::actual()`
   * fixed bug: response should not add content length header automatically when already added before
   * added `stubbles\webapp\processor\HtmlFilePassThrough`
   * added `stubbles\webapp\processor\SessionBasedHtmlFilePassThrough`
   * added `stubbles\webapp\RoutingConfigurator::passThroughOnGet()`


3.4.0 (2014-05-16)
------------------

   * now requires PHP 5.4
   * added possibility to restrict global pre and post interceptors to certain pathes
   * added `net\stubbles\webapp\interceptor\AddAccessControlAllowOriginHeader`


3.3.0 (2014-02-18)
------------------

   * implemented #41 `UriPath::getArgument()` should return `ValueReader`
      * deprecated `net\stubbles\webapp\UriPath::getArgument()`, will be removed with 4.0.0
      * added `net\stubbles\webapp\UriPath::readArgument()` which returns a `net\stubbles\input\ValueReader`


3.2.0 (2014-02-06)
------------------

   * added possibility to have route specific formatters for mime types


3.1.0 (2014-01-21)
------------------

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


3.0.0 (2013-11-01)
------------------

### BC breaks

   * interface `net\stubbles\webapp\AuthHandler` was replaced by `net\stubbles\webapp\auth\AuthHandler`

### Other changes

   * implemented #32: possibility to propagate an error in the auth system to the response


2.2.0 (2013-10-13)
------------------

   * global interceptors (pre and post) are now called even if no suitable route could be found
   * uncatched exceptions from interceptors and processors are now turned into internal server errors


2.1.2 (2013-10-06)
------------------

   * `net\stubbles\webapp\WebApp::run()` now returns response instance


2.1.1 (2013-09-17)
------------------

   * added possibility to disable content negotation for mime types
      * added `net\stubbles\webapp\ConfigurableRoute::disableContentNegotiation()`
      * added `net\stubbles\webapp\Route::disableContentNegotiation()` and `net\stubbles\webapp\Route::isContentNegotationDisabled()`
      * added `net\stubbles\webapp\RoutingConfigurator::disableContentNegotiation()`
      * added `net\stubbles\webapp\Routing::disableContentNegotiation()` and `net\stubbles\webapp\Routing::isContentNegotationDisabled()`
   * fixed `net\stubbles\webapp\RoutingConfigurator::supportsMimeType()`


2.1.0 (2013-05-02)
------------------

   * upgraded stubbles/core to ~3.0


2.0.0 (2013-02-06)
------------------

   * Initial release.
