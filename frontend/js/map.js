// Map Manager for Smart Parking System
class MapManager {
    constructor() {
        this.map = null;
        this.markers = [];
        this.userMarker = null;
        this.recommendations = null;
    }

    initializeMap() {
        if (this.map) {
            return; // Map already initialized
        }

        // Default center (New York City)
        const defaultCenter = [40.7128, -74.0060];
        const userLocation = window.app?.currentLocation;
        const center = userLocation ? [userLocation.latitude, userLocation.longitude] : defaultCenter;

        // Initialize Leaflet map
        this.map = L.map('parking-map').setView(center, 13);

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(this.map);

        // Add user location marker
        if (userLocation) {
            this.userMarker = L.marker([userLocation.latitude, userLocation.longitude], {
                icon: this.createUserIcon()
            }).addTo(this.map)
            .bindPopup('Your Location')
            .openPopup();
        }

        // Load parking zones and slots
        this.loadParkingData();

        // Map click handler
        this.map.on('click', (e) => {
            this.onMapClick(e);
        });
    }

    createUserIcon() {
        return L.divIcon({
            className: 'user-location-icon',
            html: '<i class="fas fa-user-circle" style="color: #007bff; font-size: 24px;"></i>',
            iconSize: [24, 24],
            iconAnchor: [12, 12]
        });
    }

    createSlotIcon(status, slotType = 'regular') {
        const colors = {
            available: '#28a745',
            occupied: '#dc3545',
            reserved: '#ffc107',
            maintenance: '#6c757d'
        };

        const icons = {
            regular: 'fa-car',
            disabled: 'fa-wheelchair',
            electric: 'fa-bolt'
        };

        return L.divIcon({
            className: 'parking-slot-icon',
            html: `<i class="fas ${icons[slotType]}" style="color: ${colors[status]}; font-size: 16px;"></i>`,
            iconSize: [20, 20],
            iconAnchor: [10, 10]
        });
    }

    async loadParkingData() {
        try {
            // In a real implementation, you would fetch this from the API
            // For now, we'll create sample data based on our database schema
            const sampleZones = [
                {
                    id: 1,
                    name: 'Zone A - Mall Entrance',
                    x_coordinate: 40.7128,
                    y_coordinate: -74.0060,
                    slots: [
                        { id: 1, slot_number: 'A01', status: 'available', slot_type: 'regular', x_coordinate: 40.7128, y_coordinate: -74.0060 },
                        { id: 2, slot_number: 'A02', status: 'occupied', slot_type: 'regular', x_coordinate: 40.7129, y_coordinate: -74.0060 },
                        { id: 3, slot_number: 'A03', status: 'available', slot_type: 'disabled', x_coordinate: 40.7130, y_coordinate: -74.0060 },
                        { id: 4, slot_number: 'A04', status: 'reserved', slot_type: 'regular', x_coordinate: 40.7131, y_coordinate: -74.0060 },
                        { id: 5, slot_number: 'A05', status: 'available', slot_type: 'electric', x_coordinate: 40.7132, y_coordinate: -74.0060 }
                    ]
                },
                {
                    id: 2,
                    name: 'Zone B - Office Complex',
                    x_coordinate: 40.7138,
                    y_coordinate: -74.0070,
                    slots: [
                        { id: 6, slot_number: 'B01', status: 'available', slot_type: 'regular', x_coordinate: 40.7138, y_coordinate: -74.0070 },
                        { id: 7, slot_number: 'B02', status: 'available', slot_type: 'regular', x_coordinate: 40.7139, y_coordinate: -74.0070 },
                        { id: 8, slot_number: 'B03', status: 'occupied', slot_type: 'disabled', x_coordinate: 40.7140, y_coordinate: -74.0070 },
                        { id: 9, slot_number: 'B04', status: 'available', slot_type: 'electric', x_coordinate: 40.7141, y_coordinate: -74.0070 }
                    ]
                },
                {
                    id: 3,
                    name: 'Zone C - Residential Area',
                    x_coordinate: 40.7118,
                    y_coordinate: -74.0050,
                    slots: [
                        { id: 10, slot_number: 'C01', status: 'available', slot_type: 'regular', x_coordinate: 40.7118, y_coordinate: -74.0050 },
                        { id: 11, slot_number: 'C02', status: 'maintenance', slot_type: 'regular', x_coordinate: 40.7119, y_coordinate: -74.0050 }
                    ]
                },
                {
                    id: 4,
                    name: 'Zone D - Shopping Center',
                    x_coordinate: 40.7148,
                    y_coordinate: -74.0080,
                    slots: [
                        { id: 12, slot_number: 'D01', status: 'available', slot_type: 'regular', x_coordinate: 40.7148, y_coordinate: -74.0080 },
                        { id: 13, slot_number: 'D02', status: 'available', slot_type: 'disabled', x_coordinate: 40.7149, y_coordinate: -74.0080 }
                    ]
                }
            ];

            this.displayParkingZones(sampleZones);
        } catch (error) {
            console.error('Error loading parking data:', error);
        }
    }

    displayParkingZones(zones) {
        zones.forEach(zone => {
            // Add zone marker
            const zoneMarker = L.marker([zone.x_coordinate, zone.y_coordinate], {
                icon: L.divIcon({
                    className: 'zone-marker',
                    html: `<div class="zone-label">${zone.name}</div>`,
                    iconSize: [120, 30],
                    iconAnchor: [60, 15]
                })
            }).addTo(this.map);

            // Add zone info popup
            zoneMarker.bindPopup(`
                <div class="zone-popup">
                    <h4>${zone.name}</h4>
                    <p>Total Slots: ${zone.slots.length}</p>
                    <p>Available: ${zone.slots.filter(s => s.status === 'available').length}</p>
                </div>
            `);

            // Add individual slot markers
            zone.slots.forEach(slot => {
                const slotMarker = L.marker([slot.x_coordinate, slot.y_coordinate], {
                    icon: this.createSlotIcon(slot.status, slot.slot_type)
                }).addTo(this.map);

                slotMarker.bindPopup(this.createSlotPopup(slot, zone));
                
                // Store reference for later use
                slotMarker.slotData = { ...slot, zone_name: zone.name, zone_id: zone.id };
                this.markers.push(slotMarker);
            });
        });
    }

    createSlotPopup(slot, zone) {
        const statusClass = `status-${slot.status}`;
        const typeIcon = {
            regular: 'fa-car',
            disabled: 'fa-wheelchair',
            electric: 'fa-bolt'
        };

        return `
            <div class="slot-popup">
                <h4>${zone.name}</h4>
                <p><strong>Slot:</strong> ${slot.slot_number}</p>
                <p><strong>Type:</strong> <i class="fas ${typeIcon[slot.slot_type]}"></i> ${slot.slot_type}</p>
                <p><strong>Status:</strong> <span class="booking-status ${statusClass}">${slot.status}</span></p>
                ${slot.status === 'available' ? 
                    `<button class="btn btn-primary btn-sm" onclick="mapManager.bookSlot(${slot.id})">
                        <i class="fas fa-calendar-plus"></i> Book Slot
                    </button>` : ''
                }
            </div>
        `;
    }

    bookSlot(slotId) {
        const slotMarker = this.markers.find(marker => marker.slotData?.id === slotId);
        if (!slotMarker) return;

        const slotData = slotMarker.slotData;
        
        if (!window.app.currentUser) {
            window.app.showAlert('Please login to book a slot', 'error');
            window.app.showModal('login-modal');
            return;
        }

        // Store selected slot
        window.app.selectedSlot = slotData;

        // Update booking modal with slot details
        document.getElementById('slot-details').innerHTML = `
            <div class="selected-slot">
                <h4>${slotData.zone_name} - ${slotData.slot_number}</h4>
                <p><strong>Type:</strong> ${slotData.slot_type}</p>
                <p><strong>Rate:</strong> $5.00/hour</p>
            </div>
        `;

        // Show booking modal
        window.app.showModal('booking-modal');

        // Update booking summary when times change
        const startTime = document.getElementById('start-time');
        const endTime = document.getElementById('end-time');
        
        [startTime, endTime].forEach(input => {
            input.addEventListener('change', () => {
                this.updateBookingSummary();
            });
        });

        this.updateBookingSummary();
    }

    updateBookingSummary() {
        const startTime = document.getElementById('start-time').value;
        const endTime = document.getElementById('end-time').value;
        const summaryDiv = document.getElementById('booking-summary');

        if (startTime && endTime) {
            const start = new Date(startTime);
            const end = new Date(endTime);
            const duration = (end - start) / (1000 * 60 * 60); // hours
            const rate = 5.00; // $5/hour
            const total = duration * rate;

            summaryDiv.innerHTML = `
                <div class="summary">
                    <h5>Booking Summary</h5>
                    <p><strong>Duration:</strong> ${duration.toFixed(1)} hours</p>
                    <p><strong>Rate:</strong> $${rate.toFixed(2)}/hour</p>
                    <p><strong>Total:</strong> $${total.toFixed(2)}</p>
                </div>
            `;
        }
    }

    displayRecommendations(recommendations) {
        const recommendationsList = document.getElementById('recommendations-list');
        
        if (!recommendations || Object.keys(recommendations).length === 0) {
            recommendationsList.innerHTML = '<p>No recommendations available</p>';
            return;
        }

        let html = '';
        
        if (recommendations.nearest) {
            html += this.createRecommendationCard('Nearest Slot', recommendations.nearest, 'primary');
        }
        
        if (recommendations.cheapest) {
            html += this.createRecommendationCard('Cheapest Slot', recommendations.cheapest, 'success');
        }
        
        if (recommendations.best_value) {
            html += this.createRecommendationCard('Best Value', recommendations.best_value, 'info');
        }

        recommendationsList.innerHTML = html;

        // Highlight recommended slots on map
        this.highlightRecommendedSlots(recommendations);
    }

    createRecommendationCard(title, slot, type) {
        return `
            <div class="recommendation-card ${type}">
                <h4>${title}</h4>
                <p><strong>${slot.zone_name}</strong> - ${slot.slot_number}</p>
                <p><i class="fas fa-map-marker-alt"></i> Distance: ${(Math.random() * 2 + 0.1).toFixed(1)} km</p>
                <p class="price"><i class="fas fa-dollar-sign"></i> $${slot.hourly_rate}/hour</p>
                <button class="btn btn-sm btn-primary" onclick="mapManager.bookSlot(${slot.id})">
                    Book Now
                </button>
            </div>
        `;
    }

    highlightRecommendedSlots(recommendations) {
        // Reset all markers
        this.markers.forEach(marker => {
            if (marker.slotData) {
                marker.setIcon(this.createSlotIcon(marker.slotData.status, marker.slotData.slot_type));
            }
        });

        // Highlight recommended slots
        Object.values(recommendations).forEach((slot, index) => {
            const marker = this.markers.find(m => m.slotData?.id === slot.id);
            if (marker) {
                // Create highlighted icon
                const colors = ['#007bff', '#28a745', '#17a2b8']; // primary, success, info
                const highlightIcon = L.divIcon({
                    className: 'recommended-slot-icon',
                    html: `<div class="highlight-ring" style="border-color: ${colors[index % colors.length]}">
                             <i class="fas fa-star" style="color: ${colors[index % colors.length]}; font-size: 16px;"></i>
                           </div>`,
                    iconSize: [30, 30],
                    iconAnchor: [15, 15]
                });
                
                marker.setIcon(highlightIcon);
                
                // Open popup for the first recommendation
                if (index === 0) {
                    marker.openPopup();
                    this.map.setView([slot.x_coordinate, slot.y_coordinate], 16);
                }
            }
        });
    }

    onMapClick(e) {
        // Close any open popups
        this.map.closePopup();
    }

    // Method to update slot status in real-time
    updateSlotStatus(slotId, newStatus) {
        const marker = this.markers.find(m => m.slotData?.id === slotId);
        if (marker) {
            marker.slotData.status = newStatus;
            marker.setIcon(this.createSlotIcon(newStatus, marker.slotData.slot_type));
            
            // Update popup content
            const zone = { name: marker.slotData.zone_name };
            marker.setPopupContent(this.createSlotPopup(marker.slotData, zone));
        }
    }

    // Method to add route to selected slot
    addRouteToSlot(slotCoordinates) {
        if (!window.app.currentLocation) return;

        const userLocation = window.app.currentLocation;
        
        // Simple straight line route (in real implementation, use routing service)
        const route = L.polyline([
            [userLocation.latitude, userLocation.longitude],
            [slotCoordinates.latitude, slotCoordinates.longitude]
        ], {
            color: '#007bff',
            weight: 4,
            opacity: 0.7,
            dashArray: '10, 5'
        }).addTo(this.map);

        // Fit map to show route
        this.map.fitBounds(route.getBounds(), { padding: [20, 20] });

        return route;
    }
}

// Initialize map manager
document.addEventListener('DOMContentLoaded', () => {
    window.mapManager = new MapManager();
});

// CSS for custom map elements
const mapStyles = `
<style>
.zone-label {
    background: rgba(102, 126, 234, 0.9);
    color: white;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: bold;
    text-align: center;
    white-space: nowrap;
}

.zone-popup h4 {
    margin: 0 0 10px 0;
    color: #333;
}

.zone-popup p {
    margin: 5px 0;
}

.slot-popup {
    min-width: 200px;
}

.slot-popup h4 {
    margin: 0 0 10px 0;
    color: #333;
}

.slot-popup p {
    margin: 5px 0;
}

.selected-slot {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    border-left: 4px solid #667eea;
}

.highlight-ring {
    width: 28px;
    height: 28px;
    border: 3px solid #007bff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(0, 123, 255, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(0, 123, 255, 0);
    }
}

.recommendation-card.primary {
    border-left-color: #007bff;
}

.recommendation-card.success {
    border-left-color: #28a745;
}

.recommendation-card.info {
    border-left-color: #17a2b8;
}

.user-location-icon {
    background: none;
    border: none;
}

.parking-slot-icon {
    background: none;
    border: none;
}

.recommended-slot-icon {
    background: none;
    border: none;
}
</style>
`;

// Inject styles
document.head.insertAdjacentHTML('beforeend', mapStyles);