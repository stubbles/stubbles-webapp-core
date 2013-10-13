2.2.0, (2013-10-??)
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
