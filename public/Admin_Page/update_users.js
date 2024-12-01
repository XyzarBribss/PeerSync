function fetchActiveUsers() {
    fetch('get_active_users.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error:', data.error);
                return;
            }
            const userWidget = document.querySelector('[data-widget="users"]');
            if (userWidget) {
                userWidget.textContent = data.count;
            }
        })
        .catch(error => {
            console.error('Error fetching users:', error);
        });
}

// Fetch users count every 5 seconds
setInterval(fetchActiveUsers, 5000);

// Initial fetch on page load
fetchActiveUsers();
