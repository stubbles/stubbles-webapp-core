# Changelog

## 8.0.1 (2019-11-21)

* fixed bug with `stubbles\webapp\request\WebRequest::uri()` parsing port incorrectly

## 8.0.0 (2019-11-19)

### BC breaks

* Raised minimum required PHP version to 7.3
* Implemented issue #73 `stubbles\webapp\auth\ProtectedResource` should respond with 401 instead of 403 when no user found
  * Extended interface `stubbles\webapp\Response` with new method `unauthorized(array $challenges)`
  * Extended interface `stubbles\webapp\auth\AuthenticationProvider` with new method `challengesFor(stubbles\webapp\Request $request): array`
  * Added new method `stubbles\webapp\response\Error::unauthorized()`
  * Deprecated `stubbles\webapp\routing\ConfigurableRoute::forbiddenWhenNotAlreadyLoggedIn()`, use `stubbles\webapp\routing\ConfigurableRoute::sendChallengeWhenNotLoggedIn()` instead, will be removed with 9.0
  * Deprecated `stubbles\webapp\auth\AuthConstraint::loginAllowed()`, use `stubbles\webapp\auth\AuthConstraint::redirectToLogin()` instead, will be removed with 9.0
* Added more type hints
* `stubbles\webapp\response\mimetypes\Csv` will now throw an exception when a line can't be serialized instead of silently converting to an empty line
* `stubbles\webapp\htmlpassthrough\HtmlFilePassThrough` now serves an error 500 in case the file can't be read
* `stubbles\webapp\UriPath::remaining()` now returns an empty string instead of `null` if there is no remaining path and no default given

## 7.0.0 (2016-08-06)

### BC breaks

* Raised minimum required PHP version to 7.0
* introduced scalar type hints and strict type checking

## 6.2.2 (2015-07-06)

* put filename in Content-Disposition header in double quotes, see <https://code.google.com/p/chromium/issues/detail?id=103618>

## 6.2.1 (2015-06-23)

* API index now contains information about globally supported mime types

## 6.2.0 (2015-06-17)

* added `stubbles\webapp\response\Error::inParams()`

## 6.1.0 (2015-05-28)

* added `stubbles\webapp\RoutingConfigurator::redirectOnGet()` to specify simple redirects
* added support for displaying an API index with `stubbles\webapp\RoutingConfigurator::apiIndexOnGet()`
* fixed bug where allowed methods on 404 Method Not Allowed response contained methods more than once
* fixed bug that allowed methods on 404 Method Not Allowed response did not contain HEAD when GET was allowed
* upgraded stubbles/core to 6.0

## 6.0.1 (2015-04-02)

* fixed output buffering in `stubbles\webapp\response\mimetypes\Image` which prevented proper image display in browser

## 6.0.0 (2015-04-01)

### BC breaks

* changed request interface from `stubbles\input\web\WebRequest` to `stubbles\webapp\Request`
* renamed `stubbles\webapp\response\Response` to `stubbles\webapp\Response`
* replaced `stubbles\webapp\Processor` with `stubbles\webapp\Target`
* renamed package `stubbles\webapp\processor` to `stubbles\webapp\htmlpassthrough`
* both request and response are not available via injection any more
* session instance must now be created in `stubbles\webapp\Webapp::createSession()` instead of passing a session creator closure to io bindings
* moved `stubbles\webapp\ioc\Auth` to `stubbles\webapp\auth\Auth`
* changed status code changing methods to return a `stubbles\webapp\response\Error` instead of itself:
  * `stubbles\webapp\Response::forbidden()`
  * `stubbles\webapp\Response::notFound()`
  * `stubbles\webapp\Response::methodNotAllowed()`
  * `stubbles\webapp\Response::internalServerError()`
* changed status code changing methods to return nothing instead of itself:
  * `stubbles\webapp\Response::redirect()`
  * `stubbles\webapp\Response::notAcceptable()`
  * `stubbles\webapp\Response::httpVersionNotSupported()`
* both `stubbles\webapp\auth\User` and `stubbles\webapp\auth\Roles` are not available via injection any more, use `stubbles\webapp\Request::identity()` instead
* post interceptors of auth protected resources are now always called even when actual request is not authorized

### Other changes

* reintegrated stubbles\webapp-session, classes are in `stubbles\webapp\session`

## 5.2.0 (2015-03-09)

* upgraded stubbles/core to 5.3 to ensure session scope compatibility

## 5.1.2 (2014-10-13)

* fixed bug that IoBindingModule was not marked as initialized when explicitly created in bindings

## 5.1.1 (2014-09-30)

* fixed bug that default formatter were not recognized correctly

## 5.1.0 (2014-09-29)

* upgraded stubbles/core to 5.1
* implemented #72: Allow to define a default formatter for a mime type
  * added `stubbles\webapp\RoutingConfigurator::setDefaultFormatter($mimeType, $formatterClass)`
* implemented #63: Add support for mime type annotations on callbacks
  * Added support for @SupportsMimeType
  * Added support for @DisableContentNegotiation
* implemented #71: Simple way to add a Cache-Control header
  * added `stubbles\webapp\response\Headers::cacheControl()`
* implemented #74: Possibility to automatically generate and log request ids
* added `stubbles\webapp\response\Headers::age()`

## 5.0.2 (2014-09-01)

* fixed `stubbles\webapp\RoutingConfigurator`, was not in sync with `stubbles\webapp\routing\Routing`

## 5.0.1 (2014-09-01)

* fixed issue #69: Use status code 504 for external auth provider failures instead of 503
* fixed issue #70: Invalid request should trigger a 400 Bad Request

## 5.0.0 (2014-08-18)

### BC breaks

* completely reworked how authentication and authorization works
  * removed `stubbles\webapp\auth\AuthHandler`
  * removed `stubbles\webapp\auth\AuthHandlerException`, added `stubbles\webapp\auth\InternalAuthProviderException` and `stubbles\webapp\auth\ExternalAuthProviderException`
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
* globally defined formatters can not be set via io binding module any more, but directly on the routing when specifying the global mime type

### Other changes

* upgraded stubbles/core to 5.0
* upgraded stubbles/input to 4.0
* ensured compatibility with stubbles/webapp-session 5.0
* io binding module is added to bindings by default if not explicitly specified

## 4.0.0 (2014-07-31)

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

## 3.4.0 (2014-05-16)

* now requires PHP 5.4
* added possibility to restrict global pre and post interceptors to certain pathes
* added `net\stubbles\webapp\interceptor\AddAccessControlAllowOriginHeader`

## 3.3.0 (2014-02-18)

* implemented #41 `UriPath::getArgument()` should return `ValueReader`
  * deprecated `net\stubbles\webapp\UriPath::getArgument()`, will be removed with 4.0.0
  * added `net\stubbles\webapp\UriPath::readArgument()` which returns a `net\stubbles\input\ValueReader`

## 3.2.0 (2014-02-06)

* added possibility to have route specific formatters for mime types

## 3.1.0 (2014-01-21)

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

## 3.0.0 (2013-11-01)

### BC breaks

* interface `net\stubbles\webapp\AuthHandler` was replaced by `net\stubbles\webapp\auth\AuthHandler`

### Other changes

* implemented #32: possibility to propagate an error in the auth system to the response

## 2.2.0 (2013-10-13)

* global interceptors (pre and post) are now called even if no suitable route could be found
* uncatched exceptions from interceptors and processors are now turned into internal server errors

## 2.1.2 (2013-10-06)

* `net\stubbles\webapp\WebApp::run()` now returns response instance

## 2.1.1 (2013-09-17)

* added possibility to disable content negotation for mime types
  * added `net\stubbles\webapp\ConfigurableRoute::disableContentNegotiation()`
  * added `net\stubbles\webapp\Route::disableContentNegotiation()` and `net\stubbles\webapp\Route::isContentNegotationDisabled()`
  * added `net\stubbles\webapp\RoutingConfigurator::disableContentNegotiation()`
  * added `net\stubbles\webapp\Routing::disableContentNegotiation()` and `net\stubbles\webapp\Routing::isContentNegotationDisabled()`
* fixed `net\stubbles\webapp\RoutingConfigurator::supportsMimeType()`

## 2.1.0 (2013-05-02)

* upgraded stubbles/core to ~3.0

## 2.0.0 (2013-02-06)

* Initial release.
