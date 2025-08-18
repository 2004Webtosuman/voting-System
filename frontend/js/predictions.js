// AI Predictions Manager for Smart Parking System
class PredictionsManager {
    constructor() {
        this.chart = null;
        this.currentPredictions = [];
    }

    displayPredictions(predictions) {
        this.currentPredictions = predictions;
        this.renderPredictionCards(predictions);
        this.renderPredictionChart(predictions);
    }

    renderPredictionCards(predictions) {
        const predictionsGrid = document.getElementById('predictions-grid');
        
        if (!predictions || predictions.length === 0) {
            predictionsGrid.innerHTML = '<p class="text-center">No predictions available</p>';
            return;
        }

        predictionsGrid.innerHTML = predictions.map(prediction => {
            const occupancyClass = this.getOccupancyClass(prediction.predicted_occupancy);
            const peakLevel = this.getPeakLevel(prediction.peak_probability);
            
            return `
                <div class="prediction-card">
                    <h3>${prediction.zone_name || `Zone ${prediction.zone_id}`}</h3>
                    <div class="occupancy-rate ${occupancyClass}">
                        ${prediction.predicted_occupancy}%
                    </div>
                    <div class="prediction-details">
                        <p><strong>Peak Level:</strong> ${peakLevel}</p>
                        <p><strong>Confidence:</strong> ${(prediction.confidence * 100).toFixed(0)}%</p>
                        <p><strong>Time:</strong> ${prediction.hour}:00</p>
                    </div>
                    <div class="prediction-actions">
                        <button class="btn btn-sm btn-outline" onclick="predictionsManager.showHourlyPrediction(${prediction.zone_id})">
                            <i class="fas fa-chart-line"></i> Hourly View
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }

    renderPredictionChart(predictions) {
        const ctx = document.getElementById('predictions-chart');
        if (!ctx) return;

        // Destroy existing chart
        if (this.chart) {
            this.chart.destroy();
        }

        const labels = predictions.map(p => p.zone_name || `Zone ${p.zone_id}`);
        const occupancyData = predictions.map(p => p.predicted_occupancy);
        const confidenceData = predictions.map(p => p.confidence * 100);

        this.chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Predicted Occupancy (%)',
                        data: occupancyData,
                        backgroundColor: occupancyData.map(value => this.getOccupancyColor(value)),
                        borderColor: occupancyData.map(value => this.getOccupancyColor(value, 0.8)),
                        borderWidth: 2,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Confidence (%)',
                        data: confidenceData,
                        type: 'line',
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        pointBackgroundColor: '#667eea',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        tension: 0.4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'AI Parking Predictions',
                        font: {
                            size: 16,
                            weight: 'bold'
                        }
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed.y.toFixed(1) + '%';
                                return label;
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Parking Zones'
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Occupancy Rate (%)'
                        },
                        min: 0,
                        max: 100
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Confidence (%)'
                        },
                        min: 0,
                        max: 100,
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });
    }

    async showHourlyPrediction(zoneId) {
        const date = document.getElementById('prediction-date')?.value || new Date().toISOString().split('T')[0];
        
        try {
            const response = await fetch(`../api/ai/predictions.php?type=peak_hours&zone_id=${zoneId}&date=${date}`);
            const result = await response.json();

            if (result.success) {
                this.displayHourlyPredictions(result.peak_analysis);
            } else {
                window.app.showAlert('Failed to load hourly predictions', 'error');
            }
        } catch (error) {
            console.error('Hourly prediction error:', error);
            window.app.showAlert('Failed to load hourly predictions', 'error');
        }
    }

    displayHourlyPredictions(peakAnalysis) {
        // Create modal for hourly predictions
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.id = 'hourly-predictions-modal';
        modal.innerHTML = `
            <div class="modal-content" style="max-width: 800px;">
                <div class="modal-header">
                    <h3>Hourly Predictions - Zone ${peakAnalysis.zone_id}</h3>
                    <span class="close" onclick="document.getElementById('hourly-predictions-modal').remove()">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="hourly-predictions">
                        <div class="prediction-section">
                            <h4><i class="fas fa-arrow-up text-danger"></i> Peak Hours</h4>
                            <div class="hourly-grid">
                                ${peakAnalysis.peak_hours.map(hour => `
                                    <div class="hourly-card peak">
                                        <div class="hour">${hour.hour}:00</div>
                                        <div class="occupancy ${this.getOccupancyClass(hour.predicted_occupancy)}">
                                            ${hour.predicted_occupancy}%
                                        </div>
                                        <div class="confidence">
                                            ${(hour.confidence * 100).toFixed(0)}% confidence
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                        
                        <div class="prediction-section">
                            <h4><i class="fas fa-arrow-down text-success"></i> Off-Peak Hours</h4>
                            <div class="hourly-grid">
                                ${peakAnalysis.off_peak_hours.map(hour => `
                                    <div class="hourly-card off-peak">
                                        <div class="hour">${hour.hour}:00</div>
                                        <div class="occupancy ${this.getOccupancyClass(hour.predicted_occupancy)}">
                                            ${hour.predicted_occupancy}%
                                        </div>
                                        <div class="confidence">
                                            ${(hour.confidence * 100).toFixed(0)}% confidence
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                    
                    <div class="chart-container" style="height: 300px; margin-top: 2rem;">
                        <canvas id="hourly-chart"></canvas>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        modal.style.display = 'block';

        // Render hourly chart
        setTimeout(() => {
            this.renderHourlyChart(peakAnalysis);
        }, 100);
    }

    renderHourlyChart(peakAnalysis) {
        const ctx = document.getElementById('hourly-chart');
        if (!ctx) return;

        const allHours = [...peakAnalysis.peak_hours, ...peakAnalysis.off_peak_hours]
            .sort((a, b) => a.hour - b.hour);

        const labels = allHours.map(h => `${h.hour}:00`);
        const data = allHours.map(h => h.predicted_occupancy);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Predicted Occupancy (%)',
                    data: data,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 3,
                    pointBackgroundColor: data.map(value => this.getOccupancyColor(value)),
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: `Hourly Occupancy Predictions - ${peakAnalysis.date}`,
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    },
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Hour of Day'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Occupancy Rate (%)'
                        },
                        min: 0,
                        max: 100
                    }
                }
            }
        });
    }

    getOccupancyClass(occupancy) {
        if (occupancy >= 85) return 'occupancy-very-high';
        if (occupancy >= 70) return 'occupancy-high';
        if (occupancy >= 50) return 'occupancy-medium';
        return 'occupancy-low';
    }

    getOccupancyColor(occupancy, alpha = 0.7) {
        if (occupancy >= 85) return `rgba(220, 53, 69, ${alpha})`;  // danger
        if (occupancy >= 70) return `rgba(253, 126, 20, ${alpha})`; // warning-orange
        if (occupancy >= 50) return `rgba(255, 193, 7, ${alpha})`;  // warning
        return `rgba(40, 167, 69, ${alpha})`;                       // success
    }

    getPeakLevel(probability) {
        const levels = {
            'very_high': '🔴 Very High',
            'high': '🟠 High',
            'medium': '🟡 Medium',
            'low': '🟢 Low',
            'very_low': '🟢 Very Low'
        };
        return levels[probability] || probability;
    }

    async generatePredictionReport() {
        if (!this.currentPredictions.length) {
            window.app.showAlert('No predictions to export', 'error');
            return;
        }

        const date = document.getElementById('prediction-date')?.value || new Date().toISOString().split('T')[0];
        const hour = document.getElementById('prediction-hour')?.value || new Date().getHours();

        const report = {
            generated_at: new Date().toISOString(),
            prediction_date: date,
            prediction_hour: hour,
            summary: {
                total_zones: this.currentPredictions.length,
                average_occupancy: (this.currentPredictions.reduce((sum, p) => sum + p.predicted_occupancy, 0) / this.currentPredictions.length).toFixed(1),
                high_demand_zones: this.currentPredictions.filter(p => p.predicted_occupancy >= 70).length,
                low_demand_zones: this.currentPredictions.filter(p => p.predicted_occupancy < 50).length
            },
            predictions: this.currentPredictions,
            recommendations: this.generateRecommendations()
        };

        // Create and download JSON report
        const blob = new Blob([JSON.stringify(report, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `parking-predictions-${date}-${hour}h.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        window.app.showAlert('Prediction report downloaded', 'success');
    }

    generateRecommendations() {
        const recommendations = [];

        // Find best times to park
        const lowOccupancyZones = this.currentPredictions
            .filter(p => p.predicted_occupancy < 50)
            .sort((a, b) => a.predicted_occupancy - b.predicted_occupancy);

        if (lowOccupancyZones.length > 0) {
            recommendations.push({
                type: 'best_availability',
                message: `Best availability at ${lowOccupancyZones[0].zone_name || 'Zone ' + lowOccupancyZones[0].zone_id} (${lowOccupancyZones[0].predicted_occupancy}% occupancy)`
            });
        }

        // Warn about high demand zones
        const highDemandZones = this.currentPredictions
            .filter(p => p.predicted_occupancy >= 85)
            .sort((a, b) => b.predicted_occupancy - a.predicted_occupancy);

        if (highDemandZones.length > 0) {
            recommendations.push({
                type: 'high_demand_warning',
                message: `Avoid ${highDemandZones[0].zone_name || 'Zone ' + highDemandZones[0].zone_id} - Very high demand expected (${highDemandZones[0].predicted_occupancy}% occupancy)`
            });
        }

        // General advice
        const avgOccupancy = this.currentPredictions.reduce((sum, p) => sum + p.predicted_occupancy, 0) / this.currentPredictions.length;
        
        if (avgOccupancy > 70) {
            recommendations.push({
                type: 'general_advice',
                message: 'Consider arriving earlier or later to avoid peak demand'
            });
        } else if (avgOccupancy < 40) {
            recommendations.push({
                type: 'general_advice',
                message: 'Good time to find parking - low demand expected across all zones'
            });
        }

        return recommendations;
    }

    // Method to show real-time updates
    startRealTimeUpdates() {
        // In a real implementation, this would connect to a WebSocket or poll the API
        setInterval(async () => {
            if (document.getElementById('predictions').classList.contains('active')) {
                const date = document.getElementById('prediction-date')?.value || new Date().toISOString().split('T')[0];
                const hour = document.getElementById('prediction-hour')?.value || new Date().getHours();
                
                try {
                    const response = await fetch(`../api/ai/predictions.php?type=all_zones&date=${date}&hour=${hour}`);
                    const result = await response.json();
                    
                    if (result.success) {
                        this.displayPredictions(result.predictions);
                    }
                } catch (error) {
                    console.error('Real-time update error:', error);
                }
            }
        }, 300000); // Update every 5 minutes
    }
}

// Initialize predictions manager
document.addEventListener('DOMContentLoaded', () => {
    window.predictionsManager = new PredictionsManager();
    
    // Add export button to predictions section
    const predictionsControls = document.querySelector('.predictions-controls');
    if (predictionsControls) {
        const exportButton = document.createElement('button');
        exportButton.className = 'btn btn-outline';
        exportButton.innerHTML = '<i class="fas fa-download"></i> Export Report';
        exportButton.onclick = () => window.predictionsManager.generatePredictionReport();
        predictionsControls.appendChild(exportButton);
    }
    
    // Start real-time updates
    window.predictionsManager.startRealTimeUpdates();
});

// Additional CSS for hourly predictions
const hourlyStyles = `
<style>
.hourly-predictions {
    margin: 1rem 0;
}

.prediction-section {
    margin-bottom: 2rem;
}

.prediction-section h4 {
    margin-bottom: 1rem;
    color: #333;
}

.hourly-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
}

.hourly-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    text-align: center;
    transition: transform 0.2s;
}

.hourly-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.hourly-card.peak {
    border-left: 4px solid #dc3545;
}

.hourly-card.off-peak {
    border-left: 4px solid #28a745;
}

.hourly-card .hour {
    font-size: 1.1rem;
    font-weight: bold;
    color: #333;
    margin-bottom: 0.5rem;
}

.hourly-card .occupancy {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.hourly-card .confidence {
    font-size: 0.8rem;
    color: #666;
}

.modal-body {
    padding: 2rem;
}

.text-danger { color: #dc3545 !important; }
.text-success { color: #28a745 !important; }
</style>
`;

// Inject hourly styles
document.head.insertAdjacentHTML('beforeend', hourlyStyles);