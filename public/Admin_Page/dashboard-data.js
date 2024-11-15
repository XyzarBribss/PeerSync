async function getDashboardMetrics() {
    try {
        // Fetch dashboard metrics (revenue, sales)
        const metricsResponse = await fetch('/api/dashboard_metrics');
        const metricsData = await metricsResponse.json();

        // Fetch users data
        const usersResponse = await fetch('/api/users');
        const usersData = await usersResponse.json();

        // Extract the values
        const revenue = metricsData.revenue || 0;
        const sales = metricsData.sales || 0;
        const numberOfUsers = usersData.length;
        const numberOfEmployees = usersData.filter(user => user.role === 'employee').length; // Assuming 'role' represents employee status

        // Displaying results (or use them as needed)
        console.log(`Revenue: $${revenue}`);
        console.log(`Sales: ${sales}`);
        console.log(`Number of Users: ${numberOfUsers}`);
        console.log(`Number of Employees: ${numberOfEmployees}`);
    } catch (error) {
        console.error('Error fetching dashboard data:', error);
    }
}

// Call the function to get the metrics
getDashboardMetrics();
