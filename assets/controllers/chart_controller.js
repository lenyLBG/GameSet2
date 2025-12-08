import { Controller } from '@hotwired/stimulus';
import { Chart } from 'chart.js/auto';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ["canvas"];
    static values = {
        url: String,
    }

    connect() {
        if (!this.hasCanvasTarget) {
            console.error('Chart controller requires a canvas target.');
            return;
        }
        this.fetchAndRender();
    }

    async fetchAndRender() {
        try {
            const response = await fetch(this.urlValue);
            if (!response.ok) {
                throw new Error(`Failed to fetch chart data: ${response.statusText}`);
            }
            const chartData = await response.json();
            this.renderChart(chartData);
        } catch (error) {
            console.error("Error fetching or rendering chart:", error);
        }
    }

    renderChart(chartData) {
        const labels = Object.keys(chartData);
        const data = Object.values(chartData).map(v => Number(v || 0));

        // read primary color from CSS variables to keep visual consistency
        const rootStyle = getComputedStyle(document.documentElement);
        const primary = (rootStyle.getPropertyValue('--gs-primary') || rootStyle.getPropertyValue('--primary') || '#0b3d91').trim();

        function hexToRgba(hex, alpha) {
            const h = hex.replace('#', '');
            const bigint = parseInt(h, 16);
            const r = (bigint >> 16) & 255;
            const g = (bigint >> 8) & 255;
            const b = bigint & 255;
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        }

        const ctx = this.canvasTarget.getContext('2d');

        // create a vertical gradient fill
        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, hexToRgba(primary, 0.18));
        gradient.addColorStop(1, hexToRgba(primary, 0.02));

        const maxVal = Math.max(...data, 1);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Tournois créés',
                    data: data,
                    borderColor: primary,
                    backgroundColor: gradient,
                    tension: 0.36,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: primary,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, position: 'top' },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: getComputedStyle(document.documentElement).getPropertyValue('--gs-text') || '#333' }
                    },
                    y: {
                        beginAtZero: true,
                        suggestedMax: Math.ceil(maxVal + Math.max(1, maxVal * 0.2)),
                        ticks: { stepSize: 1, color: getComputedStyle(document.documentElement).getPropertyValue('--gs-text') || '#333' },
                        grid: { color: 'rgba(0,0,0,0.04)' }
                    }
                },
                interaction: { mode: 'nearest', axis: 'x', intersect: false }
            }
        });
    }
}
