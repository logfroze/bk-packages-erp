/**
 * BK Packages ERP — Built-in Gemini AI Factory Assistant (Side Chat Drawer)
 */
(function () {
  const STORAGE_KEY = 'bk_ai_chat_history_v1';

  function getCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function formatReplyText(text) {
    return escapeHtml(text)
      .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
      .replace(/`([^`]+)`/g, '<code class="px-1 py-0.5 rounded bg-slate-100 text-slate-800 text-xs">$1</code>')
      .replace(/\n/g, '<br>');
  }

  function loadHistory() {
    try {
      return JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '[]');
    } catch {
      return [];
    }
  }

  function saveHistory(items) {
    try {
      sessionStorage.setItem(STORAGE_KEY, JSON.stringify(items.slice(-20)));
    } catch {}
  }

  function scrollToBottom() {
    const container = document.getElementById('ai-messages');
    if (container) container.scrollTop = container.scrollHeight;
  }

  function renderAllMessages() {
    const container = document.getElementById('ai-messages');
    if (!container) return;

    const history = loadHistory();
    if (history.length === 0) {
      return; // Keep default welcome screen
    }

    container.innerHTML = '';
    history.forEach((msg, index) => {
      appendMessageElement(msg.role, msg.text, msg.pending_action || null, msg.executed || false, index);
    });
    scrollToBottom();
  }

  function appendMessageElement(role, text, pendingAction = null, executed = false, historyIdx = null) {
    const container = document.getElementById('ai-messages');
    if (!container) return;

    const welcome = document.getElementById('ai-welcome-box');
    if (welcome) welcome.remove();

    const wrapper = document.createElement('div');
    wrapper.className = role === 'user' ? 'flex justify-end' : 'flex justify-start';

    if (role === 'user') {
      wrapper.innerHTML = `
        <div class="max-w-[85%] rounded-2xl rounded-br-sm bg-navy-900 text-white px-4 py-2.5 text-sm shadow-xs">
          ${escapeHtml(text)}
        </div>
      `;
    } else {
      let actionCardHtml = '';
      if (pendingAction && !executed) {
        const lines = (pendingAction.summary_lines || [])
          .map((l) => `<li class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span><span>${escapeHtml(l)}</span></li>`)
          .join('');

        const encodedAction = encodeURIComponent(JSON.stringify(pendingAction));
        actionCardHtml = `
          <div class="mt-3 p-3.5 rounded-xl bg-slate-50 border border-blue-200 space-y-2.5">
            <div class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
              <span class="material-symbols-outlined text-[16px] text-blue-600">fact_check</span>
              <span>${escapeHtml(pendingAction.title || 'Confirm Action')}</span>
            </div>
            <ul class="text-xs text-slate-700 space-y-1">${lines}</ul>
            <div class="flex items-center gap-2 pt-1">
              <button type="button"
                      onclick="window.BKAiCopilot.confirmAction(this, '${encodedAction}', ${historyIdx})"
                      class="flex-1 py-2 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition-colors">
                Confirm & Save
              </button>
              <button type="button"
                      onclick="window.BKAiCopilot.dismissAction(this, ${historyIdx})"
                      class="py-2 px-3 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 text-slate-600 text-xs font-medium">
                Cancel
              </button>
            </div>
          </div>
        `;
      } else if (pendingAction && executed) {
        actionCardHtml = `
          <div class="mt-2.5 px-3 py-2 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px] text-emerald-600">check_circle</span>
            <span>Confirmed & Saved to Database</span>
          </div>
        `;
      }

      wrapper.innerHTML = `
        <div class="max-w-[92%] rounded-2xl rounded-bl-sm bg-white border border-slate-200 px-4 py-3 text-sm text-slate-800 shadow-xs">
          <div class="leading-relaxed">${formatReplyText(text)}</div>
          ${actionCardHtml}
        </div>
      `;
    }

    container.appendChild(wrapper);
    scrollToBottom();
  }

  function showTypingIndicator() {
    const container = document.getElementById('ai-messages');
    if (!container) return;
    const div = document.createElement('div');
    div.id = 'ai-typing-indicator';
    div.className = 'flex justify-start';
    div.innerHTML = `
      <div class="rounded-2xl rounded-bl-sm bg-white border border-slate-200 px-4 py-2.5 text-xs text-slate-500 flex items-center gap-2">
        <span class="w-2 h-2 rounded-full bg-blue-500 animate-bounce"></span>
        <span>Checking factory data...</span>
      </div>
    `;
    container.appendChild(div);
    scrollToBottom();
  }

  function removeTypingIndicator() {
    document.getElementById('ai-typing-indicator')?.remove();
  }

  async function sendPrompt(messageText) {
    const input = document.getElementById('ai-input');
    const text = (messageText ?? input?.value ?? '').trim();
    if (!text) return;
    if (input && messageText === undefined) input.value = '';

    if (!navigator.onLine) {
      appendMessageElement('model', 'You are currently offline. AI Assistant requires an internet connection, while normal factory forms work offline automatically.');
      return;
    }

    const history = loadHistory();
    history.push({ role: 'user', text });
    saveHistory(history);
    appendMessageElement('user', text);

    showTypingIndicator();
    try {
      const apiHistory = history.slice(-7, -1).map((h) => ({ role: h.role, text: h.text }));
      const res = await fetch('/ai-copilot/chat', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': getCsrf()
        },
        body: JSON.stringify({ message: text, history: apiHistory })
      });

      const data = await res.json();
      removeTypingIndicator();

      const replyText = data.reply || 'Sorry, I could not understand that.';
      const pending = data.pending_action || null;

      const updated = loadHistory();
      const idx = updated.length;
      updated.push({ role: 'model', text: replyText, pending_action: pending, executed: false });
      saveHistory(updated);

      appendMessageElement('model', replyText, pending, false, idx);
    } catch (err) {
      removeTypingIndicator();
      appendMessageElement('model', 'Network error while contacting AI Assistant. Please try again.');
    }
  }

  window.BKAiCopilot = {
    toggle: function () {
      const drawer = document.getElementById('ai-drawer');
      const backdrop = document.getElementById('ai-backdrop');
      if (!drawer || !backdrop) return;
      const isHidden = drawer.classList.contains('translate-x-full');
      if (isHidden) {
        drawer.classList.remove('translate-x-full');
        backdrop.classList.remove('hidden');
        setTimeout(() => document.getElementById('ai-input')?.focus(), 150);
      } else {
        drawer.classList.add('translate-x-full');
        backdrop.classList.add('hidden');
      }
    },

    askQuick: function (promptText) {
      sendPrompt(promptText);
    },

    clearChat: function () {
      sessionStorage.removeItem(STORAGE_KEY);
      window.location.reload();
    },

    confirmAction: async function (btn, encodedAction, historyIdx) {
      const action = JSON.parse(decodeURIComponent(encodedAction));
      btn.disabled = true;
      btn.textContent = 'Saving...';

      try {
        const res = await fetch('/ai-copilot/execute', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': getCsrf()
          },
          body: JSON.stringify({
            target_path: action.target_path,
            payload: action.payload
          })
        });

        const data = await res.json();
        if (res.ok && data.status === 'executed') {
          const history = loadHistory();
          if (historyIdx !== null && history[historyIdx]) {
            history[historyIdx].executed = true;
            saveHistory(history);
          }
          btn.closest('.space-y-2\\.5').outerHTML = `
            <div class="mt-2.5 px-3 py-2 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-1.5">
              <span class="material-symbols-outlined text-[16px] text-emerald-600">check_circle</span>
              <span>${escapeHtml(data.message)} Refreshing...</span>
            </div>
          `;
          setTimeout(() => window.location.reload(), 900);
        } else {
          btn.disabled = false;
          btn.textContent = 'Confirm & Save';
          alert(data.message || 'Could not execute action.');
        }
      } catch (e) {
        btn.disabled = false;
        btn.textContent = 'Confirm & Save';
        alert('Error saving action.');
      }
    },

    dismissAction: function (btn, historyIdx) {
      const history = loadHistory();
      if (historyIdx !== null && history[historyIdx]) {
        history[historyIdx].pending_action = null;
        saveHistory(history);
      }
      btn.closest('.space-y-2\\.5')?.remove();
    }
  };

  document.addEventListener('DOMContentLoaded', () => {
    renderAllMessages();

    const form = document.getElementById('ai-chat-form');
    if (form) {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        sendPrompt();
      });
    }
  });
})();

