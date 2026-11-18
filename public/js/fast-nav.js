/**
 * BK Packages ERP — FastNav Engine
 * Optimized for high-throughput single-worker & serverless environments.
 * Prevents aggressive mouseover request floods that block single-threaded PHP CLI servers.
 */
(function () {
  'use strict';

  // Disabled aggressive hover prefetching to eliminate queue starvation on single-threaded dev servers
  window.BKFastNav = {
    prefetch: () => {},
    clear: () => {}
  };
})();
