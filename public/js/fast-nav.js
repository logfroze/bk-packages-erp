/**
 * BK Packages ERP — FastNav Prefetch Engine
 * High-performance hover/touch prefetching.
 * Pre-warms HTTP & Service Worker cache on hover for instant 0ms native page loads.
 * Fully preserves all Blade sections, modals, Alpine components, and script lifecycle.
 */
(function () {
  'use strict';

  const prefetchedUrls = new Set();

  function isEligibleLink(anchor) {
    if (!anchor || anchor.tagName !== 'A') return false;
    const href = anchor.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) {
      return false;
    }
    if (anchor.target === '_blank' || anchor.hasAttribute('download') || anchor.hasAttribute('data-no-fastnav')) {
      return false;
    }
    try {
      const url = new URL(anchor.href, window.location.origin);
      if (url.origin !== window.location.origin) return false;
      if (url.pathname.includes('/logout') || url.pathname.includes('/login') || url.pathname.includes('/print') || url.pathname.includes('/slip')) {
        return false;
      }
      return true;
    } catch (e) {
      return false;
    }
  }

  function prefetchUrl(urlStr) {
    if (prefetchedUrls.has(urlStr)) return;
    prefetchedUrls.add(urlStr);

    // Warm connection and cache via standard link prefetch or fetch
    try {
      const link = document.createElement('link');
      link.rel = 'prefetch';
      link.href = urlStr;
      link.as = 'document';
      document.head.appendChild(link);
    } catch (e) {
      fetch(urlStr, { priority: 'low' }).catch(() => {});
    }
  }

  // Pre-load on link hover (desktop) or touchstart (mobile)
  document.addEventListener('mouseover', (e) => {
    const a = e.target.closest('a');
    if (isEligibleLink(a)) {
      prefetchUrl(a.href);
    }
  }, { passive: true });

  document.addEventListener('touchstart', (e) => {
    const a = e.target.closest('a');
    if (isEligibleLink(a)) {
      prefetchUrl(a.href);
    }
  }, { passive: true });

  window.BKFastNav = {
    prefetch: prefetchUrl,
    clear: () => prefetchedUrls.clear()
  };
})();
