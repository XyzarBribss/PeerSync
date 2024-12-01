function fetchActiveEmployees() {
    fetch('get_active_employees.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error:', data.error);
                return;
            }
            const employeeWidget = document.querySelector('[data-widget="employees"]');
            if (employeeWidget) {
                employeeWidget.textContent = data.count;
            }
        })
        .catch(error => {
            console.error('Error fetching employees:', error);
        });
}

// Fetch employee count every 5 seconds
setInterval(fetchActiveEmployees, 5000);

// Initial fetch on page load
fetchActiveEmployees();
