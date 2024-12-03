// Wait for DOM to be fully loaded before initializing
document.addEventListener('DOMContentLoaded', function() {
    const NotificationSystem = {
        offset: 0,
        isLoading: false,
        hasMore: true,

        formatTimeAgo(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const seconds = Math.floor((now - date) / 1000);
            
            if (seconds < 60) return 'just now';
            const minutes = Math.floor(seconds / 60);
            if (minutes < 60) return `${minutes}m ago`;
            const hours = Math.floor(minutes / 60);
            if (hours < 24) return `${hours}h ago`;
            const days = Math.floor(hours / 24);
            if (days < 7) return `${days}d ago`;
            
            return date.toLocaleDateString();
        },

        createNotificationHTML(notification) {
            const { type, from_user, post, created_at, is_read } = notification;
            
            let message = '';
            let icon = '';
            let postLink = post ? `post_details.php?id=${post.id}` : '#';
            
            switch(type) {
                case 'like':
                    message = 'liked your post';
                    icon = 'fa-heart text-red-500';
                    break;
                case 'comment':
                    message = 'commented on your post';
                    icon = 'fa-comment text-blue-500';
                    break;
                case 'follow':
                    message = 'started following you';
                    icon = 'fa-user-plus text-green-500';
                    break;
                case 'mention':
                    message = 'mentioned you';
                    icon = 'fa-at text-purple-500';
                    break;
                default:
                    message = 'interacted with your post';
                    icon = 'fa-bell text-gray-500';
            }

            const bgColor = is_read ? 'bg-white' : 'bg-blue-50';
            
            return `
                <a href="${postLink}" class="block">
                    <div class="notification-item ${bgColor} p-4 hover:bg-gray-50 border-b transition-colors duration-200" data-notification-id="${notification.id}">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                                    <i class="fas ${icon}"></i>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm">
                                    <span class="font-semibold">${from_user.username}</span>
                                    <span class="text-gray-600">${message}</span>
                                </div>
                                ${post ? 
                                    `<div class="text-gray-600 text-sm mt-1 truncate">${post.title || post.message}</div>` : 
                                    ''
                                }
                                <div class="text-gray-400 text-xs mt-1">${this.formatTimeAgo(created_at)}</div>
                            </div>
                            ${post?.image ? 
                                `<div class="flex-shrink-0 ml-2">
                                    <img src="${post.image}" alt="Post image" class="w-12 h-12 object-cover rounded">
                                </div>` : 
                                ''
                            }
                        </div>
                    </div>
                </a>
            `;
        },

        async loadNotifications(isInitialLoad = false) {
            if (this.isLoading || !this.hasMore) return;
            
            this.isLoading = true;
            const loader = document.getElementById('notificationsLoader');
            if (loader) loader.classList.remove('hidden');

            try {
                const response = await fetch(`/PeerSync/public/api/get_notifications.php?offset=${this.offset}`);
                const data = await response.json();
                
                function handleNotificationResponse(response) {
                    console.log('Notification API Response:', response);
                    if (response.debug_info) {
                        console.log('Debug Info:', response.debug_info);
                    }
                    if (!response.success) {
                        console.error('Notification Error:', response.message);
                    }
                    return response.success;
                }

                if (handleNotificationResponse(data)) {
                    // Update notification badge regardless of modal state
                    const badge = document.querySelector('.notification-badge');
                    if (badge) {
                        const unreadCount = data.unread_count;
                        if (unreadCount > 0) {
                            badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                            badge.classList.remove('hidden');
                        } else {
                            badge.classList.add('hidden');
                        }
                    }

                    // Only update the list if we're loading the modal
                    if (!isInitialLoad) {
                        const notificationsList = document.querySelector('.notifications-list');
                        if (!notificationsList) {
                            console.error('Notifications list element not found. DOM structure:', document.body.innerHTML);
                            return;
                        }
                        
                        if (this.offset === 0) {
                            notificationsList.innerHTML = '';
                        }
                        
                        if (data.notifications.length === 0) {
                            if (this.offset === 0) {
                                notificationsList.innerHTML = `
                                    <div class="text-center py-8 text-gray-500">
                                        <i class="fas fa-bell text-4xl mb-2"></i>
                                        <p>No notifications yet</p>
                                    </div>
                                `;
                            }
                        } else {
                            data.notifications.forEach(notification => {
                                notificationsList.insertAdjacentHTML('beforeend', this.createNotificationHTML(notification));
                            });
                        }
                        
                        this.offset += data.notifications.length;
                        this.hasMore = data.has_more;
                        
                        // Setup infinite scroll if there are more notifications
                        if (this.hasMore) {
                            this.setupInfiniteScroll();
                        }
                    }
                } else {
                    throw new Error(data.error || 'Failed to load notifications');
                }
            } catch (error) {
                console.error('Error loading notifications:', error);
                if (!isInitialLoad) {
                    const notificationsList = document.querySelector('.notifications-list');
                    if (notificationsList && this.offset === 0) {
                        notificationsList.innerHTML = `
                            <div class="text-center py-8 text-red-500">
                                <i class="fas fa-exclamation-circle text-4xl mb-2"></i>
                                <p>Error loading notifications</p>
                            </div>
                        `;
                    }
                }
            } finally {
                this.isLoading = false;
                if (loader) loader.classList.add('hidden');
            }
        },

        markAllAsRead() {
            fetch('/PeerSync/public/api/mark_notifications_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(handleNotificationResponse)
            .then(success => {
                if (success) {
                    // Update UI to show all notifications as read
                    document.querySelectorAll('.notification-item').forEach(item => {
                        item.classList.remove('bg-blue-50');
                        item.classList.add('bg-white');
                    });
                    
                    // Hide notification badge
                    const badge = document.querySelector('.notification-badge');
                    if (badge) {
                        badge.classList.add('hidden');
                    }
                }
            })
            .catch(error => console.error('Error marking notifications as read:', error));
        },

        setupInfiniteScroll() {
            const container = document.getElementById('notificationsContainer');
            if (!container) return;

            container.onscroll = () => {
                if (container.scrollHeight - container.scrollTop <= container.clientHeight + 100) {
                    this.loadNotifications();
                }
            };
        },

        init() {
            // Load initial notification count without updating the list
            this.loadNotifications(true);

            const notificationsButton = document.getElementById('notificationsButton');
            const notificationsModal = document.getElementById('notificationsModal');
            const closeNotificationsModal = document.getElementById('closeNotificationsModal');
            const markAllReadBtn = document.getElementById('markAllReadBtn');

            if (notificationsButton && notificationsModal) {
                // Open modal and load notifications
                notificationsButton.addEventListener('click', () => {
                    notificationsModal.classList.remove('hidden');
                    this.offset = 0;
                    this.hasMore = true;
                    this.loadNotifications();
                });

                // Close modal
                if (closeNotificationsModal) {
                    closeNotificationsModal.addEventListener('click', () => {
                        notificationsModal.classList.add('hidden');
                    });
                }

                // Close modal when clicking outside
                window.addEventListener('click', (event) => {
                    if (event.target === notificationsModal) {
                        notificationsModal.classList.add('hidden');
                    }
                });

                // Mark all as read button
                if (markAllReadBtn) {
                    markAllReadBtn.addEventListener('click', () => this.markAllAsRead());
                }
            }
        }
    };

    // Initialize the notification system
    NotificationSystem.init();
});
