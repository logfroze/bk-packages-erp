@extends('layouts.app')

@section('title', 'Factory Chat — Internal Messaging')
@section('breadcrumb', 'Factory Chat')

@section('header_actions')
    <div class="flex items-center gap-2">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-300">
            <span class="w-2 h-2 rounded-full bg-emerald-600 animate-pulse"></span>
            <span>Live Chat Active</span>
        </span>
    </div>
@endsection

@section('content')
<div class="h-[calc(100vh-145px)] min-h-[580px] bg-white rounded-xl border border-slate-300 shadow-xs flex overflow-hidden">

    <!-- ========================================== -->
    <!-- LEFT PANE: CONTACTS & CHANNELS LIST        -->
    <!-- ========================================== -->
    <div class="w-80 sm:w-88 border-r border-slate-300 flex flex-col shrink-0 bg-slate-50">
        <!-- User Self-Profile Bar -->
        <div class="p-3.5 border-b border-slate-300 bg-white flex items-center justify-between">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 rounded-lg bg-navy-900 text-white font-bold text-xs flex items-center justify-center shrink-0 border border-navy-900">
                    {{ auth()->user()->initials() }}
                </div>
                <div class="min-w-0">
                    <div class="font-bold text-slate-900 text-xs truncate">{{ auth()->user()->name }}</div>
                    <div class="text-[10px] text-slate-500 font-mono truncate">{{ auth()->user()->email }}</div>
                </div>
            </div>
        </div>

        <!-- Channels & Contacts List -->
        <div class="flex-1 overflow-y-auto p-2.5 space-y-1">
            <div class="px-2 pt-1 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                Team Channels
            </div>

            <!-- 1. Factory General Group Channel -->
            <a href="{{ route('chat.index', ['type' => 'group']) }}"
               id="conv-link-group"
               onclick="switchConversation(event, 'group', '', 'Okara Factory Team Channel', '3 Members', 'Mirza Sohail · Dr. Ghulam Abbas (Manager) · Company Owner', 'groups', true)"
               class="conversation-link flex items-center gap-3 p-2.5 rounded-lg transition-all {{ $activeType === 'group' ? 'bg-white border border-slate-400 shadow-xs' : 'hover:bg-slate-200/60 border border-transparent text-slate-700' }}">
                <div class="w-9 h-9 rounded-lg bg-slate-900 text-white font-bold flex items-center justify-center text-xs shrink-0 border border-slate-900">
                    <span class="material-symbols-outlined text-[18px]">groups</span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-xs text-slate-900">Okara Factory Team</span>
                    </div>
                    <p class="text-[11px] text-slate-500 truncate mt-0.5">Shared room for all 3 profiles</p>
                </div>
            </a>

            <div class="px-2 pt-3 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                Direct Messages (1-on-1)
            </div>

            <!-- 2. Direct User Contacts -->
            @foreach($users as $contact)
                @php
                    $isActive = ($activeType === 'user' && $activeUser && $activeUser->id === $contact->id);
                    $unread = $unreadCounts[$contact->id] ?? 0;
                    $roleLabel = match($contact->role) {
                        'ADMIN' => 'Admin',
                        'MANAGER' => 'Manager',
                        'OWNER' => 'Owner',
                        default => $contact->role,
                    };
                @endphp
                <a href="{{ route('chat.index', ['type' => 'user', 'user_id' => $contact->id]) }}"
                   id="conv-link-user-{{ $contact->id }}"
                   onclick="switchConversation(event, 'user', {{ $contact->id }}, @js($contact->name), @js($roleLabel), @js($contact->email), @js($contact->initials()), false)"
                   class="conversation-link flex items-center gap-3 p-2.5 rounded-lg transition-all relative {{ $isActive ? 'bg-white border border-slate-400 shadow-xs' : 'hover:bg-slate-200/60 border border-transparent text-slate-700' }}">
                    <div class="relative shrink-0">
                        <div class="w-9 h-9 rounded-lg bg-slate-200 text-slate-800 font-bold text-xs flex items-center justify-center border border-slate-300">
                            {{ $contact->initials() }}
                        </div>
                        <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-emerald-600 border-2 border-white rounded-full"></span>
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-xs truncate text-slate-900">{{ $contact->name }}</span>
                            @if($unread > 0)
                                <span class="conv-unread-badge px-1.5 py-0.2 rounded text-[10px] font-extrabold bg-slate-900 text-white shrink-0">
                                    {{ $unread }}
                                </span>
                            @endif
                        </div>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <span class="px-1.5 py-0.2 rounded text-[9px] font-medium border border-slate-300 bg-slate-100 text-slate-600">
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
        <div class="px-5 py-3 bg-white border-b border-slate-300 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3 min-w-0">
                <div id="chat-header-avatar" class="w-9 h-9 rounded-lg {{ $activeType === 'group' ? 'bg-slate-900 border-slate-900' : 'bg-navy-900 border-navy-900' }} text-white font-bold text-xs flex items-center justify-center shrink-0 border">
                    @if($activeType === 'group')
                        <span class="material-symbols-outlined text-[18px]">groups</span>
                    @else
                        {{ $activeUser->initials() }}
                    @endif
                </div>
                <div>
                    <div class="font-bold text-slate-900 text-xs sm:text-sm flex items-center gap-2">
                        <span id="chat-header-title">{{ $activeType === 'group' ? 'Okara Factory Team Channel' : $activeUser->name }}</span>
                        <span id="chat-header-badge" class="px-2 py-0.2 rounded text-[9px] font-bold bg-slate-100 text-slate-700 border border-slate-300">
                            {{ $activeType === 'group' ? '3 Members' : $activeUser->role }}
                        </span>
                    </div>
                    <p id="chat-header-sub" class="text-[10px] text-slate-500 font-mono">
                        {{ $activeType === 'group' ? 'Mirza Sohail · Dr. Ghulam Abbas (Manager) · Company Owner' : $activeUser->email }}
                    </p>
                </div>
            </div>

            <!-- Action buttons: Minimalist Solid Clean Style -->
            <div class="flex items-center gap-2">
                <!-- Select Multiple Messages Trigger -->
                <button type="button" id="btn-toggle-select" onclick="toggleMultiSelectMode()"
                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-700 bg-white hover:bg-slate-100 border border-slate-300 shadow-2xs transition-colors flex items-center gap-1.5"
                        title="Select Multiple Messages to Delete">
                    <span class="material-symbols-outlined text-[16px] text-slate-600">checklist</span>
                    <span class="hidden md:inline" id="btn-select-text">Select</span>
                </button>

                <!-- Storage Management & Auto-Clean Modal Trigger -->
                <button type="button" onclick="openModal('modal-storage-cleanup')"
                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-700 bg-white hover:bg-slate-100 border border-slate-300 shadow-2xs transition-colors flex items-center gap-1.5"
                        title="Chat Storage Management & Auto-Clean">
                    <span class="material-symbols-outlined text-[16px] text-slate-600">cleaning_services</span>
                    <span class="hidden md:inline">Storage Clean</span>
                </button>

                <!-- Clear Active Chat History -->
                <button type="button" onclick="openModal('modal-clear-chat')"
                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-700 bg-white hover:bg-slate-100 border border-slate-300 shadow-2xs transition-colors flex items-center gap-1.5"
                        title="Clear all messages in this conversation">
                    <span class="material-symbols-outlined text-[16px] text-slate-600">delete_sweep</span>
                    <span class="hidden md:inline">Clear Chat</span>
                </button>

                <div class="h-4 w-px bg-slate-300 mx-1 hidden sm:block"></div>

                <div class="flex items-center gap-1 text-xs text-slate-500 font-medium">
                    <span class="material-symbols-outlined text-[15px] text-slate-500">lock</span>
                    <span class="hidden lg:inline text-[11px]">Encrypted</span>
                </div>
            </div>
        </div>

        <!-- Messages Feed (Scrollable Container) -->
        <div id="messages-container" class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-3">
            @forelse($messages as $msg)
                @php
                    $isMe = ($msg->sender_id === auth()->id());
                    $canModify = $isMe || auth()->user()->isFullAdmin();
                @endphp
                <div class="flex {{ $isMe ? 'flex-row-reverse' : 'flex-row' }} items-center gap-2 w-full message-row group relative" data-id="{{ $msg->id }}">
                    <!-- Multi-Select Checkbox -->
                    <div class="msg-select-wrap hidden items-center px-1 shrink-0">
                        <input type="checkbox" class="msg-checkbox w-4 h-4 rounded border-slate-400 text-slate-900 focus:ring-slate-900 cursor-pointer" data-id="{{ $msg->id }}" onchange="handleMessageCheckboxChange(event, {{ $msg->id }})">
                    </div>

                    <div class="flex flex-col {{ $isMe ? 'items-end' : 'items-start' }} message-node relative max-w-[85%] sm:max-w-md md:max-w-lg" data-id="{{ $msg->id }}">
                        <!-- Sender Name for Group Chat -->
                        @if(!$isMe && $activeType === 'group' && !$msg->is_deleted)
                            <span class="text-[10px] font-bold text-slate-600 mb-1 ml-1 flex items-center gap-1.5">
                                <span>{{ $msg->sender->name ?? 'User' }}</span>
                                <span class="text-[9px] font-normal text-slate-400">({{ $msg->sender->role ?? '' }})</span>
                            </span>
                        @endif

                        <div class="relative w-full">
                            <!-- Floating Action Pill Menu (WhatsApp-style Edit & Delete) -->
                            @if($canModify && !$msg->is_deleted)
                                <div class="absolute -top-3 {{ $isMe ? 'right-2' : 'left-2' }} hidden group-hover:flex items-center gap-0.5 bg-white shadow-xs border border-slate-300 rounded-lg p-0.5 z-10">
                                    @if($msg->message_text)
                                        <button type="button" onclick="startEditMessage({{ $msg->id }}, @js($msg->message_text))"
                                                class="p-1 rounded text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors"
                                                title="Edit Message">
                                            <span class="material-symbols-outlined text-[15px]">edit</span>
                                        </button>
                                    @endif
                                    <button type="button" onclick="confirmDeleteMessage({{ $msg->id }})"
                                            class="p-1 rounded text-slate-600 hover:text-rose-700 hover:bg-slate-100 transition-colors"
                                            title="Delete Message">
                                        <span class="material-symbols-outlined text-[15px]">delete</span>
                                    </button>
                                </div>
                            @endif

                            <!-- Message Content Bubble -->
                            @if($msg->is_deleted)
                                <!-- WhatsApp-Style Deleted Message Placeholder -->
                                <div class="rounded-xl px-3.5 py-2.5 shadow-2xs italic text-xs flex items-center gap-2 bg-slate-100 text-slate-600 border border-slate-300 {{ $isMe ? 'rounded-tr-xs' : 'rounded-tl-xs' }}">
                                    <span class="material-symbols-outlined text-[15px] text-slate-500">block</span>
                                    <span>{{ ($msg->deleted_by === auth()->id()) ? 'You deleted this message' : 'This message was deleted' }}</span>
                                    <span class="text-[10px] text-slate-400 not-italic ml-auto">{{ $msg->formattedTime() }}</span>
                                </div>
                            @else
                                <div class="rounded-xl p-3.5 shadow-2xs {{ $isMe ? 'bg-navy-900 text-white border border-navy-800 rounded-tr-xs' : 'bg-white text-slate-900 border border-slate-300 rounded-tl-xs' }}">
                                    <!-- 1. IMAGE ATTACHMENT -->
                                    @if($msg->attachment_path && $msg->isImage())
                                        <div class="mb-2 overflow-hidden rounded-lg bg-black/5 border border-slate-300/50">
                                            <a href="{{ $msg->attachment_path }}" target="_blank" rel="noopener">
                                                <img src="{{ $msg->attachment_path }}" alt="Image attachment" class="rounded-lg max-h-72 w-auto object-cover hover:opacity-95 transition-opacity" />
                                            </a>
                                        </div>
                                    @endif

                                    <!-- 2. AUDIO / VOICE NOTE ATTACHMENT -->
                                    @if($msg->attachment_path && $msg->isAudio())
                                        <div class="mb-2 p-2 rounded-lg {{ $isMe ? 'bg-navy-800 text-white border border-navy-700' : 'bg-slate-100 text-slate-800 border border-slate-300' }}">
                                            <div class="flex items-center gap-2 mb-1.5 text-xs font-semibold">
                                                <span class="material-symbols-outlined text-[17px] text-slate-300">graphic_eq</span>
                                                <span>Voice Note</span>
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
                                        <div class="mb-2 p-2.5 rounded-lg flex items-center justify-between gap-3 {{ $isMe ? 'bg-navy-800 text-white border border-navy-700' : 'bg-slate-100 text-slate-800 border border-slate-300' }}">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <span class="material-symbols-outlined text-[22px] {{ $msg->isPdf() ? 'text-red-400' : 'text-slate-300' }}">
                                                    {{ $msg->isPdf() ? 'picture_as_pdf' : 'description' }}
                                                </span>
                                                <div class="min-w-0">
                                                    <div class="text-xs font-bold truncate">{{ $msg->attachment_name ?: 'Document' }}</div>
                                                    <div class="text-[10px] opacity-70">{{ $msg->formattedSize() }}</div>
                                                </div>
                                            </div>
                                            <a href="{{ $msg->attachment_path }}" download="{{ $msg->attachment_name }}"
                                               class="px-2.5 py-1 rounded bg-slate-900 hover:bg-black text-white border border-slate-700 text-[11px] font-semibold shrink-0 flex items-center gap-1 transition-colors">
                                                <span class="material-symbols-outlined text-[13px]">download</span>
                                                <span>Download</span>
                                            </a>
                                        </div>
                                    @endif

                                    <!-- TEXT MESSAGE CONTENT -->
                                    @if($msg->message_text)
                                        <p class="text-xs leading-relaxed whitespace-pre-wrap select-text break-words message-body">{{ $msg->message_text }}</p>
                                    @endif

                                    <!-- Timestamp, Edited status & Read Receipt -->
                                    <div class="flex items-center justify-end gap-1 mt-1 text-[10px] {{ $isMe ? 'text-slate-400' : 'text-slate-400' }}">
                                        @if($msg->is_edited)
                                            <span class="text-[9px] text-slate-400 italic font-normal mr-0.5">(edited)</span>
                                        @endif
                                        <span>{{ $msg->formattedTime() }}</span>
                                        @if($isMe)
                                            <span class="material-symbols-outlined text-[13px] {{ $msg->is_read ? 'text-blue-400' : 'text-slate-400' }}">done_all</span>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div id="no-messages-placeholder" class="h-full flex flex-col items-center justify-center text-center p-8 text-slate-400">
                    <span class="material-symbols-outlined text-4xl mb-2 text-slate-400">chat</span>
                    <h4 class="font-bold text-slate-700 text-xs sm:text-sm">No messages yet in this conversation</h4>
                    <p class="text-xs text-slate-400 max-w-sm mt-1">Start chatting by typing below. Send text, attach photos, or record voice memos.</p>
                </div>
            @endforelse
        </div>

        <!-- ========================================== -->
        <!-- BOTTOM INPUT & MULTIMEDIA ACTION BAR       -->
        <!-- ========================================== -->
        <div class="p-3 bg-white border-t border-slate-300">
            <!-- Multi-Select Floating Action Bar -->
            <div id="multi-select-bar" class="hidden mb-2 p-2.5 rounded-lg bg-slate-900 text-white border border-slate-800 shadow-md flex items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="material-symbols-outlined text-[18px] text-amber-400">checklist</span>
                    <span id="selected-count-label" class="font-bold text-white font-mono">0 messages selected</span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="selectAllMessages()" class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 transition-colors">
                        Select All
                    </button>
                    <button type="button" onclick="toggleMultiSelectMode(false)" class="px-2.5 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold border border-slate-700 transition-colors">
                        Cancel
                    </button>
                    <button type="button" id="btn-delete-selected" onclick="confirmDeleteSelected()" disabled
                            class="px-3 py-1 rounded bg-rose-600 hover:bg-rose-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs font-bold transition-colors flex items-center gap-1 shadow-xs">
                        <span class="material-symbols-outlined text-[15px]">delete</span>
                        <span>Delete Selected</span>
                    </button>
                </div>
            </div>

            <!-- Inline Message Editing Banner -->
            <div id="editing-banner" class="hidden mb-2 p-2 rounded-lg bg-slate-100 border border-slate-300 flex items-center justify-between gap-3 text-slate-800 text-xs">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="material-symbols-outlined text-[17px] text-slate-700 shrink-0">edit</span>
                    <div class="min-w-0 truncate">
                        <span class="font-bold text-slate-900">Editing Message:</span>
                        <span id="editing-preview-text" class="text-slate-600 italic ml-1 text-[11px]"></span>
                    </div>
                </div>
                <button type="button" onclick="cancelEditing()" class="p-1 rounded text-slate-500 hover:text-slate-900 hover:bg-slate-200 transition-colors shrink-0" title="Cancel edit">
                    <span class="material-symbols-outlined text-[15px]">close</span>
                </button>
            </div>

            <!-- Selected Attachment Preview Pill Bar -->
            <div id="attachment-preview-bar" class="hidden mb-2 p-2 rounded-lg bg-slate-50 border border-slate-300 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2 min-w-0">
                    <span id="preview-icon" class="material-symbols-outlined text-[18px] text-slate-700">attachment</span>
                    <span id="preview-filename" class="text-xs font-semibold text-slate-800 truncate">file.pdf</span>
                    <span id="preview-filesize" class="text-[10px] text-slate-500 font-mono">(0 KB)</span>
                </div>
                <button type="button" onclick="cancelAttachment()" class="p-1 rounded text-slate-400 hover:text-slate-800 hover:bg-slate-200 transition-colors">
                    <span class="material-symbols-outlined text-[15px]">close</span>
                </button>
            </div>

            <!-- Voice Recording Status Banner -->
            <div id="voice-recording-banner" class="hidden mb-2 p-2 rounded-lg bg-slate-100 border border-slate-300 flex items-center justify-between text-slate-800 text-xs font-semibold">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-600 animate-ping"></span>
                    <span>Recording Voice Memo... <strong id="voice-timer" class="font-mono ml-1 text-slate-900">00:00</strong></span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="cancelVoiceRecording()" class="px-2.5 py-1 rounded bg-white border border-slate-300 text-slate-700 hover:bg-slate-100">Cancel</button>
                    <button type="button" onclick="stopAndAttachVoiceRecording()" class="px-3 py-1 rounded bg-slate-900 text-white border border-slate-900 hover:bg-black">Attach Memo</button>
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
                        class="p-2 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 border border-transparent hover:border-slate-300 transition-colors shrink-0"
                        title="Attach Photo, PDF, Document, or Audio File">
                    <span class="material-symbols-outlined text-[20px]">attach_file</span>
                </button>

                <!-- Voice Recording Mic Button -->
                <button type="button" id="btn-record-voice" onclick="toggleVoiceRecording()"
                        class="p-2 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 border border-transparent hover:border-slate-300 transition-colors shrink-0"
                        title="Record a Voice Note">
                    <span class="material-symbols-outlined text-[20px]">mic</span>
                </button>

                <!-- Expanding Text Area Input -->
                <div class="flex-1 relative">
                    <textarea id="message-input" name="message_text" rows="1"
                              placeholder="Type a message to {{ $activeType === 'group' ? 'the team' : ($activeUser ? $activeUser->name : 'contact') }}... (Enter to send, Shift+Enter for new line)"
                              class="w-full resize-none rounded-lg border border-slate-300 text-xs py-2 px-3 focus:border-slate-900 focus:ring-slate-900 leading-normal max-h-32"
                              onkeydown="handleKeyDown(event)"></textarea>
                </div>

                <!-- Send / Save Button -->
                <button type="submit" id="btn-send"
                        class="p-2 rounded-lg bg-slate-900 hover:bg-black text-white border border-slate-900 transition-all shrink-0 shadow-xs flex items-center justify-center"
                        title="Send Message">
                    <span id="btn-send-icon" class="material-symbols-outlined text-[18px]">send</span>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL 1: CONFIRM DELETE MESSAGE (SOLID)    -->
<!-- ========================================== -->
<div id="modal-delete-message" class="hidden fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeModal('modal-delete-message')"></div>
        <div class="relative inline-block w-full max-w-sm overflow-hidden text-left align-middle transition-all transform bg-white rounded-xl border border-slate-300 shadow-xl">
            <!-- Header -->
            <div class="px-5 py-3.5 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <div class="flex items-center gap-2 text-slate-900 font-bold text-sm">
                    <span class="material-symbols-outlined text-[18px] text-slate-700">delete</span>
                    <span>Delete Message?</span>
                </div>
                <button type="button" onclick="closeModal('modal-delete-message')" class="text-slate-400 hover:text-slate-700">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>

            <!-- Body -->
            <div class="p-5">
                <p class="text-xs text-slate-600 leading-relaxed">
                    This message will be deleted for everyone in this conversation. Any attached media file will also be permanently unlinked and purged from disk to reclaim storage space.
                </p>
            </div>

            <!-- Footer -->
            <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" onclick="closeModal('modal-delete-message')"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-slate-700 bg-white hover:bg-slate-100 border border-slate-300 transition-colors">
                    Cancel
                </button>
                <button type="button" id="btn-confirm-delete" onclick="executeDeleteMessage()"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-white bg-slate-900 hover:bg-black border border-slate-900 transition-colors flex items-center gap-1.5 shadow-xs">
                    <span class="material-symbols-outlined text-[15px]">delete</span>
                    <span>Delete for Everyone</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: CONFIRM BULK DELETE MESSAGES        -->
<!-- ========================================== -->
<div id="modal-bulk-delete" class="hidden fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeModal('modal-bulk-delete')"></div>
        <div class="relative inline-block w-full max-w-sm overflow-hidden text-left align-middle transition-all transform bg-white rounded-xl border border-slate-300 shadow-xl">
            <!-- Header -->
            <div class="px-5 py-3.5 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <div class="flex items-center gap-2 text-slate-900 font-bold text-sm">
                    <span class="material-symbols-outlined text-[18px] text-rose-600">delete</span>
                    <span>Delete Selected Messages?</span>
                </div>
                <button type="button" onclick="closeModal('modal-bulk-delete')" class="text-slate-400 hover:text-slate-700">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>

            <!-- Body -->
            <div class="p-5">
                <p id="bulk-delete-confirm-text" class="text-xs text-slate-600 leading-relaxed">
                    Are you sure you want to delete these messages for everyone? Any attached media files will also be permanently purged from the server disk.
                </p>
            </div>

            <!-- Footer -->
            <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" onclick="closeModal('modal-bulk-delete')"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-slate-700 bg-white hover:bg-slate-100 border border-slate-300 transition-colors">
                    Cancel
                </button>
                <button type="button" id="btn-execute-bulk-delete" onclick="executeBulkDelete()"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 transition-colors flex items-center gap-1.5 shadow-xs">
                    <span class="material-symbols-outlined text-[15px]">delete_forever</span>
                    <span>Delete for Everyone</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL 2: CONFIRM CLEAR CHAT (SOLID)        -->
<!-- ========================================== -->
<div id="modal-clear-chat" class="hidden fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeModal('modal-clear-chat')"></div>
        <div class="relative inline-block w-full max-w-md overflow-hidden text-left align-middle transition-all transform bg-white rounded-xl border border-slate-300 shadow-xl">
            <!-- Solid Minimalist Header -->
            <div class="px-5 py-3.5 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <div class="flex items-center gap-2 text-slate-900 font-bold text-sm">
                    <span class="material-symbols-outlined text-[18px] text-slate-700">delete_sweep</span>
                    <span>Clear Entire Conversation</span>
                </div>
                <button type="button" onclick="closeModal('modal-clear-chat')" class="text-slate-400 hover:text-slate-700">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>

            <!-- Solid Body -->
            <div class="p-5 space-y-3.5">
                <p class="text-xs text-slate-700 leading-relaxed">
                    You are about to delete all chat history in <strong class="text-slate-900">{{ $activeType === 'group' ? 'Okara Factory Team Channel' : ($activeUser ? $activeUser->name : 'this chat') }}</strong>.
                </p>

                <!-- Solid Neutral Notice -->
                <div class="p-3 rounded-lg bg-slate-50 border border-slate-300 text-slate-700 text-xs">
                    <div class="font-semibold text-slate-900 mb-1 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px] text-slate-600">info</span>
                        <span>Storage Space Reclamation</span>
                    </div>
                    <p class="text-[11px] text-slate-600 leading-relaxed">
                        All images, voice notes, and document files attached in this conversation will be permanently unlinked and deleted from server disk. This cannot be undone.
                    </p>
                </div>
            </div>

            <!-- Solid Footer Buttons -->
            <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" onclick="closeModal('modal-clear-chat')"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-slate-700 bg-white hover:bg-slate-100 border border-slate-300 transition-colors">
                    Cancel
                </button>
                <button type="button" id="btn-confirm-clear" onclick="executeClearChat()"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-white bg-slate-900 hover:bg-black border border-slate-900 transition-colors flex items-center gap-1.5 shadow-xs">
                    <span class="material-symbols-outlined text-[15px]">delete</span>
                    <span>Yes, Clear Chat & Storage</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL 3: STORAGE CLEANUP & AUTO-CLEAN      -->
<!-- ========================================== -->
<div id="modal-storage-cleanup" class="hidden fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeModal('modal-storage-cleanup')"></div>
        <div class="relative inline-block w-full max-w-md overflow-hidden text-left align-middle transition-all transform bg-white rounded-xl border border-slate-300 shadow-xl">
            <!-- Solid Minimalist Header -->
            <div class="px-5 py-3.5 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <div class="flex items-center gap-2 text-slate-900 font-bold text-sm">
                    <span class="material-symbols-outlined text-[18px] text-slate-700">cleaning_services</span>
                    <span>Chat Storage Management & Cleanup</span>
                </div>
                <button type="button" onclick="closeModal('modal-storage-cleanup')" class="text-slate-400 hover:text-slate-700">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>
            </div>

            <!-- Body -->
            <div class="p-5 space-y-3">
                <p class="text-xs text-slate-600 leading-relaxed">
                    Reclaim server disk space by deleting older messages, large image uploads, and voice memos.
                </p>

                <div class="space-y-2">
                    <label class="flex items-center justify-between p-3 rounded-lg border border-slate-300 hover:border-slate-500 bg-white cursor-pointer transition-colors">
                        <div class="flex items-center gap-3">
                            <input type="radio" name="clean_period" value="24_hours" class="text-slate-900 focus:ring-slate-900 border-slate-400" checked />
                            <div>
                                <div class="text-xs font-semibold text-slate-900">Older than 24 Hours (Daily Clean)</div>
                                <div class="text-[11px] text-slate-500">Purge daily voice notes & ephemeral photos older than 1 day</div>
                            </div>
                        </div>
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded border border-slate-300 bg-slate-100 text-slate-700">Default</span>
                    </label>

                    <label class="flex items-center justify-between p-3 rounded-lg border border-slate-300 hover:border-slate-500 bg-white cursor-pointer transition-colors">
                        <div class="flex items-center gap-3">
                            <input type="radio" name="clean_period" value="7_days" class="text-slate-900 focus:ring-slate-900 border-slate-400" />
                            <div>
                                <div class="text-xs font-semibold text-slate-900">Older than 7 Days (Weekly Clean)</div>
                                <div class="text-[11px] text-slate-500">Preserve this week's history; purge older files & chats</div>
                            </div>
                        </div>
                    </label>

                    <label class="flex items-center justify-between p-3 rounded-lg border border-slate-300 hover:border-slate-500 bg-white cursor-pointer transition-colors">
                        <div class="flex items-center gap-3">
                            <input type="radio" name="clean_period" value="30_days" class="text-slate-900 focus:ring-slate-900 border-slate-400" />
                            <div>
                                <div class="text-xs font-semibold text-slate-900">Older than 30 Days (Monthly Archive)</div>
                                <div class="text-[11px] text-slate-500">Purge chat records & attachments older than a month</div>
                            </div>
                        </div>
                    </label>

                    <label class="flex items-center justify-between p-3 rounded-lg border border-slate-300 hover:border-slate-500 bg-white cursor-pointer transition-colors">
                        <div class="flex items-center gap-3">
                            <input type="radio" name="clean_period" value="all" class="text-slate-900 focus:ring-slate-900 border-slate-400" />
                            <div>
                                <div class="text-xs font-semibold text-slate-900">Purge All Historical Chat & Media</div>
                                <div class="text-[11px] text-slate-500">Completely wipe all chat messages and attachments</div>
                            </div>
                        </div>
                    </label>
                </div>

                <!-- Clean Solid Cron Note -->
                <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-300 text-slate-600 text-[11px] flex items-center justify-between">
                    <span class="font-medium text-slate-700">Automated Daily Cleanup:</span>
                    <code class="font-mono bg-white px-2 py-0.5 rounded border border-slate-300 text-slate-800 text-[10px]">php artisan chat:prune --days=30 (02:00 AM)</code>
                </div>
            </div>

            <!-- Solid Footer Buttons -->
            <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" onclick="closeModal('modal-storage-cleanup')"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-slate-700 bg-white hover:bg-slate-100 border border-slate-300 transition-colors">
                    Cancel
                </button>
                <button type="button" id="btn-run-autoclean" onclick="executeAutoClean()"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-white bg-slate-900 hover:bg-black border border-slate-900 transition-colors flex items-center gap-1.5 shadow-xs">
                    <span class="material-symbols-outlined text-[15px]">cleaning_services</span>
                    <span>Execute Cleanup</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const currentUserId = {{ auth()->id() }};
    let currentActiveType = '{{ $activeType }}';
    let currentReceiverId = '{{ $activeUser ? $activeUser->id : '' }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    let lastMessageId = 0;
    let lastSyncTime = '{{ now()->toIso8601String() }}';
    let isPolling = false;
    let editingMessageId = null;
    let deletingMessageId = null;

    // Multi-Select Delete State
    let isSelectMode = false;
    const selectedMessageIds = new Set();

    // In-Memory Instant Client Cache
    const conversationCache = {};

    // Track highest message id already in DOM
    document.querySelectorAll('.message-row').forEach(el => {
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

    // ========================================================
    // MULTI-SELECT MESSAGES & BULK DELETE ENGINE
    // ========================================================
    function toggleMultiSelectMode(forcedState) {
        isSelectMode = (forcedState !== undefined) ? forcedState : !isSelectMode;
        const selectBar = document.getElementById('multi-select-bar');
        const selectBtnText = document.getElementById('btn-select-text');
        const selectWraps = document.querySelectorAll('.msg-select-wrap');

        if (isSelectMode) {
            if (selectBar) selectBar.classList.remove('hidden');
            if (selectBtnText) selectBtnText.innerText = 'Cancel';
            selectWraps.forEach(el => el.classList.remove('hidden'));
        } else {
            if (selectBar) selectBar.classList.add('hidden');
            if (selectBtnText) selectBtnText.innerText = 'Select';
            selectWraps.forEach(el => el.classList.add('hidden'));
            selectedMessageIds.clear();
            document.querySelectorAll('.msg-checkbox').forEach(cb => { cb.checked = false; });
            updateSelectBar();
        }
    }

    function handleMessageCheckboxChange(e, id) {
        if (e.target.checked) {
            selectedMessageIds.add(id);
        } else {
            selectedMessageIds.delete(id);
        }
        updateSelectBar();
    }

    function updateSelectBar() {
        const count = selectedMessageIds.size;
        const label = document.getElementById('selected-count-label');
        const btnDelete = document.getElementById('btn-delete-selected');
        if (label) label.innerText = `${count} message${count === 1 ? '' : 's'} selected`;
        if (btnDelete) btnDelete.disabled = (count === 0);
    }

    function selectAllMessages() {
        const checkboxes = document.querySelectorAll('.msg-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = true;
            const id = parseInt(cb.getAttribute('data-id'));
            if (id) selectedMessageIds.add(id);
        });
        updateSelectBar();
    }

    function confirmDeleteSelected() {
        const count = selectedMessageIds.size;
        if (count === 0) return;
        const textEl = document.getElementById('bulk-delete-confirm-text');
        if (textEl) {
            textEl.innerText = `Are you sure you want to delete ${count} selected message${count === 1 ? '' : 's'} for everyone? Any attached photos, documents, or voice notes will also be permanently removed from disk.`;
        }
        openModal('modal-bulk-delete');
    }

    function executeBulkDelete() {
        const ids = Array.from(selectedMessageIds);
        if (ids.length === 0) return;

        const btn = document.getElementById('btn-execute-bulk-delete');
        btn.disabled = true;

        fetch('{{ route('chat.delete_multiple') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ message_ids: ids })
        })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            closeModal('modal-bulk-delete');
            if (res.success && res.messages) {
                res.messages.forEach(m => {
                    updateMessageInDom(m);
                });
                toggleMultiSelectMode(false);
            } else if (res.error) {
                alert(res.error);
            }
        })
        .catch(err => {
            btn.disabled = false;
            closeModal('modal-bulk-delete');
            console.error('Bulk delete error:', err);
        });
    }

    // ========================================================
    // LIGHTNING-FAST INSTANT CONVERSATION SWITCHER (0ms SPA)
    // ========================================================
    function switchConversation(e, type, userId, name, role, subtext, iconOrInitials, isGroup) {
        if (e) e.preventDefault();

        // If clicking currently active conversation, do nothing
        if (currentActiveType === type && String(currentReceiverId) === String(userId || '')) {
            return;
        }

        // Cancel edit or select mode if in progress
        if (editingMessageId) cancelEditing();
        if (isSelectMode) toggleMultiSelectMode(false);
        cancelAttachment();

        // 1. Update State Variables Immediately
        currentActiveType = type;
        currentReceiverId = userId ? String(userId) : '';
        lastSyncTime = new Date().toISOString();

        // 2. Update Left Sidebar Active Highlight Instantly
        document.querySelectorAll('.conversation-link').forEach(link => {
            link.classList.remove('bg-white', 'border-slate-400', 'shadow-xs');
            link.classList.add('hover:bg-slate-200/60', 'border-transparent', 'text-slate-700');
        });

        const activeLink = isGroup 
            ? document.getElementById('conv-link-group')
            : document.getElementById(`conv-link-user-${userId}`);
        if (activeLink) {
            activeLink.classList.remove('hover:bg-slate-200/60', 'border-transparent', 'text-slate-700');
            activeLink.classList.add('bg-white', 'border-slate-400', 'shadow-xs');
            const badge = activeLink.querySelector('.conv-unread-badge');
            if (badge) badge.remove();
        }

        // 3. Update Active Header Instantly (0ms response)
        const headerTitle = document.getElementById('chat-header-title');
        const headerBadge = document.getElementById('chat-header-badge');
        const headerSub = document.getElementById('chat-header-sub');
        const headerAvatar = document.getElementById('chat-header-avatar');

        if (headerTitle) headerTitle.innerText = name;
        if (headerBadge) headerBadge.innerText = role;
        if (headerSub) headerSub.innerText = subtext;
        if (headerAvatar) {
            if (isGroup) {
                headerAvatar.className = 'w-9 h-9 rounded-lg bg-slate-900 border-slate-900 text-white font-bold text-xs flex items-center justify-center shrink-0 border';
                headerAvatar.innerHTML = '<span class="material-symbols-outlined text-[18px]">groups</span>';
            } else {
                headerAvatar.className = 'w-9 h-9 rounded-lg bg-navy-900 border-navy-900 text-white font-bold text-xs flex items-center justify-center shrink-0 border';
                headerAvatar.innerHTML = escapeHtml(iconOrInitials);
            }
        }

        // 4. Update Form Inputs & Placeholder
        const inputType = document.getElementById('chat-active-type');
        const inputReceiver = document.getElementById('chat-receiver-id');
        const messageInput = document.getElementById('message-input');

        if (inputType) inputType.value = type;
        if (inputReceiver) inputReceiver.value = userId || '';
        if (messageInput) {
            messageInput.placeholder = `Type a message to ${name}... (Enter to send, Shift+Enter for new line)`;
        }

        // 5. Update Address Bar without Reload
        const newUrl = isGroup ? `{{ route('chat.index', ['type' => 'group']) }}` : `{{ route('chat.index') }}?type=user&user_id=${userId}`;
        window.history.pushState({ type, userId, name, role, subtext, iconOrInitials, isGroup }, '', newUrl);

        // 6. Check In-Memory Cache or Fetch Fresh
        const cacheKey = `${type}_${userId || 'group'}`;
        if (conversationCache[cacheKey]) {
            renderConversationMessages(conversationCache[cacheKey]);
            pollNewMessages();
        } else {
            messagesContainer.innerHTML = `
                <div class="h-full flex flex-col items-center justify-center text-center p-8 text-slate-400">
                    <span class="material-symbols-outlined text-3xl mb-2 text-slate-400 animate-spin">progress_activity</span>
                    <p class="text-xs text-slate-500 font-semibold">Loading conversation...</p>
                </div>`;
            lastMessageId = 0;

            fetch(`{{ route('chat.messages') }}?type=${type}&receiver_id=${userId || ''}&after_id=0`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.messages) {
                    conversationCache[cacheKey] = data.messages;
                    if (currentActiveType === type && String(currentReceiverId) === String(userId || '')) {
                        renderConversationMessages(data.messages);
                    }
                }
            })
            .catch(err => {
                console.error('Failed to load conversation:', err);
            });
        }
    }

    function renderConversationMessages(messages) {
        messagesContainer.innerHTML = '';
        lastMessageId = 0;

        if (!messages || messages.length === 0) {
            messagesContainer.innerHTML = `
                <div id="no-messages-placeholder" class="h-full flex flex-col items-center justify-center text-center p-8 text-slate-400">
                    <span class="material-symbols-outlined text-4xl mb-2 text-slate-400">chat</span>
                    <h4 class="font-bold text-slate-700 text-xs sm:text-sm">No messages yet in this conversation</h4>
                    <p class="text-xs text-slate-400 max-w-sm mt-1">Start chatting by typing below. Send text, attach photos, or record voice memos.</p>
                </div>`;
            return;
        }

        messages.forEach(m => {
            const div = document.createElement('div');
            const isMe = m.is_me;
            div.className = `flex ${isMe ? 'flex-row-reverse' : 'flex-row'} items-center gap-2 w-full message-row group relative`;
            div.setAttribute('data-id', m.id);
            div.innerHTML = buildMessageRowInnerHtml(m);
            messagesContainer.appendChild(div);
            if (m.id > lastMessageId) lastMessageId = m.id;
        });

        scrollToBottom();
    }

    // Handle Browser Back / Forward Buttons Seamlessly
    window.addEventListener('popstate', (e) => {
        if (e.state && e.state.type) {
            switchConversation(null, e.state.type, e.state.userId, e.state.name, e.state.role, e.state.subtext, e.state.iconOrInitials, e.state.isGroup);
        }
    });

    // Key handling: Enter submits, Shift+Enter adds new line
    function handleKeyDown(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            document.getElementById('chat-form').dispatchEvent(new Event('submit', { cancelable: true }));
        } else if (e.key === 'Escape' && editingMessageId) {
            cancelEditing();
        }
    }

    // Attachment File Selection Preview
    function handleFileSelected(e) {
        const file = e.target.files[0];
        if (!file) return;

        if (editingMessageId) cancelEditing();

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
        if (editingMessageId) cancelEditing();

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

    // ========================================================
    // EDIT MESSAGE (WhatsApp-Style)
    // ========================================================
    function startEditMessage(id, text) {
        cancelAttachment();
        cancelVoiceRecording();

        editingMessageId = id;
        const textInput = document.getElementById('message-input');
        textInput.value = text;
        textInput.focus();

        const previewText = document.getElementById('editing-preview-text');
        previewText.textContent = text.length > 55 ? text.substring(0, 55) + '...' : text;
        document.getElementById('editing-banner').classList.remove('hidden');

        document.getElementById('btn-send-icon').innerText = 'check';
        document.getElementById('btn-send').title = 'Save Changes';
    }

    function cancelEditing() {
        editingMessageId = null;
        document.getElementById('message-input').value = '';
        document.getElementById('editing-banner').classList.add('hidden');
        document.getElementById('btn-send-icon').innerText = 'send';
        document.getElementById('btn-send').title = 'Send Message';
    }

    // ========================================================
    // DELETE SINGLE MESSAGE (WhatsApp-Style)
    // ========================================================
    function confirmDeleteMessage(id) {
        deletingMessageId = id;
        openModal('modal-delete-message');
    }

    function executeDeleteMessage() {
        if (!deletingMessageId) return;

        const btn = document.getElementById('btn-confirm-delete');
        btn.disabled = true;

        fetch(`{{ url('/chat/messages') }}/${deletingMessageId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            closeModal('modal-delete-message');
            if (res.success && res.message) {
                updateMessageInDom(res.message);
                if (editingMessageId === deletingMessageId) {
                    cancelEditing();
                }
            } else if (res.error) {
                alert(res.error);
            }
            deletingMessageId = null;
        })
        .catch(err => {
            btn.disabled = false;
            deletingMessageId = null;
            closeModal('modal-delete-message');
            console.error('Delete error:', err);
        });
    }

    // ========================================================
    // CLEAR ENTIRE CHAT CONVERSATION
    // ========================================================
    function executeClearChat() {
        const btn = document.getElementById('btn-confirm-clear');
        btn.disabled = true;

        fetch('{{ route('chat.clear') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                active_type: currentActiveType,
                receiver_id: currentReceiverId || null
            })
        })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            closeModal('modal-clear-chat');
            if (res.success) {
                const cacheKey = `${currentActiveType}_${currentReceiverId || 'group'}`;
                conversationCache[cacheKey] = [];
                messagesContainer.innerHTML = `
                    <div id="no-messages-placeholder" class="h-full flex flex-col items-center justify-center text-center p-8 text-slate-400">
                        <span class="material-symbols-outlined text-4xl mb-2 text-slate-400">chat</span>
                        <h4 class="font-bold text-slate-700 text-xs sm:text-sm">Conversation cleared</h4>
                        <p class="text-xs text-slate-400 max-w-sm mt-1">All messages and physical attachments were purged from server disk to reclaim storage.</p>
                    </div>`;
                lastMessageId = 0;
            } else if (res.error) {
                alert(res.error);
            }
        })
        .catch(err => {
            btn.disabled = false;
            closeModal('modal-clear-chat');
            console.error('Clear chat error:', err);
        });
    }

    // ========================================================
    // STORAGE CLEANUP & AUTO-CLEAN
    // ========================================================
    function executeAutoClean() {
        const checkedRadio = document.querySelector('input[name="clean_period"]:checked');
        const period = checkedRadio ? checkedRadio.value : '24_hours';

        const btn = document.getElementById('btn-run-autoclean');
        btn.disabled = true;

        fetch('{{ route('chat.auto_clean') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ period: period })
        })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            closeModal('modal-storage-cleanup');
            if (res.success) {
                alert(res.message);
                window.location.reload();
            } else if (res.error) {
                alert(res.error);
            }
        })
        .catch(err => {
            btn.disabled = false;
            closeModal('modal-storage-cleanup');
            console.error('Auto clean error:', err);
        });
    }

    // ========================================================
    // SEND / SAVE MESSAGE (AJAX Form Handler)
    // ========================================================
    function handleSendMessage(e) {
        e.preventDefault();
        const textInput = document.getElementById('message-input');
        const fileInput = document.getElementById('file-input');
        const textVal = textInput.value.trim();

        // 1. EDIT FLOW
        if (editingMessageId) {
            if (!textVal) return;
            const btnSend = document.getElementById('btn-send');
            btnSend.disabled = true;

            fetch(`{{ url('/chat/messages') }}/${editingMessageId}/edit`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ message_text: textVal })
            })
            .then(r => r.json())
            .then(res => {
                btnSend.disabled = false;
                if (res.success && res.message) {
                    updateMessageInDom(res.message);
                    cancelEditing();
                } else if (res.error) {
                    alert(res.error);
                }
            })
            .catch(err => {
                btnSend.disabled = false;
                console.error('Edit error:', err);
            });
            return;
        }

        // 2. NORMAL NEW MESSAGE FLOW
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
                textInput.value = '';
                cancelAttachment();

                appendMessageToDom(res.message);
                if (res.message.id > lastMessageId) lastMessageId = res.message.id;
                scrollToBottom();

                const placeholder = document.getElementById('no-messages-placeholder');
                if (placeholder) placeholder.remove();

                // Append to cache
                const cacheKey = `${currentActiveType}_${currentReceiverId || 'group'}`;
                if (conversationCache[cacheKey]) {
                    conversationCache[cacheKey].push(res.message);
                }
            } else if (res.error) {
                alert(res.error);
            }
        })
        .catch(err => {
            btnSend.disabled = false;
            console.error('Send error:', err);
        });
    }

    // ========================================================
    // DOM BUILDER & UPDATER
    // ========================================================
    function buildMessageRowInnerHtml(m) {
        const isMe = m.is_me;
        const isChecked = selectedMessageIds.has(m.id);
        const canModify = m.can_modify;

        let html = `
            <!-- Multi-Select Checkbox -->
            <div class="msg-select-wrap ${isSelectMode ? 'flex' : 'hidden'} items-center px-1 shrink-0">
                <input type="checkbox" class="msg-checkbox w-4 h-4 rounded border-slate-400 text-slate-900 focus:ring-slate-900 cursor-pointer"
                       data-id="${m.id}" ${isChecked ? 'checked' : ''} onchange="handleMessageCheckboxChange(event, ${m.id})">
            </div>

            <div class="flex flex-col ${isMe ? 'items-end' : 'items-start'} message-node relative max-w-[85%] sm:max-w-md md:max-w-lg" data-id="${m.id}">
        `;

        // Sender name for group chat
        if (!isMe && currentActiveType === 'group' && !m.is_deleted) {
            html += `<span class="text-[10px] font-bold text-slate-600 mb-1 ml-1 flex items-center gap-1.5">
                        <span>${escapeHtml(m.sender_name)}</span>
                        <span class="text-[9px] font-normal text-slate-400">(${escapeHtml(m.sender_role)})</span>
                     </span>`;
        }

        html += `<div class="relative w-full">`;

        // Action menu (Edit / Delete)
        if (canModify && !m.is_deleted) {
            html += `<div class="absolute -top-3 ${isMe ? 'right-2' : 'left-2'} hidden group-hover:flex items-center gap-0.5 bg-white shadow-xs border border-slate-300 rounded-lg p-0.5 z-10">`;
            if (m.message_text) {
                const escapedTextJson = JSON.stringify(m.message_text);
                html += `<button type="button" onclick='startEditMessage(${m.id}, ${escapedTextJson})'
                                 class="p-1 rounded text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors"
                                 title="Edit Message">
                            <span class="material-symbols-outlined text-[15px]">edit</span>
                         </button>`;
            }
            html += `<button type="button" onclick="confirmDeleteMessage(${m.id})"
                             class="p-1 rounded text-slate-600 hover:text-rose-700 hover:bg-slate-100 transition-colors"
                             title="Delete Message">
                        <span class="material-symbols-outlined text-[15px]">delete</span>
                     </button>`;
            html += `</div>`;
        }

        // WhatsApp-Style Deleted placeholder vs Active Message
        if (m.is_deleted) {
            html += `<div class="rounded-xl px-3.5 py-2.5 shadow-2xs italic text-xs flex items-center gap-2 bg-slate-100 text-slate-600 border border-slate-300 ${isMe ? 'rounded-tr-xs' : 'rounded-tl-xs'}">
                        <span class="material-symbols-outlined text-[15px] text-slate-500">block</span>
                        <span>${m.deleted_by_me ? 'You deleted this message' : 'This message was deleted'}</span>
                        <span class="text-[10px] text-slate-400 not-italic ml-auto">${m.formatted_time || ''}</span>
                     </div>`;
        } else {
            html += `<div class="rounded-xl p-3.5 shadow-2xs ${isMe ? 'bg-navy-900 text-white border border-navy-800 rounded-tr-xs' : 'bg-white text-slate-900 border border-slate-300 rounded-tl-xs'}">`;

            // 1. Image
            if (m.attachment_path && m.attachment_type === 'image') {
                html += `<div class="mb-2 overflow-hidden rounded-lg bg-black/5 border border-slate-300/50">
                            <a href="${m.attachment_path}" target="_blank" rel="noopener">
                                <img src="${m.attachment_path}" class="rounded-lg max-h-72 w-auto object-cover hover:opacity-95 transition-opacity" />
                            </a>
                         </div>`;
            }

            // 2. Audio
            if (m.attachment_path && m.attachment_type === 'audio') {
                html += `<div class="mb-2 p-2 rounded-lg ${isMe ? 'bg-navy-800 text-white border border-navy-700' : 'bg-slate-100 text-slate-800 border border-slate-300'}">
                            <div class="flex items-center gap-2 mb-1.5 text-xs font-semibold">
                                <span class="material-symbols-outlined text-[17px] text-slate-300">graphic_eq</span>
                                <span>Voice Note</span>
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
                html += `<div class="mb-2 p-2.5 rounded-lg flex items-center justify-between gap-3 ${isMe ? 'bg-navy-800 text-white border border-navy-700' : 'bg-slate-100 text-slate-800 border border-slate-300'}">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="material-symbols-outlined text-[22px] ${isPdf ? 'text-red-400' : 'text-slate-300'}">
                                    ${isPdf ? 'picture_as_pdf' : 'description'}
                                </span>
                                <div class="min-w-0">
                                    <div class="text-xs font-bold truncate">${escapeHtml(m.attachment_name || 'Document')}</div>
                                    <div class="text-[10px] opacity-70">${m.formatted_size || ''}</div>
                                </div>
                            </div>
                            <a href="${m.attachment_path}" download="${escapeHtml(m.attachment_name || 'file')}"
                               class="px-2.5 py-1 rounded bg-slate-900 hover:bg-black text-white border border-slate-700 text-[11px] font-semibold shrink-0 flex items-center gap-1 transition-colors">
                                <span class="material-symbols-outlined text-[13px]">download</span>
                                <span>Download</span>
                            </a>
                         </div>`;
            }

            // Text content
            if (m.message_text) {
                html += `<p class="text-xs leading-relaxed whitespace-pre-wrap select-text break-words message-body">${escapeHtml(m.message_text)}</p>`;
            }

            // Time, Edited status & Tick
            html += `<div class="flex items-center justify-end gap-1 mt-1 text-[10px] ${isMe ? 'text-slate-400' : 'text-slate-400'}">
                        ${m.is_edited ? `<span class="text-[9px] text-slate-400 italic font-normal mr-0.5">(edited)</span>` : ''}
                        <span>${m.formatted_time || ''}</span>
                        ${isMe ? `<span class="material-symbols-outlined text-[13px] ${m.is_read ? 'text-blue-400' : 'text-slate-400'}">done_all</span>` : ''}
                     </div>`;

            html += `</div>`;
        }

        html += `</div></div>`;
        return html;
    }

    function appendMessageToDom(m) {
        const isMe = m.is_me;
        const div = document.createElement('div');
        div.className = `flex ${isMe ? 'flex-row-reverse' : 'flex-row'} items-center gap-2 w-full message-row group relative`;
        div.setAttribute('data-id', m.id);
        div.innerHTML = buildMessageRowInnerHtml(m);
        messagesContainer.appendChild(div);
    }

    function updateMessageInDom(m) {
        const row = document.querySelector(`.message-row[data-id="${m.id}"]`);
        if (!row) return;
        const isMe = m.is_me;
        row.className = `flex ${isMe ? 'flex-row-reverse' : 'flex-row'} items-center gap-2 w-full message-row group relative`;
        row.innerHTML = buildMessageRowInnerHtml(m);
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ========================================================
    // REAL-TIME POLLING ENGINE (Live Sync Every 3s)
    // ========================================================
    function pollNewMessages() {
        if (isPolling) return;
        isPolling = true;

        const url = `{{ route('chat.messages') }}?type=${currentActiveType}&receiver_id=${currentReceiverId}&after_id=${lastMessageId}&last_sync=${encodeURIComponent(lastSyncTime)}`;

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                isPolling = false;

                if (data.sync_time) {
                    lastSyncTime = data.sync_time;
                }

                if (data.updated_messages && data.updated_messages.length > 0) {
                    data.updated_messages.forEach(m => {
                        updateMessageInDom(m);
                    });
                }

                if (data.messages && data.messages.length > 0) {
                    const placeholder = document.getElementById('no-messages-placeholder');
                    if (placeholder) placeholder.remove();

                    data.messages.forEach(m => {
                        if (!document.querySelector(`.message-row[data-id="${m.id}"]`)) {
                            appendMessageToDom(m);
                            if (m.id > lastMessageId) lastMessageId = m.id;
                        }
                    });
                    scrollToBottom();

                    // Update memory cache
                    const cacheKey = `${currentActiveType}_${currentReceiverId || 'group'}`;
                    if (conversationCache[cacheKey]) {
                        data.messages.forEach(m => {
                            if (!conversationCache[cacheKey].find(item => item.id === m.id)) {
                                conversationCache[cacheKey].push(m);
                            }
                        });
                    }
                }
            })
            .catch(() => {
                isPolling = false;
            });
    }

    setInterval(pollNewMessages, 3000);
</script>
@endpush
