/**
 * BK Packages ERP — Side-by-Side Gemini AI Factory Assistant & Conversation History
 */
(function () {
  const OPEN_STATE_KEY = 'bk_ai_drawer_open';
  let activeConversationId = null;
  let welcomeBoxHtml = '';
  let cachedConversations = [];
  let isHistoryOpen = false;

  function getUserId() {
    return document.getElementById('ai-drawer')?.getAttribute('data-user-id') || 'guest';
  }

  function getActiveConvKey() {
    return `bk_ai_active_conv_u${getUserId()}`;
  }

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

  function formatTimestamp(isoString, fallbackHuman) {
    if (!isoString) return fallbackHuman || '';
    try {
      const dt = new Date(isoString);
      return dt.toLocaleString([], {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
      });
    } catch {
      return fallbackHuman || '';
    }
  }

  function setActiveConversation(id, title = null) {
    activeConversationId = id ? Number(id) : null;
    try {
      if (activeConversationId) {
        localStorage.setItem(getActiveConvKey(), String(activeConversationId));
      } else {
        localStorage.removeItem(getActiveConvKey());
      }
    } catch {}

    const subtitle = document.getElementById('ai-header-subtitle');
    if (subtitle) {
      subtitle.textContent = title ? title : 'Side-by-Side · Live Data';
    }
  }

  function scrollToBottom() {
    const container = document.getElementById('ai-messages');
    if (container) container.scrollTop = container.scrollHeight;
  }

  function resetToWelcomeView() {
    const container = document.getElementById('ai-messages');
    if (!container) return;
    container.innerHTML = welcomeBoxHtml || '';
    setActiveConversation(null, null);
  }

  function appendMessageElement(role, text, pendingAction = null, actionStatus = null, messageId = null) {
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
      if (pendingAction && actionStatus === 'executed') {
        actionCardHtml = `
          <div class="mt-2.5 px-3 py-2 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px] text-emerald-600">check_circle</span>
            <span>Confirmed & Saved to Database</span>
          </div>
        `;
      } else if (pendingAction && actionStatus !== 'dismissed') {
        const lines = (pendingAction.summary_lines || [])
          .map((l) => `<li class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span><span>${escapeHtml(l)}</span></li>`)
          .join('');

        const encodedAction = encodeURIComponent(JSON.stringify(pendingAction));
        const msgIdArg = messageId ? Number(messageId) : 'null';
        actionCardHtml = `
          <div class="mt-3 p-3.5 rounded-xl bg-slate-50 border border-blue-200 space-y-2.5 ai-action-card">
            <div class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
              <span class="material-symbols-outlined text-[16px] text-blue-600">fact_check</span>
              <span>${escapeHtml(pendingAction.title || 'Confirm Action')}</span>
            </div>
            <ul class="text-xs text-slate-700 space-y-1">${lines}</ul>
            <div class="flex items-center gap-2 pt-1">
              <button type="button"
                      onclick="window.BKAiCopilot.confirmAction(this, '${encodedAction}', ${msgIdArg})"
                      class="flex-1 py-2 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition-colors">
                Confirm & Save
              </button>
              <button type="button"
                      onclick="window.BKAiCopilot.dismissAction(this, ${msgIdArg})"
                      class="py-2 px-3 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 text-slate-600 text-xs font-medium">
                Cancel
              </button>
            </div>
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

  function renderHistoryList(items) {
    const listEl = document.getElementById('ai-history-list');
    if (!listEl) return;

    if (!items || items.length === 0) {
      listEl.innerHTML = `
        <div class="py-12 px-4 text-center space-y-2">
          <span class="material-symbols-outlined text-[32px] text-slate-300">forum</span>
          <div class="text-xs font-semibold text-slate-600">No saved conversations yet</div>
          <p class="text-[11px] text-slate-400">Your questions and AI entries will appear here automatically.</p>
        </div>
      `;
      return;
    }

    listEl.innerHTML = items.map((c) => {
      const isCurrent = activeConversationId && Number(c.id) === Number(activeConversationId);
      const timeLabel = formatTimestamp(c.updated_at_iso, c.updated_at_human);
      return `
        <div onclick="window.BKAiCopilot.openConversation(${Number(c.id)})"
             class="group cursor-pointer rounded-xl p-3 border transition-colors ${isCurrent ? 'bg-blue-50/70 border-blue-300' : 'bg-white border-slate-200 hover:border-blue-200 hover:bg-slate-50'}">
          <div class="flex items-start justify-between gap-2">
            <div class="min-w-0 flex-1">
              <div class="text-xs font-semibold text-slate-900 truncate flex items-center gap-1.5">
                ${isCurrent ? '<span class="w-1.5 h-1.5 rounded-full bg-blue-600 shrink-0"></span>' : ''}
                <span class="truncate">${escapeHtml(c.title)}</span>
              </div>
              <div class="text-[11px] text-slate-500 truncate mt-0.5">${escapeHtml(c.preview || '')}</div>
              <div class="flex items-center gap-2 mt-1.5 text-[10px] text-slate-400">
                <span>${escapeHtml(timeLabel)}</span>
                <span>•</span>
                <span>${Number(c.messages_count || 0)} msgs</span>
              </div>
            </div>
            <button type="button"
                    onclick="window.BKAiCopilot.deleteConversation(event, ${Number(c.id)})"
                    title="Delete conversation"
                    class="p-1 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors shrink-0">
              <span class="material-symbols-outlined text-[16px]">delete</span>
            </button>
          </div>
        </div>
      `;
    }).join('');
  }

  async function fetchHistoryList() {
    const listEl = document.getElementById('ai-history-list');
    if (listEl) {
      listEl.innerHTML = '<div class="py-10 text-center text-xs text-slate-400">Loading saved conversations...</div>';
    }

    try {
      const res = await fetch('/ai-copilot/conversations', {
        headers: { 'Accept': 'application/json' },
      });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const data = await res.json();
      cachedConversations = data.conversations || [];
      const searchVal = document.getElementById('ai-history-search')?.value || '';
      window.BKAiCopilot.filterHistory(searchVal);
    } catch {
      if (listEl) {
        listEl.innerHTML = '<div class="py-10 text-center text-xs text-red-500">Could not load conversation history.</div>';
      }
    }
  }

  async function loadConversationMessages(convId) {
    const container = document.getElementById('ai-messages');
    if (!container) return;

    container.innerHTML = '<div class="py-10 text-center text-xs text-slate-400">Loading conversation...</div>';

    try {
      const res = await fetch(`/ai-copilot/conversations/${Number(convId)}`, {
        headers: { 'Accept': 'application/json' },
      });
      if (!res.ok) {
        resetToWelcomeView();
        return;
      }
      const data = await res.json();
      setActiveConversation(data.conversation.id, data.conversation.title);

      container.innerHTML = '';
      const messages = data.messages || [];
      if (messages.length === 0) {
        container.innerHTML = welcomeBoxHtml || '';
        return;
      }

      messages.forEach((msg) => {
        appendMessageElement(
          msg.role,
          msg.text,
          msg.pending_action || null,
          msg.action_status || null,
          msg.id
        );
      });
      scrollToBottom();
    } catch {
      resetToWelcomeView();
    }
  }

  async function sendPrompt(messageText) {
    const input = document.getElementById('ai-input');
    const text = (messageText ?? input?.value ?? '').trim();
    if (!text) return;
    if (input && messageText === undefined) input.value = '';

    if (isHistoryOpen) {
      window.BKAiCopilot.toggleHistory(false);
    }

    if (!navigator.onLine) {
      appendMessageElement('model', 'You are currently offline. AI Assistant requires an internet connection, while normal factory forms work offline automatically.');
      return;
    }

    appendMessageElement('user', text);
    showTypingIndicator();

    try {
      const payload = { message: text };
      if (activeConversationId) {
        payload.conversation_id = activeConversationId;
      }

      const res = await fetch('/ai-copilot/chat', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': getCsrf(),
        },
        body: JSON.stringify(payload),
      });

      const data = await res.json();
      removeTypingIndicator();

      if (res.ok && data.conversation_id) {
        setActiveConversation(data.conversation_id, data.conversation_title || null);
      }

      const replyText = data.reply || 'Sorry, I could not understand that.';
      const pending = data.pending_action || null;
      const msgId = data.model_message_id || null;

      appendMessageElement('model', replyText, pending, null, msgId);
    } catch {
      removeTypingIndicator();
      appendMessageElement('model', 'Network error while contacting AI Assistant. Please try again.');
    }
  }

  window.BKAiCopilot = {
    toggle: function (forceState = null) {
      const root = document.documentElement;
      const isOpen = root.classList.contains('ai-dock-open');
      const shouldOpen = forceState !== null ? Boolean(forceState) : !isOpen;

      root.classList.toggle('ai-dock-open', shouldOpen);
      try {
        localStorage.setItem(OPEN_STATE_KEY, shouldOpen ? '1' : '0');
      } catch {}

      if (shouldOpen) {
        setTimeout(() => document.getElementById('ai-input')?.focus(), 150);
      }
    },

    toggleHistory: function (forceState = null) {
      const historyPanel = document.getElementById('ai-history-panel');
      const chatView = document.getElementById('ai-chat-view');
      const historyBtn = document.getElementById('ai-history-btn');
      if (!historyPanel || !chatView) return;

      isHistoryOpen = forceState !== null ? Boolean(forceState) : historyPanel.classList.contains('hidden');

      historyPanel.classList.toggle('hidden', !isHistoryOpen);
      chatView.classList.toggle('hidden', isHistoryOpen);
      historyBtn?.classList.toggle('bg-slate-800', isHistoryOpen);
      historyBtn?.classList.toggle('text-white', isHistoryOpen);

      if (isHistoryOpen) {
        fetchHistoryList();
      }
    },

    filterHistory: function (query) {
      const q = String(query || '').trim().toLowerCase();
      if (!q) {
        renderHistoryList(cachedConversations);
        return;
      }
      const filtered = cachedConversations.filter((c) =>
        (c.title || '').toLowerCase().includes(q) ||
        (c.preview || '').toLowerCase().includes(q)
      );
      renderHistoryList(filtered);
    },

    openConversation: async function (convId) {
      window.BKAiCopilot.toggleHistory(false);
      await loadConversationMessages(convId);
    },

    deleteConversation: async function (event, convId) {
      event.stopPropagation();
      if (!confirm('Delete this conversation from history?')) return;

      try {
        const res = await fetch(`/ai-copilot/conversations/${Number(convId)}`, {
          method: 'DELETE',
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': getCsrf(),
          },
        });
        if (res.ok) {
          cachedConversations = cachedConversations.filter((c) => Number(c.id) !== Number(convId));
          if (activeConversationId && Number(activeConversationId) === Number(convId)) {
            resetToWelcomeView();
          }
          const searchVal = document.getElementById('ai-history-search')?.value || '';
          window.BKAiCopilot.filterHistory(searchVal);
        }
      } catch {
        alert('Could not delete conversation.');
      }
    },

    startNewChat: function () {
      if (isHistoryOpen) {
        window.BKAiCopilot.toggleHistory(false);
      }
      resetToWelcomeView();
      setTimeout(() => document.getElementById('ai-input')?.focus(), 80);
    },

    clearChat: function () {
      this.startNewChat();
    },

    askQuick: function (promptText) {
      if (!document.documentElement.classList.contains('ai-dock-open')) {
        this.toggle(true);
      }
      sendPrompt(promptText);
    },

    confirmAction: async function (btn, encodedAction, messageId = null) {
      const action = JSON.parse(decodeURIComponent(encodedAction));
      btn.disabled = true;
      btn.textContent = 'Saving...';

      try {
        const res = await fetch('/ai-copilot/execute', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': getCsrf(),
          },
          body: JSON.stringify({
            target_path: action.target_path,
            payload: action.payload,
            message_id: messageId,
          }),
        });

        const data = await res.json();
        if (res.ok && (data.status === 'executed' || data.status === 'already_executed')) {
          btn.closest('.ai-action-card').outerHTML = `
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
      } catch {
        btn.disabled = false;
        btn.textContent = 'Confirm & Save';
        alert('Error saving action.');
      }
    },

    dismissAction: async function (btn, messageId = null) {
      btn.closest('.ai-action-card')?.remove();
      if (messageId) {
        try {
          await fetch(`/ai-copilot/messages/${Number(messageId)}/dismiss`, {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              'X-CSRF-TOKEN': getCsrf(),
            },
          });
        } catch {}
      }
    },
  };

  document.addEventListener('DOMContentLoaded', () => {
    const welcomeEl = document.getElementById('ai-welcome-box');
    if (welcomeEl) {
      welcomeBoxHtml = welcomeEl.outerHTML;
    }

    try {
      const savedConvId = localStorage.getItem(getActiveConvKey());
      if (savedConvId && Number(savedConvId) > 0) {
        loadConversationMessages(Number(savedConvId));
      }
    } catch {}

    const form = document.getElementById('ai-chat-form');
    if (form) {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        sendPrompt();
      });
    }
  });
})();
