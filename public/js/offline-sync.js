/**
 * BK Packages ERP — Offline-First IndexedDB (Dexie.js) Sync Engine
 * Stores queued factory mutations locally during Wi-Fi drops and syncs automatically on reconnect.
 */
(function () {
  if (typeof Dexie === 'undefined') {
    console.warn('Dexie.js not loaded; offline queue disabled.');
    return;
  }

  const db = new Dexie('BKPackagesOfflineDB');
  db.version(1).stores({
    sync_queue: '++id, client_uuid, target_path, label, status, created_at'
  });

  // Register PWA Service Worker
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('/sw.js').catch((err) => {
        console.warn('Service worker registration skipped:', err);
      });
    });
  }

  // Serialize FormData into nested JS objects (handles records[0][status] syntax cleanly)
  function formDataToObject(formData) {
    const root = {};
    for (const [rawKey, value] of formData.entries()) {
      if (rawKey === '_token') continue;
      const keys = rawKey.replace(/\]/g, '').split('[');
      let current = root;
      for (let i = 0; i < keys.length; i++) {
        const key = keys[i];
        const isLast = i === keys.length - 1;
        const nextIsIndex = !isLast && /^\d+$/.test(keys[i + 1]);

        if (isLast) {
          current[key] = value;
        } else {
          if (current[key] === undefined) {
            current[key] = nextIsIndex ? [] : {};
          }
          current = current[key];
        }
      }
    }
    return root;
  }

  function generateUuid() {
    if (crypto && typeof crypto.randomUUID === 'function') {
      return crypto.randomUUID();
    }
    return 'bk-' + Date.now() + '-' + Math.random().toString(36).slice(2, 10);
  }

  function showOfflineToast(message, type = 'amber') {
    const existing = document.getElementById('bk-sync-toast');
    if (existing) existing.remove();

    const colors = type === 'emerald'
      ? 'bg-emerald-50 border-emerald-200 text-emerald-800'
      : 'bg-amber-50 border-amber-200 text-amber-900';

    const icon = type === 'emerald' ? 'cloud_done' : 'cloud_off';

    const div = document.createElement('div');
    div.id = 'bk-sync-toast';
    div.className = `fixed bottom-5 right-5 z-50 max-w-sm rounded-xl border px-4 py-3 shadow-lg flex items-center gap-3 text-sm font-medium ${colors}`;
    div.innerHTML = `
      <span class="material-symbols-outlined">${icon}</span>
      <span class="flex-1">${message}</span>
      <button onclick="this.parentElement.remove()" class="opacity-60 hover:opacity-100">
        <span class="material-symbols-outlined text-[18px]">close</span>
      </button>
    `;
    document.body.appendChild(div);
    setTimeout(() => div.remove(), 6000);
  }

  async function updateNetworkStatusUI() {
    const pill = document.getElementById('network-status-pill');
    const dot = document.getElementById('network-status-dot');
    const label = document.getElementById('network-status-text');
    if (!pill || !dot || !label) return;

    const pendingCount = await db.sync_queue.where('status').equals('PENDING').count();
    const isOnline = navigator.onLine;

    if (!isOnline) {
      pill.className = 'inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200 cursor-pointer';
      dot.className = 'w-2 h-2 rounded-full bg-amber-500 animate-pulse';
      label.textContent = pendingCount > 0 ? `Offline · ${pendingCount} Pending` : 'Offline Mode';
    } else if (pendingCount > 0) {
      pill.className = 'inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200 cursor-pointer';
      dot.className = 'w-2 h-2 rounded-full bg-blue-500 animate-pulse';
      label.textContent = `Syncing (${pendingCount})...`;
    } else {
      pill.className = 'inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200 cursor-pointer';
      dot.className = 'w-2 h-2 rounded-full bg-emerald-500';
      label.textContent = 'Online · Synced';
    }

    await renderQueueModalList();
  }

  async function renderQueueModalList() {
    const list = document.getElementById('offline-queue-list');
    if (!list) return;

    const items = await db.sync_queue.orderBy('id').reverse().toArray();
    if (items.length === 0) {
      list.innerHTML = '<div class="py-8 text-center text-sm text-slate-500">All device entries are synchronized with the central server.</div>';
      return;
    }

    list.innerHTML = items.map((item) => `
      <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-between gap-3">
        <div>
          <div class="text-sm font-semibold text-slate-900">${item.label}</div>
          <div class="text-xs text-slate-500 mt-0.5">${item.target_path} · ${new Date(item.created_at).toLocaleTimeString()}</div>
        </div>
        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold ${item.status === 'PENDING' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-700'}">
          ${item.status}
        </span>
      </div>
    `).join('');
  }

  async function queueMutation(form, labelText) {
    const actionUrl = form.getAttribute('action') || window.location.pathname;
    const targetPath = new URL(actionUrl, window.location.origin).pathname;
    const formData = new FormData(form);
    const payload = formDataToObject(formData);

    await db.sync_queue.add({
      client_uuid: generateUuid(),
      target_path: targetPath,
      label: labelText,
      payload: payload,
      status: 'PENDING',
      created_at: new Date().toISOString()
    });

    // Close any open modal gracefully
    document.querySelectorAll('[id^="modal-"]').forEach((m) => m.classList.add('hidden'));
    document.body.classList.remove('overflow-hidden');
    form.reset();

    await updateNetworkStatusUI();
    showOfflineToast(`Saved offline (${labelText}). Will sync automatically when online.`, 'amber');
  }

  let isSyncing = false;
  async function syncOfflineQueue(showEmptyNotice = false) {
    if (isSyncing || !navigator.onLine) return;
    const pending = await db.sync_queue.where('status').equals('PENDING').toArray();
    if (pending.length === 0) {
      if (showEmptyNotice) showOfflineToast('All records are already synced!', 'emerald');
      return;
    }

    isSyncing = true;
    await updateNetworkStatusUI();

    try {
      // Refresh CSRF token safely in case session refreshed while offline
      const pingRes = await fetch('/offline-sync/ping', { headers: { 'Accept': 'application/json' } });
      if (!pingRes.ok) throw new Error('Server unreachable');
      const pingData = await pingRes.json();
      const csrfToken = pingData.csrf_token;

      let syncedCount = 0;
      for (const item of pending) {
        const res = await fetch('/offline-sync/replay', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
          },
          body: JSON.stringify({
            client_uuid: item.client_uuid,
            target_path: item.target_path,
            payload: item.payload
          })
        });

        if (res.ok) {
          await db.sync_queue.delete(item.id);
          syncedCount++;
        } else {
          await db.sync_queue.update(item.id, { status: 'ERROR' });
        }
      }

      if (syncedCount > 0) {
        showOfflineToast(`Successfully synced ${syncedCount} offline entry(s) to central database!`, 'emerald');
        setTimeout(() => window.location.reload(), 1200);
      }
    } catch (err) {
      console.warn('Sync postponed until connection stabilizes:', err);
    } finally {
      isSyncing = false;
      await updateNetworkStatusUI();
    }
  }

  // Global Form Intercept for Offline Resilience
  document.addEventListener('submit', async (e) => {
    const form = e.target;
    if (!form || form.tagName !== 'FORM') return;

    const method = (form.getAttribute('method') || 'GET').toUpperCase();
    if (method === 'GET' || form.hasAttribute('data-no-offline')) return;

    const actionUrl = form.getAttribute('action') || window.location.pathname;
    const labelMap = {
      '/production': 'Production Batch Entry',
      '/production/conversion': 'Stage 1: Reel to Roll Run',
      '/gate-register': 'Vehicle Gate Pass',
      '/khata/voucher': 'Khata / Voucher Entry',
      '/khata/party': 'New Party Account',
      '/attendance': 'Daily Worker Attendance',
      '/attendance/worker': 'New Worker Profile',
      '/attendance/advance': 'Worker Cash Advance',
      '/inventory/reels': 'Paper Reel Stock',
      '/inventory/cartons': 'Carton Specification'
    };
    const path = new URL(actionUrl, window.location.origin).pathname;
    const labelText = labelMap[path] || 'Factory Record';

    if (!navigator.onLine) {
      e.preventDefault();
      await queueMutation(form, labelText);
    }
  });

  window.addEventListener('online', () => {
    updateNetworkStatusUI();
    syncOfflineQueue();
  });

  window.addEventListener('offline', () => {
    updateNetworkStatusUI();
    showOfflineToast('Internet disconnected. Offline mode active — entries will save locally.', 'amber');
  });

  document.addEventListener('DOMContentLoaded', () => {
    updateNetworkStatusUI();
    if (navigator.onLine) {
      syncOfflineQueue();
    }
  });

  // Expose helper to UI
  window.BKOffline = {
    syncNow: () => syncOfflineQueue(true),
    clearErrors: async () => {
      await db.sync_queue.where('status').equals('ERROR').delete();
      await updateNetworkStatusUI();
    }
  };
})();

