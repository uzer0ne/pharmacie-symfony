// assets/controllers/sales_chart_controller.js
import { Controller } from '@hotwired/stimulus';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

/**
 * Stimulus Controller : Sales Chart
 * Gère le graphique d'évolution des ventes sur 7 jours.
 * Les données sont passées via data-values du HTML (aucun CDN externe).
 */
export default class extends Controller {
    static values = {
        labels: Array,
        data:   Array,
    };

    connect() {
        const canvas = this.element;
        const ctx = canvas.getContext('2d');

        // Dégradé vert émeraude
        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(16, 185, 129, 0.25)');
        gradient.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

        this.chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: this.labelsValue,
                datasets: [{
                    label: 'Chiffre d\'affaires (€)',
                    data: this.dataValue,
                    borderColor: '#10b981',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#10b981',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.4,
                }],
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 12,
                        titleFont: { size: 13, family: "'Inter', sans-serif" },
                        bodyFont: { size: 14, family: "'Inter', sans-serif", weight: 'bold' },
                        callbacks: {
                            label: (ctx) => ctx.parsed.y.toFixed(2) + ' €',
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { borderDash: [5, 5], color: '#f1f5f9' },
                        ticks: {
                            font: { family: "'Inter', sans-serif" },
                            color: '#64748b',
                            callback: (v) => v + ' €',
                        },
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { family: "'Inter', sans-serif" },
                            color: '#64748b',
                        },
                    },
                },
            },
        });
    }

    disconnect() {
        // Nettoyage propre pour éviter les fuites mémoire avec Turbo
        if (this.chart) {
            this.chart.destroy();
            this.chart = null;
        }
    }
}
