// Smart Parking Admin Panel
class AdminPanel {
    constructor() {
        this.apiBase = '../api';
        this.currentUser = null;
        this.adminMap = null;
        this.charts = {};
        this.init();
    }

    init() {
        this.checkAdminAuth();
        this.setupEventListeners();
        this.initializeCharts();
        this.loadDashboardData();
    }

    checkAdminAuth() {
        const token = localStorage.getItem('token');
        const user = localStorage.getItem('user');

        if (!token || !user) {
            window.location.href = '../frontend/index.html';
            return;
        }

        try {
            const userData = JSON.parse(user);
            if (userData.role !== 'admin') {
                alert('Access denied. Admin privileges required.');
                window.location.href = '../frontend/index.html';
                return;
            }

            this.currentUser = userData;
            document.getElementById('admin-name').textContent = userData.full_name;
        } catch (error) {
            console.error('Auth error:', error);
            window.location.href = '../frontend/index.html';
        }
    }

    setupEventListeners() {
        // Navigation
        document.querySelectorAll('.nav-link, .menu-item').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const section = e.target.getAttribute('href')?.substring(1) || 
                               e.target.closest('a').getAttribute('href')?.substring(1);
                if (section) {
                    this.showSection(section);
                }
            });
        });

        // Form submissions
        document.getElementById('add-zone-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleAddZone(e);
        });

        document.getElementById('edit-zone-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleEditZone(e);
        });

        // Filter changes
        document.getElementById('booking-filter')?.addEventListener('change', (e) => {
            this.loadBookings(e.target.value);
        });

        document.getElementById('analytics-period')?.addEventListener('change', (e) => {
            this.loadAnalytics(e.target.value);
        });

        // Search
        document.getElementById('zone-search')?.addEventListener('input', (e) => {
            this.filterZones(e.target.value);
        });
    }

    showSection(sectionId) {
        // Hide all sections
        document.querySelectorAll('.admin-section').forEach(section => {
            section.classList.remove('active');
        });

        // Show target section
        const targetSection = document.getElementById(sectionId);
        if (targetSection) {
            targetSection.classList.add('active');

            // Update navigation
            document.querySelectorAll('.nav-link, .menu-item').forEach(link => {
                link.classList.remove('active');
            });
            
            document.querySelectorAll(`[href="#${sectionId}"]`).forEach(link => {
                link.classList.add('active');
            });

            // Load section-specific data
            this.loadSectionData(sectionId);
        }
    }

    loadSectionData(sectionId) {
        switch (sectionId) {
            case 'dashboard':
                this.loadDashboardData();
                break;
            case 'zones':
                this.loadZones();
                this.initializeAdminMap();
                break;
            case 'bookings':
                this.loadBookings();
                break;
            case 'users':
                this.loadUsers();
                break;
            case 'analytics':
                this.loadAnalytics();
                break;
        }
    }

    async loadDashboardData() {
        try {
            // Load dashboard statistics
            await Promise.all([
                this.loadDashboardStats(),
                this.loadRecentActivity(),
                this.updateDashboardCharts()
            ]);
        } catch (error) {
            console.error('Dashboard loading error:', error);
        }
    }

    async loadDashboardStats() {
        // Simulate API calls with sample data
        const stats = {
            totalSlots: 255,
            availableSlots: 142,
            occupiedSlots: 98,
            dailyRevenue: 1247.50
        };

        document.getElementById('total-slots').textContent = stats.totalSlots;
        document.getElementById('available-slots').textContent = stats.availableSlots;
        document.getElementById('occupied-slots').textContent = stats.occupiedSlots;
        document.getElementById('daily-revenue').textContent = `$${stats.dailyRevenue.toFixed(2)}`;
    }

    async loadRecentActivity() {
        // Sample recent activity data
        const activities = [
            {
                type: 'booking',
                message: 'New booking by John Doe',
                time: '5 minutes ago',
                icon: 'booking'
            },
            {
                type: 'payment',
                message: 'Payment received - $15.00',
                time: '12 minutes ago',
                icon: 'payment'
            },
            {
                type: 'cancel',
                message: 'Booking cancelled by Jane Smith',
                time: '25 minutes ago',
                icon: 'cancel'
            },
            {
                type: 'booking',
                message: 'New booking by Mike Johnson',
                time: '1 hour ago',
                icon: 'booking'
            }
        ];

        const activityList = document.getElementById('recent-bookings');
        activityList.innerHTML = activities.map(activity => `
            <div class="activity-item">
                <div class="activity-icon ${activity.icon}">
                    <i class="fas ${activity.icon === 'booking' ? 'fa-calendar-plus' : 
                                   activity.icon === 'payment' ? 'fa-dollar-sign' : 'fa-times'}"></i>
                </div>
                <div class="activity-content">
                    <p>${activity.message}</p>
                    <div class="time">${activity.time}</div>
                </div>
            </div>
        `).join('');
    }

    initializeCharts() {
        // Initialize Chart.js charts
        const occupancyCtx = document.getElementById('occupancy-chart');
        if (occupancyCtx) {
            this.charts.occupancy = new Chart(occupancyCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Available', 'Occupied', 'Reserved', 'Maintenance'],
                    datasets: [{
                        data: [142, 98, 12, 3],
                        backgroundColor: ['#28a745', '#dc3545', '#ffc107', '#6c757d'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        const revenueCtx = document.getElementById('revenue-chart');
        if (revenueCtx) {
            this.charts.revenue = new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Daily Revenue',
                        data: [1200, 1350, 980, 1150, 1400, 1600, 1247],
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value;
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    async updateDashboardCharts() {
        // Update charts with fresh data
        if (this.charts.occupancy) {
            // Simulate real-time data updates
            const newData = [
                Math.floor(Math.random() * 50) + 120,
                Math.floor(Math.random() * 30) + 80,
                Math.floor(Math.random() * 20) + 5,
                Math.floor(Math.random() * 10) + 1
            ];
            this.charts.occupancy.data.datasets[0].data = newData;
            this.charts.occupancy.update();
        }
    }

    async loadZones() {
        try {
            // Sample zones data
            const zones = [
                {
                    id: 1,
                    name: 'Zone A - Mall Entrance',
                    description: 'Premium parking near mall entrance',
                    total_slots: 50,
                    available_slots: 32,
                    hourly_rate: 5.00,
                    is_active: true,
                    x_coordinate: 40.7128,
                    y_coordinate: -74.0060
                },
                {
                    id: 2,
                    name: 'Zone B - Office Complex',
                    description: 'Business district parking',
                    total_slots: 75,
                    available_slots: 45,
                    hourly_rate: 4.00,
                    is_active: true,
                    x_coordinate: 40.7138,
                    y_coordinate: -74.0070
                },
                {
                    id: 3,
                    name: 'Zone C - Residential Area',
                    description: 'Residential parking zone',
                    total_slots: 30,
                    available_slots: 18,
                    hourly_rate: 3.00,
                    is_active: true,
                    x_coordinate: 40.7118,
                    y_coordinate: -74.0050
                },
                {
                    id: 4,
                    name: 'Zone D - Shopping Center',
                    description: 'Large shopping center parking',
                    total_slots: 100,
                    available_slots: 67,
                    hourly_rate: 4.50,
                    is_active: false,
                    x_coordinate: 40.7148,
                    y_coordinate: -74.0080
                }
            ];

            this.displayZones(zones);
        } catch (error) {
            console.error('Error loading zones:', error);
        }
    }

    displayZones(zones) {
        const zonesGrid = document.getElementById('zones-grid');
        
        zonesGrid.innerHTML = zones.map(zone => `
            <div class="zone-card" data-zone-id="${zone.id}">
                <div class="d-flex justify-content-between align-items-start">
                    <h4>${zone.name}</h4>
                    <span class="zone-status ${zone.is_active ? 'active' : 'inactive'}">
                        ${zone.is_active ? 'Active' : 'Inactive'}
                    </span>
                </div>
                <p>${zone.description}</p>
                <p><strong>Total Slots:</strong> ${zone.total_slots}</p>
                <p><strong>Available:</strong> ${zone.available_slots || 0}</p>
                <p><strong>Rate:</strong> $${zone.hourly_rate}/hour</p>
                <div class="zone-actions">
                    <button class="btn btn-sm btn-primary" onclick="adminPanel.editZone(${zone.id})">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-sm btn-info" onclick="adminPanel.viewZoneDetails(${zone.id})">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button class="btn btn-sm ${zone.is_active ? 'btn-warning' : 'btn-success'}" 
                            onclick="adminPanel.toggleZoneStatus(${zone.id}, ${!zone.is_active})">
                        <i class="fas ${zone.is_active ? 'fa-pause' : 'fa-play'}"></i> 
                        ${zone.is_active ? 'Deactivate' : 'Activate'}
                    </button>
                </div>
            </div>
        `).join('');

        this.zones = zones;
    }

    initializeAdminMap() {
        if (this.adminMap) {
            return; // Map already initialized
        }

        const mapContainer = document.getElementById('admin-map');
        if (!mapContainer) return;

        this.adminMap = L.map('admin-map').setView([40.7128, -74.0060], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(this.adminMap);

        // Add zone markers
        if (this.zones) {
            this.zones.forEach(zone => {
                const marker = L.marker([zone.x_coordinate, zone.y_coordinate]).addTo(this.adminMap);
                marker.bindPopup(`
                    <div class="zone-popup">
                        <h4>${zone.name}</h4>
                        <p><strong>Status:</strong> ${zone.is_active ? 'Active' : 'Inactive'}</p>
                        <p><strong>Total Slots:</strong> ${zone.total_slots}</p>
                        <p><strong>Rate:</strong> $${zone.hourly_rate}/hour</p>
                        <button class="btn btn-sm btn-primary" onclick="adminPanel.editZone(${zone.id})">
                            Edit Zone
                        </button>
                    </div>
                `);
            });
        }
    }

    async loadBookings(filter = 'all') {
        try {
            // Sample bookings data
            const bookings = [
                {
                    id: 1,
                    user_name: 'John Doe',
                    user_email: 'john@example.com',
                    zone_name: 'Zone A - Mall Entrance',
                    slot_number: 'A01',
                    start_time: '2024-01-20 09:00:00',
                    end_time: '2024-01-20 11:00:00',
                    total_amount: 10.00,
                    status: 'confirmed'
                },
                {
                    id: 2,
                    user_name: 'Jane Smith',
                    user_email: 'jane@example.com',
                    zone_name: 'Zone B - Office Complex',
                    slot_number: 'B02',
                    start_time: '2024-01-20 08:30:00',
                    end_time: '2024-01-20 17:30:00',
                    total_amount: 36.00,
                    status: 'active'
                },
                {
                    id: 3,
                    user_name: 'Mike Johnson',
                    user_email: 'mike@example.com',
                    zone_name: 'Zone C - Residential Area',
                    slot_number: 'C01',
                    start_time: '2024-01-19 14:00:00',
                    end_time: '2024-01-19 18:00:00',
                    total_amount: 12.00,
                    status: 'completed'
                }
            ];

            const filteredBookings = filter === 'all' ? bookings : 
                                   bookings.filter(b => b.status === filter);

            this.displayBookings(filteredBookings);
        } catch (error) {
            console.error('Error loading bookings:', error);
        }
    }

    displayBookings(bookings) {
        const tbody = document.querySelector('#bookings-table tbody');
        
        tbody.innerHTML = bookings.map(booking => `
            <tr>
                <td>${booking.id}</td>
                <td>
                    <div>
                        <strong>${booking.user_name}</strong><br>
                        <small class="text-muted">${booking.user_email}</small>
                    </div>
                </td>
                <td>${booking.zone_name}</td>
                <td>${booking.slot_number}</td>
                <td>${new Date(booking.start_time).toLocaleString()}</td>
                <td>${new Date(booking.end_time).toLocaleString()}</td>
                <td>$${booking.total_amount.toFixed(2)}</td>
                <td><span class="status-badge status-${booking.status}">${booking.status}</span></td>
                <td>
                    <div class="table-actions">
                        <button class="btn btn-sm btn-info" onclick="adminPanel.viewBooking(${booking.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${booking.status === 'confirmed' ? 
                            `<button class="btn btn-sm btn-warning" onclick="adminPanel.cancelBooking(${booking.id})">
                                <i class="fas fa-times"></i>
                            </button>` : ''
                        }
                    </div>
                </td>
            </tr>
        `).join('');
    }

    async loadUsers() {
        try {
            // Sample users data
            const users = [
                {
                    id: 1,
                    username: 'admin',
                    email: 'admin@smartparking.com',
                    full_name: 'System Admin',
                    role: 'admin',
                    created_at: '2024-01-01 00:00:00'
                },
                {
                    id: 2,
                    username: 'john_doe',
                    email: 'john@example.com',
                    full_name: 'John Doe',
                    role: 'user',
                    created_at: '2024-01-15 10:30:00'
                },
                {
                    id: 3,
                    username: 'jane_smith',
                    email: 'jane@example.com',
                    full_name: 'Jane Smith',
                    role: 'user',
                    created_at: '2024-01-18 14:20:00'
                }
            ];

            this.displayUsers(users);
        } catch (error) {
            console.error('Error loading users:', error);
        }
    }

    displayUsers(users) {
        const tbody = document.querySelector('#users-table tbody');
        
        tbody.innerHTML = users.map(user => `
            <tr>
                <td>${user.id}</td>
                <td>${user.username}</td>
                <td>${user.email}</td>
                <td>${user.full_name}</td>
                <td>
                    <span class="status-badge status-${user.role === 'admin' ? 'confirmed' : 'active'}">
                        ${user.role}
                    </span>
                </td>
                <td>${new Date(user.created_at).toLocaleDateString()}</td>
                <td>
                    <div class="table-actions">
                        <button class="btn btn-sm btn-info" onclick="adminPanel.viewUser(${user.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-primary" onclick="adminPanel.editUser(${user.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        ${user.role !== 'admin' ? 
                            `<button class="btn btn-sm btn-danger" onclick="adminPanel.deleteUser(${user.id})">
                                <i class="fas fa-trash"></i>
                            </button>` : ''
                        }
                    </div>
                </td>
            </tr>
        `).join('');
    }

    async loadAnalytics(period = 'week') {
        // Initialize analytics charts
        this.initializeAnalyticsCharts();
    }

    initializeAnalyticsCharts() {
        // Usage Patterns Chart
        const usagePatternsCtx = document.getElementById('usage-patterns-chart');
        if (usagePatternsCtx) {
            new Chart(usagePatternsCtx, {
                type: 'line',
                data: {
                    labels: Array.from({length: 24}, (_, i) => `${i}:00`),
                    datasets: [{
                        label: 'Occupancy Rate (%)',
                        data: [20, 15, 12, 10, 8, 12, 25, 45, 65, 70, 68, 72, 75, 70, 65, 60, 70, 85, 80, 65, 50, 40, 35, 25],
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });
        }

        // Zone Performance Chart
        const zonePerformanceCtx = document.getElementById('zone-performance-chart');
        if (zonePerformanceCtx) {
            new Chart(zonePerformanceCtx, {
                type: 'bar',
                data: {
                    labels: ['Zone A', 'Zone B', 'Zone C', 'Zone D'],
                    datasets: [{
                        label: 'Average Occupancy (%)',
                        data: [75, 82, 65, 45],
                        backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#dc3545'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });
        }

        // Zone Revenue Chart
        const zoneRevenueCtx = document.getElementById('zone-revenue-chart');
        if (zoneRevenueCtx) {
            new Chart(zoneRevenueCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Zone A', 'Zone B', 'Zone C', 'Zone D'],
                    datasets: [{
                        data: [2500, 3200, 1800, 1200],
                        backgroundColor: ['#667eea', '#28a745', '#ffc107', '#dc3545'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': $' + context.parsed.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }

        // User Activity Chart
        const userActivityCtx = document.getElementById('user-activity-chart');
        if (userActivityCtx) {
            new Chart(userActivityCtx, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [
                        {
                            label: 'New Users',
                            data: [12, 8, 15, 20, 18, 25, 22],
                            borderColor: '#28a745',
                            backgroundColor: 'rgba(40, 167, 69, 0.1)',
                            borderWidth: 3,
                            tension: 0.4
                        },
                        {
                            label: 'Active Users',
                            data: [45, 52, 48, 61, 58, 67, 63],
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            borderWidth: 3,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    }

    // Zone Management Methods
    async handleAddZone(e) {
        const formData = new FormData(e.target);
        const zoneData = {
            name: formData.get('name') || document.getElementById('zone-name').value,
            description: formData.get('description') || document.getElementById('zone-description').value,
            x_coordinate: parseFloat(formData.get('latitude') || document.getElementById('zone-latitude').value),
            y_coordinate: parseFloat(formData.get('longitude') || document.getElementById('zone-longitude').value),
            total_slots: parseInt(formData.get('total_slots') || document.getElementById('zone-total-slots').value),
            hourly_rate: parseFloat(formData.get('hourly_rate') || document.getElementById('zone-hourly-rate').value)
        };

        try {
            // In a real implementation, this would be an API call
            console.log('Adding zone:', zoneData);
            this.showAlert('Zone added successfully!', 'success');
            this.closeModal('add-zone-modal');
            this.loadZones();
        } catch (error) {
            console.error('Add zone error:', error);
            this.showAlert('Failed to add zone', 'error');
        }
    }

    editZone(zoneId) {
        const zone = this.zones?.find(z => z.id === zoneId);
        if (!zone) return;

        // Populate edit form
        document.getElementById('edit-zone-id').value = zone.id;
        document.getElementById('edit-zone-name').value = zone.name;
        document.getElementById('edit-zone-description').value = zone.description || '';
        document.getElementById('edit-zone-latitude').value = zone.x_coordinate;
        document.getElementById('edit-zone-longitude').value = zone.y_coordinate;
        document.getElementById('edit-zone-total-slots').value = zone.total_slots;
        document.getElementById('edit-zone-hourly-rate').value = zone.hourly_rate;
        document.getElementById('edit-zone-active').checked = zone.is_active;

        this.showModal('edit-zone-modal');
    }

    async handleEditZone(e) {
        const formData = new FormData(e.target);
        const zoneData = {
            id: parseInt(document.getElementById('edit-zone-id').value),
            name: document.getElementById('edit-zone-name').value,
            description: document.getElementById('edit-zone-description').value,
            x_coordinate: parseFloat(document.getElementById('edit-zone-latitude').value),
            y_coordinate: parseFloat(document.getElementById('edit-zone-longitude').value),
            total_slots: parseInt(document.getElementById('edit-zone-total-slots').value),
            hourly_rate: parseFloat(document.getElementById('edit-zone-hourly-rate').value),
            is_active: document.getElementById('edit-zone-active').checked
        };

        try {
            console.log('Updating zone:', zoneData);
            this.showAlert('Zone updated successfully!', 'success');
            this.closeModal('edit-zone-modal');
            this.loadZones();
        } catch (error) {
            console.error('Edit zone error:', error);
            this.showAlert('Failed to update zone', 'error');
        }
    }

    async toggleZoneStatus(zoneId, newStatus) {
        try {
            console.log(`Toggling zone ${zoneId} status to ${newStatus}`);
            this.showAlert(`Zone ${newStatus ? 'activated' : 'deactivated'} successfully!`, 'success');
            this.loadZones();
        } catch (error) {
            console.error('Toggle zone status error:', error);
            this.showAlert('Failed to update zone status', 'error');
        }
    }

    // Utility Methods
    showModal(modalId) {
        document.getElementById(modalId).style.display = 'block';
    }

    closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    showAlert(message, type = 'info') {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.textContent = message;
        alert.style.position = 'fixed';
        alert.style.top = '100px';
        alert.style.right = '20px';
        alert.style.zIndex = '3000';
        alert.style.minWidth = '300px';

        document.body.appendChild(alert);

        setTimeout(() => {
            document.body.removeChild(alert);
        }, 5000);
    }

    filterZones(searchTerm) {
        const zoneCards = document.querySelectorAll('.zone-card');
        zoneCards.forEach(card => {
            const zoneName = card.querySelector('h4').textContent.toLowerCase();
            const zoneDescription = card.querySelector('p').textContent.toLowerCase();
            
            if (zoneName.includes(searchTerm.toLowerCase()) || 
                zoneDescription.includes(searchTerm.toLowerCase())) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }
}

// Global functions
function showAddZoneModal() {
    adminPanel.showModal('add-zone-modal');
}

function closeModal(modalId) {
    adminPanel.closeModal(modalId);
}

function refreshDashboard() {
    adminPanel.loadDashboardData();
}

function exportBookings() {
    // Implement booking export functionality
    adminPanel.showAlert('Export functionality coming soon!', 'info');
}

function adminLogout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    window.location.href = '../frontend/index.html';
}

// Initialize admin panel
document.addEventListener('DOMContentLoaded', () => {
    window.adminPanel = new AdminPanel();
});

// Close modals when clicking outside
window.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
    }
});