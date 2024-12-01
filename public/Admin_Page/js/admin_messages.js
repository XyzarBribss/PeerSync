let currentAdminId = null;
let selectedAdminId = null;
let lastMessageId = 0;
let isTyping = false;
let typingTimer = null;
let messageCheckInterval = null;

// Initialize the admin messaging system
async function initializeAdminMessaging() {
    try {
        // Get current admin's ID
        const response = await fetch('../../api/get_current_admin.php');
        const data = await response.json();
        currentAdminId = data.Emp_id;
        
        // Load admin contacts
        await loadAdminContacts();
        
        // Start periodic checks for new messages
        startMessageChecking();
    } catch (error) {
        console.error('Error initializing admin messaging:', error);
    }
}

// Load admin contacts
async function loadAdminContacts() {
    try {
        const response = await fetch('../../api/get_admin_contacts.php');
        const admins = await response.json();
        
        const contactsList = document.getElementById('contactsList');
        contactsList.innerHTML = ''; // Clear existing contacts
        
        admins.forEach(admin => {
            if (admin.Emp_id !== currentAdminId) {
                const initials = getInitials(admin.name);
                const contactHtml = `
                    <div class="contact-item" data-admin-id="${admin.Emp_id}">
                        <div class="contact-avatar">${initials}</div>
                        <div class="contact-info">
                            <div class="contact-name">${admin.name}</div>
                            <div class="contact-status">
                                <span class="status-indicator ${admin.online ? 'status-online' : 'status-offline'}"></span>
                                <span>${admin.online ? 'Online' : 'Offline'}</span>
                            </div>
                            ${admin.unread_count > 0 ? `<span class="unread-badge">${admin.unread_count}</span>` : ''}
                        </div>
                    </div>
                `;
                contactsList.insertAdjacentHTML('beforeend', contactHtml);
            }
        });

        // Add click event listeners to contacts
        document.querySelectorAll('.contact-item').forEach(contact => {
            contact.addEventListener('click', () => selectContact(contact));
        });
    } catch (error) {
        console.error('Error loading admin contacts:', error);
    }
}

// Get initials from name
function getInitials(name) {
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
}

// Select a contact to chat with
async function selectContact(contactElement) {
    document.querySelectorAll('.contact-item').forEach(item => item.classList.remove('active'));
    contactElement.classList.add('active');
    
    selectedAdminId = contactElement.dataset.adminId;
    const adminName = contactElement.querySelector('.contact-name').textContent;
    const initials = getInitials(adminName);
    
    // Update header
    document.getElementById('currentContactName').textContent = adminName;
    document.getElementById('currentContactAvatar').textContent = initials;
    
    // Clear existing messages
    document.getElementById('chatMessages').innerHTML = '';
    
    // Load chat history
    await loadChatHistory();
    
    // Enable message input
    document.getElementById('messageInput').disabled = false;
    document.getElementById('sendButton').disabled = false;
    
    // Mark messages as read
    await markMessagesAsRead();
}

// Load chat history
async function loadChatHistory() {
    try {
        const response = await fetch(`../../api/get_chat_history.php?contact_id=${selectedAdminId}`);
        const messages = await response.json();
        
        const messagesList = document.getElementById('chatMessages');
        messagesList.innerHTML = ''; // Clear existing messages
        
        messages.forEach(message => {
            appendMessage(message);
        });
        
        scrollToBottom();
        
        // Update last message ID
        if (messages.length > 0) {
            lastMessageId = Math.max(...messages.map(m => m.id));
        }
    } catch (error) {
        console.error('Error loading chat history:', error);
    }
}

// Send a message
async function sendMessage() {
    const messageInput = document.getElementById('messageInput');
    const message = messageInput.value.trim();
    
    if (!message || !selectedAdminId) return;
    
    try {
        const response = await fetch('../../api/send_admin_message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                recipient_id: selectedAdminId,
                message: message
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            messageInput.value = '';
            appendMessage(data.message);
            scrollToBottom();
            lastMessageId = data.message.id;
        }
    } catch (error) {
        console.error('Error sending message:', error);
    }
}

// Append a message to the chat
function appendMessage(message) {
    const messagesList = document.getElementById('chatMessages');
    const isCurrentUser = message.sender_id === currentAdminId;
    
    const messageHtml = `
        <div class="message ${isCurrentUser ? 'sent' : 'received'}">
            <div class="message-content">
                ${message.message}
                <div class="message-time">${formatTimestamp(message.created_at)}</div>
            </div>
        </div>
    `;
    
    messagesList.insertAdjacentHTML('beforeend', messageHtml);
}

// Format timestamp
function formatTimestamp(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

// Check for new messages
async function checkNewMessages() {
    if (!lastMessageId) return;
    
    try {
        const response = await fetch(`../../api/get_new_messages.php?last_id=${lastMessageId}`);
        const messages = await response.json();
        
        messages.forEach(message => {
            if (message.id > lastMessageId) {
                appendMessage(message);
                lastMessageId = message.id;
            }
        });
        
        if (messages.length > 0) {
            await loadAdminContacts(); // Refresh contact list for unread counts
            scrollToBottom();
        }
    } catch (error) {
        console.error('Error checking for new messages:', error);
    }
}

// Start checking for new messages
function startMessageChecking() {
    messageCheckInterval = setInterval(checkNewMessages, 3000); // Check every 3 seconds
}

// Handle typing indicator
function handleTyping() {
    if (!isTyping) {
        isTyping = true;
        sendTypingStatus(true);
    }
    
    clearTimeout(typingTimer);
    typingTimer = setTimeout(() => {
        isTyping = false;
        sendTypingStatus(false);
    }, 1000);
}

// Send typing status
async function sendTypingStatus(isTyping) {
    if (!selectedAdminId) return;
    
    try {
        await fetch('../../api/update_typing_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                recipient_id: selectedAdminId,
                is_typing: isTyping
            })
        });
    } catch (error) {
        console.error('Error updating typing status:', error);
    }
}

// Mark messages as read
async function markMessagesAsRead() {
    if (!selectedAdminId) return;
    
    try {
        const response = await fetch('../../api/mark_messages_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `sender_id=${selectedAdminId}`
        });
        
        const data = await response.json();
        if (data.success) {
            const unreadBadge = document.querySelector(`.contact-item[data-admin-id="${selectedAdminId}"] .unread-badge`);
            if (unreadBadge) unreadBadge.remove();
        }
    } catch (error) {
        console.error('Error marking messages as read:', error);
    }
}

// Scroll to bottom of messages
function scrollToBottom() {
    const messagesList = document.getElementById('chatMessages');
    messagesList.scrollTop = messagesList.scrollHeight;
}

// Event Listeners
document.addEventListener('DOMContentLoaded', () => {
    initializeAdminMessaging();
    
    const messageInput = document.getElementById('messageInput');
    const sendButton = document.getElementById('sendButton');
    const messageForm = document.getElementById('messageForm');
    
    messageInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
    
    messageInput.addEventListener('input', handleTyping);
    
    sendButton.addEventListener('click', sendMessage);
    
    // Auto-resize textarea
    messageInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
});
