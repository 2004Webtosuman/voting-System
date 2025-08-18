// Smart Parking System - Main Application
class SmartParkingApp {
    constructor() {
        this.apiBase = '../api';
        this.currentUser = null;
        this.currentLocation = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.checkAuthStatus();
        this.getCurrentLocation();
        this.initializeDateInputs();
    }

    setupEventListeners() {
        // Navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const section = e.target.getAttribute('href').substring(1);
                this.showSection(section);
            });
        });

        // Hamburger menu
        const hamburger = document.getElementById('hamburger');
        const navMenu = document.getElementById('nav-menu');
        
        hamburger?.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });

        // Form submissions
        this.setupFormHandlers();
    }

    setupFormHandlers() {
        // Login form
        document.getElementById('login-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.handleLogin(e);
        });

        // Register form
        document.getElementById('register-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.handleRegister(e);
        });

        // Booking form
        document.getElementById('booking-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.handleBooking(e);
        });
    }

    showSection(sectionId) {
        // Hide all sections
        document.querySelectorAll('.section').forEach(section => {
            section.classList.remove('active');
        });

        // Show target section
        const targetSection = document.getElementById(sectionId);
        if (targetSection) {
            targetSection.classList.add('active');
            
            // Update navigation
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            document.querySelector(`[href="#${sectionId}"]`)?.classList.add('active');

            // Load section-specific data
            this.loadSectionData(sectionId);
        }
    }

    loadSectionData(sectionId) {
        switch (sectionId) {
            case 'map':
                if (window.mapManager) {
                    window.mapManager.initializeMap();
                }
                break;
            case 'bookings':
                if (this.currentUser) {
                    this.loadUserBookings();
                }
                break;
            case 'predictions':
                this.loadPredictions();
                break;
        }
    }

    async handleLogin(e) {
        const formData = new FormData(e.target);
        const loginData = {
            email: formData.get('email') || document.getElementById('login-email').value,
            password: formData.get('password') || document.getElementById('login-password').value
        };

        try {
            const response = await fetch(`${this.apiBase}/auth/login.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(loginData)
            });

            const result = await response.json();

            if (result.success) {
                this.currentUser = result.user;
                localStorage.setItem('token', result.token);
                localStorage.setItem('user', JSON.stringify(result.user));
                
                this.updateAuthUI();
                this.closeModal('login-modal');
                this.showAlert('Login successful!', 'success');
            } else {
                this.showAlert(result.message, 'error');
            }
        } catch (error) {
            console.error('Login error:', error);
            this.showAlert('Login failed. Please try again.', 'error');
        }
    }

    async handleRegister(e) {
        const formData = new FormData(e.target);
        const registerData = {
            username: formData.get('username') || document.getElementById('register-username').value,
            email: formData.get('email') || document.getElementById('register-email').value,
            full_name: formData.get('full_name') || document.getElementById('register-fullname').value,
            phone: formData.get('phone') || document.getElementById('register-phone').value,
            password: formData.get('password') || document.getElementById('register-password').value
        };

        try {
            const response = await fetch(`${this.apiBase}/auth/register.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(registerData)
            });

            const result = await response.json();

            if (result.success) {
                this.closeModal('register-modal');
                this.showAlert('Registration successful! Please login.', 'success');
                this.showLogin();
            } else {
                this.showAlert(result.message, 'error');
            }
        } catch (error) {
            console.error('Registration error:', error);
            this.showAlert('Registration failed. Please try again.', 'error');
        }
    }

    async handleBooking(e) {
        if (!this.currentUser) {
            this.showAlert('Please login to book a slot', 'error');
            return;
        }

        const formData = new FormData(e.target);
        const bookingData = {
            slot_id: this.selectedSlot?.id,
            start_time: formData.get('start_time') || document.getElementById('start-time').value,
            end_time: formData.get('end_time') || document.getElementById('end-time').value
        };

        try {
            const response = await fetch(`${this.apiBase}/bookings/create.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                },
                body: JSON.stringify(bookingData)
            });

            const result = await response.json();

            if (result.success) {
                this.closeModal('booking-modal');
                this.showAlert('Booking successful!', 'success');
                this.showQRCode(result.booking.qr_code);
                this.loadUserBookings();
            } else {
                this.showAlert(result.message, 'error');
            }
        } catch (error) {
            console.error('Booking error:', error);
            this.showAlert('Booking failed. Please try again.', 'error');
        }
    }

    checkAuthStatus() {
        const token = localStorage.getItem('token');
        const user = localStorage.getItem('user');

        if (token && user) {
            try {
                const tokenData = JSON.parse(atob(token.split('.')[1] || token));
                if (tokenData.exp > Date.now() / 1000) {
                    this.currentUser = JSON.parse(user);
                    this.updateAuthUI();
                } else {
                    this.logout();
                }
            } catch (error) {
                console.error('Token validation error:', error);
                this.logout();
            }
        }
    }

    updateAuthUI() {
        const authSection = document.getElementById('nav-auth');
        const userMenu = document.getElementById('user-menu');
        const userName = document.getElementById('user-name');

        if (this.currentUser) {
            authSection.style.display = 'none';
            userMenu.style.display = 'flex';
            userName.textContent = this.currentUser.full_name;
        } else {
            authSection.style.display = 'flex';
            userMenu.style.display = 'none';
        }
    }

    logout() {
        this.currentUser = null;
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        this.updateAuthUI();
        this.showAlert('Logged out successfully', 'info');
        this.showSection('home');
    }

    getCurrentLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.currentLocation = {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude
                    };
                },
                (error) => {
                    console.warn('Geolocation error:', error);
                    // Use default location (New York City)
                    this.currentLocation = {
                        latitude: 40.7128,
                        longitude: -74.0060
                    };
                }
            );
        } else {
            // Default location
            this.currentLocation = {
                latitude: 40.7128,
                longitude: -74.0060
            };
        }
    }

    async loadUserBookings() {
        if (!this.currentUser) {
            document.getElementById('bookings-list').innerHTML = 
                '<p class="text-center">Please login to view your bookings</p>';
            return;
        }

        try {
            const response = await fetch(`${this.apiBase}/bookings/user_bookings.php`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            });

            const result = await response.json();

            if (result.success) {
                this.displayBookings(result.bookings);
            } else {
                this.showAlert(result.message, 'error');
            }
        } catch (error) {
            console.error('Error loading bookings:', error);
            this.showAlert('Failed to load bookings', 'error');
        }
    }

    displayBookings(bookings) {
        const bookingsList = document.getElementById('bookings-list');

        if (bookings.length === 0) {
            bookingsList.innerHTML = '<p class="text-center">No bookings found</p>';
            return;
        }

        bookingsList.innerHTML = bookings.map(booking => `
            <div class="booking-card">
                <h3>${booking.zone_name} - ${booking.slot_number}</h3>
                <div class="booking-details">
                    <p><strong>Start:</strong> ${new Date(booking.start_time).toLocaleString()}</p>
                    <p><strong>End:</strong> ${new Date(booking.end_time).toLocaleString()}</p>
                    <p><strong>Amount:</strong> $${booking.total_amount}</p>
                    <p><strong>Status:</strong> 
                        <span class="booking-status status-${booking.status}">${booking.status}</span>
                    </p>
                </div>
                <div class="booking-actions">
                    ${booking.status === 'confirmed' ? 
                        `<button class="btn btn-primary" onclick="app.showQRCode('${booking.qr_code}')">
                            <i class="fas fa-qrcode"></i> Show QR
                        </button>` : ''
                    }
                    ${booking.status === 'confirmed' ? 
                        `<button class="btn btn-outline" onclick="app.cancelBooking(${booking.id})">
                            Cancel
                        </button>` : ''
                    }
                </div>
            </div>
        `).join('');
    }

    async cancelBooking(bookingId) {
        if (!confirm('Are you sure you want to cancel this booking?')) {
            return;
        }

        try {
            const response = await fetch(`${this.apiBase}/bookings/cancel.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                },
                body: JSON.stringify({ booking_id: bookingId })
            });

            const result = await response.json();

            if (result.success) {
                this.showAlert('Booking cancelled successfully', 'success');
                this.loadUserBookings();
            } else {
                this.showAlert(result.message, 'error');
            }
        } catch (error) {
            console.error('Cancel booking error:', error);
            this.showAlert('Failed to cancel booking', 'error');
        }
    }

    showQRCode(qrCode) {
        const qrDisplay = document.getElementById('qr-code-display');
        qrDisplay.innerHTML = '';

        QRCode.toCanvas(qrDisplay, qrCode, {
            width: 200,
            height: 200,
            margin: 2
        }, (error) => {
            if (error) {
                console.error('QR Code generation error:', error);
                qrDisplay.innerHTML = '<p>Failed to generate QR code</p>';
            }
        });

        this.showModal('qr-modal');
    }

    initializeDateInputs() {
        const now = new Date();
        const tomorrow = new Date(now);
        tomorrow.setDate(tomorrow.getDate() + 1);

        // Set default prediction date to today
        const predictionDate = document.getElementById('prediction-date');
        if (predictionDate) {
            predictionDate.value = now.toISOString().split('T')[0];
        }

        // Populate hour select
        const hourSelect = document.getElementById('prediction-hour');
        if (hourSelect) {
            for (let i = 0; i < 24; i++) {
                const option = document.createElement('option');
                option.value = i;
                option.textContent = `${i.toString().padStart(2, '0')}:00`;
                if (i === now.getHours()) {
                    option.selected = true;
                }
                hourSelect.appendChild(option);
            }
        }

        // Set default booking times
        const startTime = document.getElementById('start-time');
        const endTime = document.getElementById('end-time');
        
        if (startTime && endTime) {
            const startDateTime = new Date(now);
            startDateTime.setMinutes(0, 0, 0);
            startTime.value = startDateTime.toISOString().slice(0, 16);

            const endDateTime = new Date(startDateTime);
            endDateTime.setHours(endDateTime.getHours() + 2);
            endTime.value = endDateTime.toISOString().slice(0, 16);
        }
    }

    showModal(modalId) {
        document.getElementById(modalId).style.display = 'block';
    }

    closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    showAlert(message, type = 'info') {
        // Create alert element
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.textContent = message;
        alert.style.position = 'fixed';
        alert.style.top = '100px';
        alert.style.right = '20px';
        alert.style.zIndex = '3000';
        alert.style.minWidth = '300px';
        alert.style.animation = 'slideInRight 0.3s ease-out';

        document.body.appendChild(alert);

        // Auto remove after 5 seconds
        setTimeout(() => {
            alert.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => {
                document.body.removeChild(alert);
            }, 300);
        }, 5000);
    }

    async findNearestSlot() {
        if (!this.currentLocation) {
            this.showAlert('Location not available. Please enable location services.', 'error');
            return;
        }

        const slotType = document.getElementById('slot-type-filter')?.value || 'regular';

        try {
            const response = await fetch(`${this.apiBase}/slots/find_nearest.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    latitude: this.currentLocation.latitude,
                    longitude: this.currentLocation.longitude,
                    slot_type: slotType
                })
            });

            const result = await response.json();

            if (result.success && window.mapManager) {
                window.mapManager.displayRecommendations(result.recommendations);
            } else {
                this.showAlert('No available slots found', 'info');
            }
        } catch (error) {
            console.error('Find nearest slot error:', error);
            this.showAlert('Failed to find nearest slot', 'error');
        }
    }

    async loadPredictions() {
        const date = document.getElementById('prediction-date')?.value || new Date().toISOString().split('T')[0];
        const hour = document.getElementById('prediction-hour')?.value || new Date().getHours();

        try {
            const response = await fetch(`${this.apiBase}/ai/predictions.php?type=all_zones&date=${date}&hour=${hour}`);
            const result = await response.json();

            if (result.success && window.predictionsManager) {
                window.predictionsManager.displayPredictions(result.predictions);
            }
        } catch (error) {
            console.error('Load predictions error:', error);
            this.showAlert('Failed to load predictions', 'error');
        }
    }
}

// Global functions for HTML onclick handlers
function showLogin() {
    app.showModal('login-modal');
}

function showRegister() {
    app.showModal('register-modal');
}

function logout() {
    app.logout();
}

function closeModal(modalId) {
    app.closeModal(modalId);
}

function showSection(sectionId) {
    app.showSection(sectionId);
}

function findParking() {
    app.showSection('map');
    setTimeout(() => {
        app.findNearestSlot();
    }, 500);
}

function findNearestSlot() {
    app.findNearestSlot();
}

function loadPredictions() {
    app.loadPredictions();
}

function downloadQR() {
    const canvas = document.querySelector('#qr-code-display canvas');
    if (canvas) {
        const link = document.createElement('a');
        link.download = 'parking-qr-code.png';
        link.href = canvas.toDataURL();
        link.click();
    }
}

function shareQR() {
    if (navigator.share) {
        const canvas = document.querySelector('#qr-code-display canvas');
        if (canvas) {
            canvas.toBlob((blob) => {
                const file = new File([blob], 'parking-qr-code.png', { type: 'image/png' });
                navigator.share({
                    title: 'Parking QR Code',
                    text: 'My parking slot QR code',
                    files: [file]
                });
            });
        }
    } else {
        app.showAlert('Sharing not supported on this device', 'info');
    }
}

// Initialize app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.app = new SmartParkingApp();
});

// Close modals when clicking outside
window.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
    }
});