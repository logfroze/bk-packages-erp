@extends('layouts.app')

@section('title', 'Factory Chat — Internal Messaging')
@section('breadcrumb', 'Factory Chat')

@section('header_actions')
    <div class="flex items-center gap-2">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>Live Chat Active</span>
        </span>
    </div>
@endsection

@section('content')
<div class="h-[calc(100vh-145px)] min-h-[580px] bg-white rounded-2xl border border-slate-200 shadow-sm flex overflow-hidden">

    <!-- ========================================== -->
    <!-- LEFT PANE: CONTACTS & CHANNELS LIST        -->
    <!-- ========================================== -->
    <div class="w-80 sm:w-88 border-r border-slate-200 flex flex-col shrink-0 bg-slate-50/50">
        <!-- User Self-Profile Bar -->
        <div class="p-4 border-b border-slate-200 bg-white flex items-center justify-between">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-full bg-navy-900 text-white font-bold text-sm flex items-center justify-center shrink-0 shadow-xs">
                    {{ auth()->user()->initials() }}
                </div>
                <div class="min-w-0">
                    <div class="font-bold text-slate-900 text-sm truncate">{{ auth()->user()->name }}</div>
                    <div class="text-[11px] text-slate-500 font-mono truncate">{{ auth()->user()->email }}</div>
                </div>
            </div>
        </div>

        <!-- Channels & Contacts List -->
        <div class="flex-1 overflow-y-auto p-3 space-y-1">
            <div class="px-2 pt-1 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                Team Channels
            </div>

            <!-- 1. Factory General Group Channel -->
            <a href="{{ route('chat.index', ['type' => 'group']) }}"
               class="flex items-center gap-3 p-2.5 rounded-xl transition-all {{ $activeType === 'group' ? 'bg-blue-50 border border-blue-200 shadow-xs' : 'hover:bg-slate-100 text-slate-700' }}">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-bold flex items-center justify-center text-sm shadow-xs shrink-0">
                    <span class="material-symbols-outlined text-[20px]">groups</span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-xs {{ $activeType === 'group' ? 'text-blue-900' : 'text-slate-900' }}">Okara Factory Team</span>
                    </div>
                    <p class="text-[11px] text-slate-500 truncate mt-0.5">Shared room for all 3 profiles</p>
                </div>
            </a>

            <div class="px-2 pt-4 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                Direct Messages (1-on-1)
            </div>

            <!-- 2. Direct User Contacts -->
            @foreach($users as $contact)
                @php
                    $isActive = ($activeType === 'user' && $activeUser && $activeUser->id === $contact->id);
                    $unread = $unreadCounts[$contact->id] ?? 0;
                    $roleLabel = match($contact->role) {
                        'ADMIN' => 'Full Admin',
                        'MANAGER' => 'Manager',
                        'OWNER' => 'Company Owner',
                        default => $contact->role,
                    };
                    $roleColor = match($contact->role) {
                        'ADMIN' => 'bg-blue-50 text-blue-700 border-blue-200',
                        'MANAGER' => 'bg-purple-50 text-purple-700 border-purple-200',
                        'OWNER' => 'bg-amber-50 text-amber-700 border-amber-200',
                        default => 'bg-slate-100 text-slate-700',
                    };
                @endphp
                <a href="{{ route('chat.index', ['type' => 'user', 'user_id' => $contact->id]) }}"
                   class="flex items-center gap-3 p-2.5 rounded-xl transition-all relative {{ $isActive ? 'bg-blue-50 border border-blue-200 shadow-xs' : 'hover:bg-slate-100 text-slate-700' }}">
                    <div class="relative shrink-0">
                        <div class="w-10 h-10 rounded-full bg-slate-200 text-slate-800 font-bold text-xs flex items-center justify-center">
                            {{ $contact->initials() }}
                        </div>
                        <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-500 border-2 border-white rounded-full"></span>
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-xs truncate {{ $isActive ? 'text-blue-900' : 'text-slate-900' }}">{{ $contact->name }}</span>
                            @if($unread > 0)
                                <span class="px-1.5 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-600 text-white shrink-0">
                                    {{ $unread }}
                                </span>
                            @endif
                        </div>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <span class="px-1.5 py-0.2 rounded text-[9px] font-semibold border {{ $roleColor }}">
                                {{ $roleLabel }}
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    <!-- ========================================== -->
    <!-- RIGHT PANE: ACTIVE CHAT CONVERSATION       -->
    <!-- ========================================== -->
    <div class="flex-1 flex flex-col min-w-0 bg-slate-50">

        <!-- Active Chat Header -->
        <div class="px-6 py-3.5 bg-white border-b border-slate-200 flex items-center justify-between shrink-0 shadow-xs">
            <div class="flex items-center gap-3 min-w-0">
                @if($activeType === 'group')
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-bold flex items-center justify-center shadow-xs shrink-0">
                        <span class="material-symbols-outlined text-[20px]">groups</span>
                    </div>
                    <div>
                        <div class="font-bold text-slate-900 text-sm flex items-center gap-2">
                            <span>Okara Factory Team Channel</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                All 3 Users
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400">Mirza Sohail · Dr. Ghulam Abbas (Manager) · Company Owner</p>
                    </div>
                @else
                    <div class="w-10 h-10 rounded-full bg-navy-900 text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-xs">
                        {{ $activeUser->initials() }}
                    </div>
                    <div>
                        <div class="font-bold text-slate-900 text-sm flex items-center gap-2">
                            <span>{{ $activeUser->name }}</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                                {{ $activeUser->role }}
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400 font-mono">{{ $activeUser->email }}</p>
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-2 text-xs text-slate-400 font-medium">
                <span class="material-symbols-outlined text-[16px] text-emerald-600">lock</span>
                <span class="hidden sm:inline">Internal Encrypted Chat</span>
            </div>
        </div>

        <!-- Messages Feed (Scrollable Container) -->
        <div id="messages-container" class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-3.5">
            @forelse($messages as $msg)
                @php
                    $isMe = ($msg->sender_id === auth()->id());
                @endphp
                <div class="flex flex-col {{ $isMe ? 'items-end' : 'items-start' }} message-node" data-id="{{ $msg->id }}">
                    <!-- Sender Name for Group Chat -->
                    @if(!$isMe && $activeType === 'group')
                        <span class="text-[10px] font-bold text-slate-500 mb-1 ml-1 flex items-center gap-1.5">
                            <span>{{ $msg->sender->name ?? 'User' }}</span>
                            <span class="text-[9px] font-normal text-slate-400">({{ $msg->sender->role ?? '' }})</span>
                        </span>
                    @endif

                    <div class="max-w-[85%] sm:max-w-md md:max-w-lg rounded-2xl p-3.5 shadow-xs {{ $isMe ? 'bg-navy-900 text-white rounded-tr-xs' : 'bg-white text-slate-900 border border-slate-200 rounded-tl-xs' }}">
                        <!-- 1. IMAGE ATTACHMENT -->
                        @if($msg->attachment_path && $msg->isImage())
                            <div class="mb-2 overflow-hidden rounded-xl bg-black/5">
                                <a href="{{ $msg->attachment_path }}" target="_blank" rel="noopener">
                                    <img src="{{ $msg->attachment_path }}" alt="Image attachment" class="rounded-xl max-h-72 w-auto object-cover hover:scale-102 transition-transform duration-200" />
                                </a>
                            </div>
                        @endif

                        <!-- 2. AUDIO / VOICE NOTE ATTACHMENT -->
                        @if($msg->attachment_path && $msg->isAudio())
                            <div class="mb-2 p-2 rounded-xl {{ $isMe ? 'bg-navy-800 text-white' : 'bg-slate-100 text-slate-800' }}">
                                <div class="flex items-center gap-2 mb-1.5 text-xs font-semibold">
                                    <span class="material-symbols-outlined text-[18px] text-blue-400">graphic_eq</span>
                                    <span>Voice Note / Audio Clip</span>
                                    @if($msg->formattedSize())
                                        <span class="text-[10px] opacity-70">({{ $msg->formattedSize() }})</span>
                                    @endif
                                </div>
                                <audio controls class="w-full h-8" preload="metadata">
                                    <source src="{{ $msg->attachment_path }}" type="{{ $msg->attachment_mime ?: 'audio/mpeg' }}">
                                    Your browser does not support audio playback.
                                </audio>
                            </div>
                        @endif

                        <!-- 3. PDF & DOCUMENT ATTACHMENT -->
                        @if($msg->attachment_path && !$msg->isImage() && !$msg->isAudio())
                            <div class="mb-2 p-2.5 rounded-xl flex items-center justify-between gap-3 {{ $isMe ? 'bg-navy-800 text-white' : 'bg-slate-100 text-slate-800' }}">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="material-symbols-outlined text-[24px] {{ $msg->isPdf() ? 'text-red-400' : 'text-blue-400' }}">
                                        {{ $msg->isPdf() ? 'picture_as_pdf' : 'description' }}
                                    </span>
                                    <div class="min-w-0">
                                        <div class="text-xs font-bold truncate">{{ $msg->attachment_name ?: 'Document' }}</div>
                                        <div class="text-[10px] opacity-70">{{ $msg->formattedSize() }}</div>
                                    </div>
                                </div>
                                <a href="{{ $msg->attachment_path }}" download="{{ $msg->attachment_name }}"
                                   class="px-2.5 py-1 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-semibold shrink-0 flex items-center gap-1 transition-colors">
                                    <span class="material-symbols-outlined text-[14px]">download</span>
                                    <span>Download</span>
                                </a>
                            </div>
                        @endif

                        <!-- TEXT MESSAGE CONTENT -->
                        @if($msg->message_text)
                            <p class="text-xs leading-relaxed whitespace-pre-wrap select-text break-words">{{ $msg->message_text }}</p>
                        @endif

                        <!-- Timestamp & Status -->
                        <div class="flex items-center justify-end gap-1 mt-1 text-[10px] {{ $isMe ? 'text-slate-400' : 'text-slate-400' }}">
                            <span>{{ $msg->formattedTime() }}</span>
                            @if($isMe)
                                <span class="material-symbols-outlined text-[13px] {{ $msg->is_read ? 'text-blue-400' : 'text-slate-400' }}">done_all</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div id="no-messages-placeholder" class="h-full flex flex-col items-center justify-center text-center p-8 text-slate-400">
                    <span class="material-symbols-outlined text-5xl mb-2 text-slate-300">chat</span>
                    <h4 class="font-bold text-slate-700 text-sm">No messages yet in this conversation</h4>
                    <p class="text-xs text-slate-400 max-w-sm mt-1">Start chatting by typing below. You can send text, attach photos, share PDFs, or record voice memos.</p>
                </div>
            @endforelse
        </div>

        <!-- ========================================== -->
        <!-- BOTTOM INPUT & MULTIMEDIA ACTION BAR       -->
        <!-- ========================================== -->
        <div class="p-3 sm:p-4 bg-white border-t border-slate-200">
            <!-- Selected Attachment Preview Pill Bar -->
            <div id="attachment-preview-bar" class="hidden mb-2 p-2 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2 min-w-0">
                    <span id="preview-icon" class="material-symbols-outlined text-[20px] text-blue-600">attachment</span>
                    <span id="preview-filename" class="text-xs font-semibold text-slate-800 truncate">file.pdf</span>
                    <span id="preview-filesize" class="text-[10px] text-slate-500 font-mono">(0 KB)</span>
                </div>
                <button type="button" onclick="cancelAttachment()" class="p-1 rounded text-slate-400 hover:text-red-600 hover:bg-slate-200">
                    <span class="material-symbols-outlined text-[16px]">close</span>
                </button>
            </div>

            <!-- Voice Recording Status Banner -->
            <div id="voice-recording-banner" class="hidden mb-2 p-2.5 rounded-xl bg-red-50 border border-red-200 flex items-center justify-between text-red-700 text-xs font-semibold">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-600 animate-ping"></span>
                    <span>Recording Voice Memo... <strong id="voice-timer" class="font-mono ml-1">00:00</strong></span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="cancelVoiceRecording()" class="px-2.5 py-1 rounded bg-white border border-red-200 text-red-600 hover:bg-red-100">Cancel</button>
                    <button type="button" onclick="stopAndAttachVoiceRecording()" class="px-3 py-1 rounded bg-red-600 text-white hover:bg-red-700">Attach Voice Note</button>
                </div>
            </div>

            <!-- Input Form -->
            <form id="chat-form" onsubmit="handleSendMessage(event)" class="flex items-end gap-2">
                @csrf
                <input type="hidden" name="active_type" id="chat-active-type" value="{{ $activeType }}" />
                <input type="hidden" name="receiver_id" id="chat-receiver-id" value="{{ $activeUser ? $activeUser->id : '' }}" />

                <!-- Hidden Native File Input for Attachments -->
                <input type="file" id="file-input" name="attachment" class="hidden" onchange="handleFileSelected(event)"
                       accept="image/*,audio/*,application/pdf,.doc,.docx,.xls,.xlsx,.txt" />

                <!-- Paperclip Attachment Button -->
                <button type="button" onclick="document.getElementById('file-input').click()"
                        class="p-2.5 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors shrink-0"
                        title="Attach Photo, PDF, Document, or Audio File">
                    <span class="material-symbols-outlined text-[22px]">attach_file</span>
                </button>

                <!-- Voice Recording Mic Button -->
                <button type="button" id="btn-record-voice" onclick="toggleVoiceRecording()"
                        class="p-2.5 rounded-xl text-slate-500 hover:text-red-600 hover:bg-red-50 transition-colors shrink-0"
                        title="Record a Voice Note">
                    <span class="material-symbols-outlined text-[22px]">mic</span>
                </button>

                <!-- Expanding Text Area Input -->
                <div class="flex-1 relative">
                    <textarea id="message-input" name="message_text" rows="1"
                              placeholder="Type a message to {{ $activeType === 'group' ? 'the team' : ($activeUser ? $activeUser->name : 'contact') }}... (Enter to send, Shift+Enter for new line)"
                              class="w-full resize-none rounded-xl border-slate-300 text-xs py-2.5 px-3.5 focus:border-blue-600 focus:ring-blue-600 leading-normal max-h-32"
                              onkeydown="handleKeyDown(event)"></textarea>
                </div>

                <!-- Send Button -->
                <button type="submit" id="btn-send"
                        class="p-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white transition-all shrink-0 shadow-sm flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">send</span>
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const currentUserId = {{ auth()->id() }};
    const activeType = '{{ $activeType }}';
    const receiverId = '{{ $activeUser ? $activeUser->id : '' }}';
    let lastMessageId = 0;
    let isPolling = false;

    // Track highest message id already in DOM
    document.querySelectorAll('.message-node').forEach(el => {
        const id = parseInt(el.getAttribute('data-id')) || 0;
        if (id > lastMessageId) lastMessageId = id;
    });

    const messagesContainer = document.getElementById('messages-container');

    function scrollToBottom() {
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    }
    scrollToBottom();

    // Key handling: Enter submits, Shift+Enter adds new line
    function handleKeyDown(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            document.getElementById('chat-form').dispatchEvent(new Event('submit', { cancelable: true }));
        }
    }

    // Attachment File Selection Preview
    function handleFileSelected(e) {
        const file = e.target.files[0];
        if (!file) return;

        const previewBar = document.getElementById('attachment-preview-bar');
        const filenameSpan = document.getElementById('preview-filename');
        const filesizeSpan = document.getElementById('preview-filesize');
        const previewIcon = document.getElementById('preview-icon');

        filenameSpan.innerText = file.name;
        filesizeSpan.innerText = '(' + (file.size > 1048576 ? (file.size / 1048576).toFixed(1) + ' MB' : Math.round(file.size / 1024) + ' KB') + ')';

        if (file.type.startsWith('image/')) {
            previewIcon.innerText = 'image';
        } else if (file.type.startsWith('audio/')) {
            previewIcon.innerText = 'audiotrack';
        } else if (file.type.includes('pdf')) {
            previewIcon.innerText = 'picture_as_pdf';
        } else {
            previewIcon.innerText = 'description';
        }

        previewBar.classList.remove('hidden');
    }

    function cancelAttachment() {
        const fileInput = document.getElementById('file-input');
        fileInput.value = '';
        document.getElementById('attachment-preview-bar').classList.add('hidden');
    }

    // Clipboard Paste Listener (Ctrl+V Image Sharing)
    document.addEventListener('paste', (e) => {
        if (!e.clipboardData || !e.clipboardData.items) return;
        for (let item of e.clipboardData.items) {
            if (item.type.indexOf('image') !== -1) {
                const blob = item.getAsFile();
                const file = new File([blob], 'screenshot_' + Date.now() + '.png', { type: blob.type });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                document.getElementById('file-input').files = dataTransfer.files;
                handleFileSelected({ target: { files: [file] } });
                break;
            }
        }
    });

    // Voice Recording Engine (Web MediaRecorder API)
    let mediaRecorder = null;
    let recordedAudioChunks = [];
    let recordTimerInterval = null;
    let recordingStartTime = 0;

    async function toggleVoiceRecording() {
        if (mediaRecorder && mediaRecorder.state === 'recording') {
            stopAndAttachVoiceRecording();
            return;
        }

        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            mediaRecorder = new MediaRecorder(stream);
            recordedAudioChunks = [];

            mediaRecorder.ondataavailable = (e) => {
                if (e.data.size > 0) recordedAudioChunks.push(e.data);
            };

            mediaRecorder.onstop = () => {
                const audioBlob = new Blob(recordedAudioChunks, { type: 'audio/webm' });
                const audioFile = new File([audioBlob], 'voice_note_' + Date.now() + '.webm', { type: 'audio/webm' });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(audioFile);
                document.getElementById('file-input').files = dataTransfer.files;
                handleFileSelected({ target: { files: [audioFile] } });
                stream.getTracks().forEach(t => t.stop());
            };

            mediaRecorder.start();
            recordingStartTime = Date.now();
            document.getElementById('voice-recording-banner').classList.remove('hidden');

            recordTimerInterval = setInterval(() => {
                const elapsedSec = Math.floor((Date.now() - recordingStartTime) / 1000);
                const mins = String(Math.floor(elapsedSec / 60)).padStart(2, '0');
                const secs = String(elapsedSec % 60).padStart(2, '0');
                document.getElementById('voice-timer').innerText = mins + ':' + secs;
            }, 500);

        } catch (err) {
            alert('Microphone access was denied or is unavailable on this device.');
        }
    }

    function cancelVoiceRecording() {
        if (mediaRecorder && mediaRecorder.state === 'recording') {
            mediaRecorder.stop();
        }
        clearInterval(recordTimerInterval);
        document.getElementById('voice-recording-banner').classList.add('hidden');
        recordedAudioChunks = [];
        cancelAttachment();
    }

    function stopAndAttachVoiceRecording() {
        if (mediaRecorder && mediaRecorder.state === 'recording') {
            mediaRecorder.stop();
        }
        clearInterval(recordTimerInterval);
        document.getElementById('voice-recording-banner').classList.add('hidden');
    }

    // Submit Message via AJAX
    function handleSendMessage(e) {
        e.preventDefault();
        const textInput = document.getElementById('message-input');
        const fileInput = document.getElementById('file-input');
        const textVal = textInput.value.trim();

        if (!textVal && fileInput.files.length === 0) return;

        const formData = new FormData(document.getElementById('chat-form'));
        const btnSend = document.getElementById('btn-send');
        btnSend.disabled = true;

        fetch('{{ route('chat.send') }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(r => r.json())
        .then(res => {
            btnSend.disabled = false;
            if (res.success && res.message) {
                // Clear input and attachments
                textInput.value = '';
                cancelAttachment();

                // Append sent message to feed immediately
                appendMessageToDom(res.message);
                if (res.message.id > lastMessageId) lastMessageId = res.message.id;
                scrollToBottom();

                const placeholder = document.getElementById('no-messages-placeholder');
                if (placeholder) placeholder.remove();
            } else if (res.error) {
                alert(res.error);
            }
        })
        .catch(err => {
            btnSend.disabled = false;
            console.error('Send error:', err);
        });
    }

    // Render single message HTML in feed
    function appendMessageToDom(m) {
        const isMe = m.is_me;
        const div = document.createElement('div');
        div.className = `flex flex-col ${isMe ? 'items-end' : 'items-start'} message-node`;
        div.setAttribute('data-id', m.id);

        let html = '';
        if (!isMe && activeType === 'group') {
            html += `<span class="text-[10px] font-bold text-slate-500 mb-1 ml-1 flex items-center gap-1.5">
                        <span>${escapeHtml(m.sender_name)}</span>
                        <span class="text-[9px] font-normal text-slate-400">(${escapeHtml(m.sender_role)})</span>
                     </span>`;
        }

        html += `<div class="max-w-[85%] sm:max-w-md md:max-w-lg rounded-2xl p-3.5 shadow-xs ${isMe ? 'bg-navy-900 text-white rounded-tr-xs' : 'bg-white text-slate-900 border border-slate-200 rounded-tl-xs'}">`;

        // 1. Image
        if (m.attachment_path && m.attachment_type === 'image') {
            html += `<div class="mb-2 overflow-hidden rounded-xl bg-black/5">
                        <a href="${m.attachment_path}" target="_blank" rel="noopener">
                            <img src="${m.attachment_path}" class="rounded-xl max-h-72 w-auto object-cover hover:scale-102 transition-transform duration-200" />
                        </a>
                     </div>`;
        }

        // 2. Audio
        if (m.attachment_path && m.attachment_type === 'audio') {
            html += `<div class="mb-2 p-2 rounded-xl ${isMe ? 'bg-navy-800 text-white' : 'bg-slate-100 text-slate-800'}">
                        <div class="flex items-center gap-2 mb-1.5 text-xs font-semibold">
                            <span class="material-symbols-outlined text-[18px] text-blue-400">graphic_eq</span>
                            <span>Voice Note / Audio Clip</span>
                            ${m.formatted_size ? `<span class="text-[10px] opacity-70">(${m.formatted_size})</span>` : ''}
                        </div>
                        <audio controls class="w-full h-8" preload="metadata">
                            <source src="${m.attachment_path}">
                        </audio>
                     </div>`;
        }

        // 3. Document / PDF
        if (m.attachment_path && m.attachment_type !== 'image' && m.attachment_type !== 'audio') {
            const isPdf = (m.attachment_type === 'pdf');
            html += `<div class="mb-2 p-2.5 rounded-xl flex items-center justify-between gap-3 ${isMe ? 'bg-navy-800 text-white' : 'bg-slate-100 text-slate-800'}">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="material-symbols-outlined text-[24px] ${isPdf ? 'text-red-400' : 'text-blue-400'}">
                                ${isPdf ? 'picture_as_pdf' : 'description'}
                            </span>
                            <div class="min-w-0">
                                <div class="text-xs font-bold truncate">${escapeHtml(m.attachment_name || 'Document')}</div>
                                <div class="text-[10px] opacity-70">${m.formatted_size || ''}</div>
                            </div>
                        </div>
                        <a href="${m.attachment_path}" download="${escapeHtml(m.attachment_name || 'file')}"
                           class="px-2.5 py-1 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-semibold shrink-0 flex items-center gap-1 transition-colors">
                            <span class="material-symbols-outlined text-[14px]">download</span>
                            <span>Download</span>
                        </a>
                     </div>`;
        }

        // Text
        if (m.message_text) {
            html += `<p class="text-xs leading-relaxed whitespace-pre-wrap select-text break-words">${escapeHtml(m.message_text)}</p>`;
        }

        // Time & checkmark
        html += `<div class="flex items-center justify-end gap-1 mt-1 text-[10px] ${isMe ? 'text-slate-400' : 'text-slate-400'}">
                    <span>${m.formatted_time || ''}</span>
                    ${isMe ? `<span class="material-symbols-outlined text-[13px] ${m.is_read ? 'text-blue-400' : 'text-slate-400'}">done_all</span>` : ''}
                 </div>`;

        html += `</div>`;
        div.innerHTML = html;
        messagesContainer.appendChild(div);
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Real-Time Polling Engine (Every 3 seconds)
    function pollNewMessages() {
        if (isPolling) return;
        isPolling = true;

        const url = `{{ route('chat.messages') }}?type=${activeType}&receiver_id=${receiverId}&after_id=${lastMessageId}`;

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                isPolling = false;
                if (data.messages && data.messages.length > 0) {
                    const placeholder = document.getElementById('no-messages-placeholder');
                    if (placeholder) placeholder.remove();

                    data.messages.forEach(m => {
                        // Check if already rendered
                        if (!document.querySelector(`.message-node[data-id="${m.id}"]`)) {
                            appendMessageToDom(m);
                            if (m.id > lastMessageId) lastMessageId = m.id;
                        }
                    });
                    scrollToBottom();
                }
            })
            .catch(() => {
                isPolling = false;
            });
    }

    // Start 3-second live background synchronization
    setInterval(pollNewMessages, 3000);
</script>
@endpush
