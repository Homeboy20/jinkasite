/**
 * Live Chat Widget JavaScript
 */

class LiveChatWidget {
    constructor() {
        this.sessionId = localStorage.getItem('chat_session_id');
        this.lastMessageId = 0;
        this.isOpen = false;
        this.pollingInterval = null;
        this.typingTimeout = null;
        this.sessionStatus = null;
        this.sessionActive = false;
        this.chatLocked = false;
        this.endNoticeShown = false;
        /**
         * Live Chat Widget JavaScript
         * Front-end chat client for SupportSystem / api/support-chat.php
         */

        class LiveChatWidget {
            constructor(options = {}) {
                this.options = Object.assign({
                    apiEndpoint: 'api/support-chat.php',
                    customerName: 'Guest',
                    customerEmail: '',
                    customerId: null,
                    storageKey: 'chat_session_id'
                }, options);

                this.sessionId = localStorage.getItem(this.options.storageKey);
                this.lastMessageId = 0;
                this.isOpen = false;
                this.pollingInterval = null;
                this.sessionStatus = null;
                this.sessionActive = false;
                this.chatLocked = false;
                this.endNoticeShown = false;
                this.elements = {};

                this.init();
            }

            init() {
                this.createWidget();
                this.cacheElements();
                this.bindEvents();

                if (this.sessionId) {
                    this.resumeSession();
                } else {
                    this.showPrechat();
                }
            }

            cacheElements() {
                this.elements.window = document.getElementById('chatWindow');
                this.elements.messages = document.getElementById('chatMessages');
                this.elements.input = document.getElementById('chatInput');
                this.elements.sendBtn = document.getElementById('chatSendBtn');
                this.elements.widgetBtn = document.getElementById('chatWidgetBtn');
                this.elements.closeBtn = document.getElementById('chatCloseBtn');
                this.elements.badge = document.getElementById('chatBadge');
                this.elements.status = document.getElementById('chatStatus');
                this.elements.prechat = document.getElementById('chatPreSession');
                this.elements.prechatName = document.getElementById('chatName');
                this.elements.prechatEmail = document.getElementById('chatEmail');
                this.elements.prechatPhone = document.getElementById('chatPhone');
                this.elements.prechatMessage = document.getElementById('chatFirstMessage');
                this.elements.startBtn = document.getElementById('chatStartBtn');
                this.elements.endBtn = document.getElementById('chatEndBtn');
                this.elements.ticketLink = document.getElementById('chatTicketLink');
            }

            bindEvents() {
                this.elements.widgetBtn.addEventListener('click', () => this.toggleChat());
                this.elements.closeBtn.addEventListener('click', () => this.closeChat());
                this.elements.sendBtn.addEventListener('click', () => this.sendMessage());
                this.elements.input.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        this.sendMessage();
                    }
                });
                this.elements.input.addEventListener('input', (e) => this.autoResize(e.target));
                this.elements.startBtn.addEventListener('click', () => this.startChat());
                this.elements.endBtn.addEventListener('click', () => this.endChat());
                this.elements.ticketLink.addEventListener('click', () => {
                    this.closeChat();
                });
            }

            createWidget() {
                const widget = document.createElement('div');
                widget.innerHTML = `
                    <div class="chat-widget-button" id="chatWidgetBtn">
                        <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/></svg>
                        <span class="badge" id="chatBadge" style="display: none;">0</span>
                    </div>

                    <div class="chat-window" id="chatWindow">
                        <div class="chat-header">
                            <div class="chat-header-info">
                                <div class="chat-agent-avatar">PS</div>
                                <div class="chat-agent-details">
                                    <h4>ProCut Support</h4>
                                    <p id="chatStatus">Online</p>
                                </div>
                            </div>
                            <div class="chat-actions">
                                <button class="chat-end-btn" id="chatEndBtn" title="End chat">End</button>
                                <button class="chat-close-btn" id="chatCloseBtn" aria-label="Close chat">
                                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                                </button>
                            </div>
                        </div>

                        <div class="chat-messages" id="chatMessages">
                            <div class="chat-welcome">
                                <h3>👋 Welcome!</h3>
                                <p>Average reply time: under 5 minutes.</p>
                            </div>
                            <div class="chat-presession" id="chatPreSession">
                                <div class="chat-precopy">Tell us a few details to start chatting.</div>
                                <div class="chat-form-group">
                                    <label for="chatName">Name</label>
                                    <input type="text" id="chatName" placeholder="Your name" value="${this.escapeHtml(this.options.customerName)}">
                                </div>
                                <div class="chat-form-group">
                                    <label for="chatEmail">Email</label>
                                    <input type="email" id="chatEmail" placeholder="you@example.com" value="${this.escapeHtml(this.options.customerEmail)}">
                                </div>
                                <div class="chat-form-group">
                                    <label for="chatPhone">Phone (optional)</label>
                                    <input type="tel" id="chatPhone" placeholder="WhatsApp or phone">
                                </div>
                                <div class="chat-form-group">
                                    <label for="chatFirstMessage">What do you need help with?</label>
                                    <textarea id="chatFirstMessage" rows="3" placeholder="Describe your question"></textarea>
                                </div>
                                <button class="chat-start-btn" id="chatStartBtn">Start Chat</button>
                                <a href="customer-support.php" class="chat-ticket-link" id="chatTicketLink">Prefer to open a support ticket?</a>
                            </div>
                        </div>

                        <div class="chat-input-area">
                            <div class="chat-input-container">
                                <textarea id="chatInput" class="chat-input" placeholder="Type your message..." rows="1"></textarea>
                                <button id="chatSendBtn" class="chat-send-btn">
                                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M2.01 21 23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                `;

                document.body.appendChild(widget);
            }

            toggleChat() {
                if (this.isOpen) {
                    this.closeChat();
                } else {
                    this.openChat();
                }
            }

            async openChat() {
                this.isOpen = true;
                this.elements.window.classList.add('open');
                this.elements.input.focus();

                if (!this.sessionId || this.sessionStatus === 'ended') {
                    this.chatLocked = false;
                    this.enableChatInput();
                    this.clearSession();
                    this.showPrechat();
                } else {
                    this.hidePrechat();
                    await this.loadMessages();
                    this.startPolling();
                }
            }

            closeChat() {
                this.isOpen = false;
                this.elements.window.classList.remove('open');
                this.stopPolling();
                if (this.sessionStatus === 'ended') {
                    this.clearSession();
                    this.chatLocked = false;
                    this.enableChatInput();
                    this.showPrechat();
                }
            }

            async startChat() {
                const name = (this.elements.prechatName.value || '').trim() || 'Guest';
                const email = (this.elements.prechatEmail.value || '').trim();
                const phone = (this.elements.prechatPhone.value || '').trim();
                const firstMessage = (this.elements.prechatMessage.value || '').trim();

                if (!firstMessage) {
                    this.flashStatus('Please enter a short message so we can help.', 'warning');
                    this.elements.prechatMessage.focus();
                    return;
                }

                this.disableChatInput('Connecting...');

                const created = await this.createSession({ name, email, phone });
                if (!created) {
                    this.enableChatInput();
                    return;
                }

                this.hidePrechat();
                this.addMessage({ sender_type: 'customer', message: firstMessage, created_at: new Date().toISOString() });
                await this.sendMessageToServer(firstMessage);
                this.enableChatInput();
                this.elements.input.focus();
                this.startPolling();
            }

            async createSession(extra = {}) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'create_chat_session');
                    if (extra.name) formData.append('guest_name', extra.name);
                    if (extra.email) formData.append('guest_email', extra.email);
                    if (extra.phone) formData.append('guest_phone', extra.phone);

                    const response = await fetch(this.options.apiEndpoint, { method: 'POST', body: formData });
                    const data = await response.json();

                    if (data.success) {
                        this.sessionId = data.session_id;
                        localStorage.setItem(this.options.storageKey, this.sessionId);
                        this.sessionStatus = 'waiting';
                        this.sessionActive = true;
                        this.chatLocked = false;
                        this.setStatus('Waiting for agent', 'waiting');
                        this.addMessage({ sender_type: 'system', message: 'Connected! An agent will be with you shortly.', created_at: new Date().toISOString() });
                        return true;
                    }

                    this.setStatus('Unable to start chat', 'error');
                    return false;
                } catch (error) {
                    console.error('Error creating chat session:', error);
                    this.setStatus('Network error starting chat', 'error');
                    return false;
                }
            }

            async resumeSession() {
                await this.checkActiveSession();
                if (!this.sessionId || this.sessionStatus === 'ended') {
                    this.showPrechat();
                    this.enableChatInput();
                    return;
                }
                this.hidePrechat();
                await this.loadMessages();
                this.startPolling();
            }

            async checkActiveSession() {
                try {
                    const response = await fetch(`${this.options.apiEndpoint}?action=get_session&session_id=${encodeURIComponent(this.sessionId)}`);
                    const data = await response.json();

                    if (!data.success || !data.session) {
                        this.clearSession();
                    } else {
                        this.sessionStatus = data.session.status;
                        this.sessionActive = ['active', 'waiting', 'transferred'].includes(this.sessionStatus);
                        if (!this.sessionActive && this.sessionStatus === 'ended') {
                            this.chatLocked = true;
                            this.handleSessionEnded();
                        }
                    }
                } catch (error) {
                    console.error('Error checking session:', error);
                }
            }

            async sendMessage() {
                const message = (this.elements.input.value || '').trim();
                if (!message) return;
                if (this.chatLocked || this.sessionStatus === 'ended') {
                    this.handleSessionEnded();
                    return;
                }

                if (!this.sessionId) {
                    await this.startChat();
                    return;
                }

                this.elements.input.value = '';
                this.autoResize(this.elements.input);
                this.addMessage({ sender_type: 'customer', message, created_at: new Date().toISOString() });
                await this.sendMessageToServer(message);
            }

            async sendMessageToServer(message) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'send_message');
                    formData.append('session_id', this.sessionId);
                    formData.append('message', message);

                    const response = await fetch(this.options.apiEndpoint, { method: 'POST', body: formData });
                    const result = await response.json();

                    if (!result.success) {
                        if (result.error === 'Chat session is closed') {
                            this.sessionStatus = 'ended';
                            this.handleSessionEnded();
                        } else {
                            this.flashStatus('Message not sent. Please try again.', 'error');
                        }
                    }
                } catch (error) {
                    console.error('Error sending message:', error);
                    this.flashStatus('Network error. Trying again...', 'warning');
                }
            }

            async loadMessages() {
                try {
                    const response = await fetch(`${this.options.apiEndpoint}?action=get_messages&session_id=${encodeURIComponent(this.sessionId)}&since_id=${this.lastMessageId}`);
                    const data = await response.json();

                    if ('session_status' in data) {
                        this.sessionStatus = data.session_status;
                        this.sessionActive = !!data.session_active;
                        if (!this.sessionActive && this.sessionStatus === 'ended') {
                            this.handleSessionEnded();
                            return;
                        }
                    }

                    if (data.success && Array.isArray(data.messages) && data.messages.length > 0) {
                        data.messages.forEach(msg => this.addMessage(msg));
                        this.lastMessageId = data.messages[data.messages.length - 1].id;
                    }
                } catch (error) {
                    console.error('Error loading messages:', error);
                }
            }

            addMessage(message) {
                // Remove welcome message when the first real message appears
                const welcome = this.elements.messages.querySelector('.chat-welcome');
                if (welcome) welcome.remove();

                const wrap = document.createElement('div');
                wrap.className = `chat-message ${message.sender_type}`;

                const time = new Date(message.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                const safeText = this.escapeHtml(message.message || '');

                wrap.innerHTML = `
                    <div class="chat-message-content">
                        ${safeText}
                        <div class="chat-message-time">${time}</div>
                    </div>
                `;

                this.elements.messages.appendChild(wrap);
                this.elements.messages.scrollTop = this.elements.messages.scrollHeight;

                if (message.sender_type === 'agent' && !this.isOpen) {
                    this.updateBadge(1);
                }
            }

            startPolling() {
                this.stopPolling();
                this.pollingInterval = setInterval(() => {
                    if (this.sessionId) {
                        this.loadMessages();
                    }
                }, 3000);
            }

            stopPolling() {
                if (this.pollingInterval) {
                    clearInterval(this.pollingInterval);
                    this.pollingInterval = null;
                }
            }

            async endChat() {
                if (!this.sessionId || this.sessionStatus === 'ended') {
                    this.handleSessionEnded();
                    return;
                }

                try {
                    const formData = new FormData();
                    formData.append('action', 'end_chat');
                    formData.append('session_id', this.sessionId);

                    await fetch(this.options.apiEndpoint, { method: 'POST', body: formData });
                } catch (error) {
                    console.error('Error ending chat:', error);
                }

                this.sessionStatus = 'ended';
                this.handleSessionEnded();
            }

            handleSessionEnded() {
                if (this.endNoticeShown) {
                    this.disableChatInput('This chat has ended. Start a new one anytime.');
                    this.insertTicketCTA();
                    return;
                }

                this.endNoticeShown = true;
                this.chatLocked = true;
                this.stopPolling();
                this.disableChatInput('This chat has ended. Start a new one anytime.');
                this.setStatus('Chat ended', 'ended');
                this.addMessage({ sender_type: 'system', message: 'This conversation is closed. Open a new chat or create a support ticket if you need more help.', created_at: new Date().toISOString() });
                this.insertTicketCTA();
            }

            insertTicketCTA() {
                if (this.elements.messages.querySelector('.chat-ticket-cta')) return;
                const cta = document.createElement('div');
                cta.className = 'chat-ticket-cta';
                cta.innerHTML = `
                    <p>Need more help? Open a support ticket and we will follow up fast.</p>
                    <a href="customer-support.php" class="chat-ticket-btn">Open ticket</a>
                `;
                this.elements.messages.appendChild(cta);
                this.elements.messages.scrollTop = this.elements.messages.scrollHeight;
            }

            setStatus(text, state = 'online') {
                if (!this.elements.status) return;
                this.elements.status.textContent = text;
                this.elements.status.dataset.state = state;
            }

            flashStatus(text, state = 'info') {
                this.setStatus(text, state);
                setTimeout(() => {
                    this.setStatus('Online', 'online');
                }, 4000);
            }

            showPrechat() {
                if (this.elements.prechat) {
                    this.elements.prechat.style.display = 'block';
                }
                this.disableChatInput('Start chat to type...');
            }

            hidePrechat() {
                if (this.elements.prechat) {
                    this.elements.prechat.style.display = 'none';
                }
                this.enableChatInput();
            }

            clearSession() {
                localStorage.removeItem(this.options.storageKey);
                this.sessionId = null;
                this.sessionStatus = null;
                this.sessionActive = false;
                this.endNoticeShown = false;
                this.chatLocked = false;
                this.lastMessageId = 0;
            }

            updateBadge(increment) {
                const current = parseInt(this.elements.badge.textContent, 10) || 0;
                const next = current + increment;
                if (next > 0) {
                    this.elements.badge.textContent = next;
                    this.elements.badge.style.display = 'flex';
                } else {
                    this.elements.badge.style.display = 'none';
                }
            }

            enableChatInput() {
                this.elements.input.disabled = false;
                this.elements.input.placeholder = 'Type your message...';
                this.elements.sendBtn.disabled = false;
                this.elements.sendBtn.style.opacity = '';
                this.elements.sendBtn.style.cursor = 'pointer';
            }

            disableChatInput(message) {
                this.elements.input.value = '';
                this.elements.input.disabled = true;
                this.elements.input.placeholder = message || '';
                this.elements.sendBtn.disabled = true;
                this.elements.sendBtn.style.opacity = '0.6';
                this.elements.sendBtn.style.cursor = 'not-allowed';
            }

            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text || '';
                return div.innerHTML;
            }

            autoResize(textarea) {
                textarea.style.height = 'auto';
                textarea.style.height = `${textarea.scrollHeight}px`;
            }
        }
