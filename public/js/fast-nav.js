/**
 * BK Packages ERP — FastNav Engine
 * Instant, 0ms page transitions via hover-prefetching & Turbo-style DOM morphing.
 * Eliminates full-page reloads and remote server latency.
 */
(function () {
  'use strict';

  const cache = new Map();
  const parser = new DOMParser();

  // Check if link is an internal ERP page suitable for instant swap
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
      // Do not intercept logout, auth, or print endpoints
      if (url.pathname.includes('/logout') || url.pathname.includes('/login') || url.pathname.includes('/print') || url.pathname.includes('/slip')) {
        return false;
      }
      return true;
    } catch (e) {
      return false;
    }
  }

  // Prefetch page HTML into memory cache
  async function prefetch(urlStr) {
    if (cache.has(urlStr)) return cache.get(urlStr);
    try {
      const res = await fetch(urlStr, {
        headers: { 'X-Requested-With': 'FastNav', 'Accept': 'text/html' }
      });
      if (res.ok) {
        const html = await res.text();
        cache.set(urlStr, html);
        return html;
      }
    } catch (e) {
      // Offline or network error; ServiceWorker will handle fallback
    }
    return null;
  }

  // Swap page DOM smoothly without full reload
  async function navigate(urlStr, pushState = true) {
    let html = cache.get(urlStr);

    // Show slim top loading indicator if not cached instantly
    let progressBar = null;
    if (!html) {
      progressBar = document.createElement('div');
      progressBar.id = 'fastnav-progress';
      progressBar.className = 'fixed top-0 left-0 right-0 h-0.5 bg-blue-600 z-50 animate-pulse transition-all';
      document.body.appendChild(progressBar);
      html = await prefetch(urlStr);
    }

    if (!html) {
      // Fallback to standard browser navigation if fetch fails completely
      window.location.href = urlStr;
      return;
    }

    if (progressBar) progressBar.remove();

    try {
      const doc = parser.parseFromString(html, 'text/html');
      const newMain = doc.querySelector('main');
      const currentMain = document.querySelector('main');

      if (!newMain || !currentMain) {
        window.location.href = urlStr;
        return;
      }

      // Update Document Title
      if (doc.title) {
        document.title = doc.title;
      }

      // Update Breadcrumb & Header Actions
      const newBreadcrumb = doc.querySelector('header span.text-blue-600');
      const currentBreadcrumb = document.querySelector('header span.text-blue-600');
      if (newBreadcrumb && currentBreadcrumb) {
        currentBreadcrumb.textContent = newBreadcrumb.textContent;
      }

      // Swap Main Content
      currentMain.innerHTML = newMain.innerHTML;

      // Update Modals if present
      const newModals = doc.querySelectorAll('[id^="modal-"]');
      newModals.forEach((m) => {
        const existing = document.getElementById(m.id);
        if (existing) {
          existing.replaceWith(m);
        } else {
          document.body.appendChild(m);
        }
      });

      // Update Sidebar Navigation Link highlighting
      const targetUrl = new URL(urlStr, window.location.origin);
      document.querySelectorAll('#sidebar nav a').forEach((link) => {
        try {
          const linkUrl = new URL(link.href, window.location.origin);
          const isMatch = linkUrl.pathname === targetUrl.pathname;
          const icon = link.querySelector('.material-symbols-outlined');

          if (isMatch) {
            link.classList.remove('text-slate-300', 'hover:bg-navy-800/60');
            link.classList.add('bg-navy-800', 'text-white', 'border-l-4', 'border-blue-500');
            if (icon) icon.classList.add('text-blue-400');
          } else {
            link.classList.remove('bg-navy-800', 'text-white', 'border-l-4', 'border-blue-500');
            link.classList.add('text-slate-300', 'hover:bg-navy-800/60');
            if (icon) icon.classList.remove('text-blue-400');
          }
        } catch (e) { }
      });

      // Execute newly injected scripts
      const scripts = doc.querySelectorAll('main script, body > script:not([src])');
      scripts.forEach((oldScript) => {
        if (!oldScript.src) {
          try {
            const newScript = document.createElement('script');
            newScript.textContent = oldScript.textContent;
            document.body.appendChild(newScript);
            setTimeout(() => newScript.remove(), 100);
          } catch (err) {
            console.error('Script execution error during FastNav swap:', err);
          }
        }
      });

      if (pushState) {
        window.history.pushState({ url: urlStr }, '', urlStr);
      }

      window.scrollTo({ top: 0, behavior: 'instant' });

      // Close mobile sidebar if open
      const sidebar = document.getElementById('sidebar');
      const backdrop = document.getElementById('mobile-backdrop');
      if (sidebar && !sidebar.classList.contains('-translate-x-full') && window.innerWidth < 1024) {
        sidebar.classList.add('-translate-x-full');
        if (backdrop) backdrop.classList.add('hidden');
      }

      // Re-trigger offline network UI update
      if (window.BKOffline && typeof window.BKOffline.updateUI === 'function') {
        window.BKOffline.updateUI();
      }

      // Re-fetch in background to update cache silently (Stale-While-Revalidate pattern)
      fetch(urlStr, { headers: { 'X-Requested-With': 'FastNav' } })
        .then((r) => r.ok ? r.text() : null)
        .then((freshHtml) => {
          if (freshHtml) cache.set(urlStr, freshHtml);
        })
        .catch(() => {});

    } catch (e) {
      console.warn('FastNav failed, falling back to full navigation:', e);
      window.location.href = urlStr;
    }
  }

  // Pre-load on hover or touchstart
  document.addEventListener('mouseover', (e) => {
    const a = e.target.closest('a');
    if (isEligibleLink(a)) {
      prefetch(a.href);
    }
  }, { passive: true });

  document.addEventListener('touchstart', (e) => {
    const a = e.target.closest('a');
    if (isEligibleLink(a)) {
      prefetch(a.href);
    }
  }, { passive: true });

  // Handle link clicks with instant FastNav swap
  document.addEventListener('click', (e) => {
    // If modifier keys pressed (Ctrl/Cmd click to open in new tab), let browser handle
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

    const a = e.target.closest('a');
    if (isEligibleLink(a)) {
      e.preventDefault();
      navigate(a.href, true);
    }
  });

  // Handle browser Back / Forward buttons
  window.addEventListener('popstate', (e) => {
    if (e.state && e.state.url) {
      navigate(e.state.url, false);
    } else {
      navigate(window.location.href, false);
    }
  });

  // Expose FastNav helper globally
  window.BKFastNav = {
    prefetch: prefetch,
    navigate: navigate,
    clearCache: () => cache.clear()
  };
})();
