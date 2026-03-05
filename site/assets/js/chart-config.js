'use strict';

/**
 * SILL SA — Chart.js Configuration
 * Swiss Design charts: clean, precise, minimal.
 *
 * Requires Chart.js 4.x loaded from CDN before this script.
 *
 * Usage (declarative):
 *   <canvas id="myChart"
 *           data-chart="portfolio"
 *           data-chart-config='{"labels":["A","B"],"values":[30,70]}'>
 *   </canvas>
 *
 * Usage (programmatic):
 *   SILLCharts.renderPortfolioChart('myChart', { values: [47,52,32,0,0,24] });
 */

var SILLCharts = (function () {

  // ---------------------------------------------------------------
  // Brand colours
  // ---------------------------------------------------------------
  var COLORS = {
    red:     '#FF0000',
    redDark: '#CC0000',
    red80:   'rgba(255, 0, 0, 0.8)',
    red60:   'rgba(255, 0, 0, 0.6)',
    red40:   'rgba(255, 0, 0, 0.4)',
    red20:   'rgba(255, 0, 0, 0.2)',
    red10:   'rgba(255, 0, 0, 0.1)',
    dark:    '#1A1A1A',
    body:    '#333333',
    muted:   '#999999',
    border:  '#E0E0E0',
    warmBg:  '#FAFAF8',
    white:   '#FFFFFF'
  };

  // ---------------------------------------------------------------
  // Typography tokens
  // ---------------------------------------------------------------
  var FONT_HEADING = "'Inter', sans-serif";
  var FONT_BODY    = "'Lato', sans-serif";

  // ---------------------------------------------------------------
  // Swiss-locale number formatter (1 234.5)
  // ---------------------------------------------------------------
  var numberFmt = (typeof Intl !== 'undefined')
    ? new Intl.NumberFormat('fr-CH')
    : { format: function (v) { return String(v); } };

  // ---------------------------------------------------------------
  // Internal registry — allows safe destroy / re-render
  // ---------------------------------------------------------------
  var _instances = {};

  function destroyIfExists(canvasId) {
    if (_instances[canvasId]) {
      _instances[canvasId].destroy();
      delete _instances[canvasId];
    }
  }

  // ---------------------------------------------------------------
  // Shared default options (Swiss Design: minimal, precise)
  // ---------------------------------------------------------------
  var defaultPlugins = {
    legend: {
      position: 'bottom',
      labels: {
        font: { family: FONT_HEADING, size: 12, weight: 500 },
        color: COLORS.body,
        padding: 16,
        usePointStyle: true,
        pointStyleWidth: 10
      }
    },
    tooltip: {
      backgroundColor: COLORS.dark,
      titleFont:  { family: FONT_HEADING, size: 13, weight: 600 },
      bodyFont:   { family: FONT_BODY, size: 12 },
      padding: 12,
      cornerRadius: 2,
      displayColors: true,
      callbacks: {
        label: function (ctx) {
          var label = ctx.dataset.label || ctx.label || '';
          var value = ctx.parsed.y !== undefined ? ctx.parsed.y : ctx.parsed;
          if (typeof value === 'number') {
            value = numberFmt.format(value);
          }
          return label ? label + ' : ' + value : value;
        }
      }
    }
  };

  var defaultOptions = {
    responsive: true,
    maintainAspectRatio: true,
    plugins: defaultPlugins
  };

  // ---------------------------------------------------------------
  // Helper: build a chart title plugin config
  // ---------------------------------------------------------------
  function titleConfig(text) {
    if (!text) return { display: false };
    return {
      display: true,
      text: text,
      font: { family: FONT_HEADING, size: 16, weight: 600 },
      color: COLORS.dark,
      padding: { top: 0, bottom: 24 },
      align: 'start'
    };
  }

  // ---------------------------------------------------------------
  // 1. Portfolio breakdown — Doughnut chart
  // ---------------------------------------------------------------
  function renderPortfolioChart(canvasId, data) {
    var ctx = document.getElementById(canvasId);
    if (!ctx) return null;
    data = data || {};

    destroyIfExists(canvasId);

    var chart = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: data.labels || [
          'Subventionné',
          'Contrôlé',
          'Libre',
          'Mixte',
          'PPE',
          'Étudiant'
        ],
        datasets: [{
          data: data.values || [47, 52, 32, 0, 0, 24],
          backgroundColor: [
            COLORS.red,
            COLORS.red80,
            COLORS.red60,
            COLORS.red40,
            COLORS.muted,
            COLORS.body
          ],
          borderWidth: 2,
          borderColor: COLORS.white,
          hoverBorderColor: COLORS.white,
          hoverOffset: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        cutout: '58%',
        plugins: {
          legend: defaultPlugins.legend,
          tooltip: {
            backgroundColor: COLORS.dark,
            titleFont:  defaultPlugins.tooltip.titleFont,
            bodyFont:   defaultPlugins.tooltip.bodyFont,
            padding:    defaultPlugins.tooltip.padding,
            cornerRadius: defaultPlugins.tooltip.cornerRadius,
            displayColors: true,
            callbacks: {
              label: function (ctx) {
                var label = ctx.label || '';
                var value = ctx.parsed;
                var total = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                var pct   = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                return label + ' : ' + numberFmt.format(value) + ' (' + pct + ' %)';
              }
            }
          },
          title: titleConfig(data.title || 'Répartition du portefeuille')
        }
      }
    });

    _instances[canvasId] = chart;
    return chart;
  }

  // ---------------------------------------------------------------
  // 2. KPI evolution — Line chart
  // ---------------------------------------------------------------
  function renderLineChart(canvasId, data) {
    var ctx = document.getElementById(canvasId);
    if (!ctx) return null;
    data = data || {};

    destroyIfExists(canvasId);

    var datasets = data.datasets || [{
      label:  data.label || 'Valeur',
      data:   data.values || [],
      borderColor:      COLORS.red,
      backgroundColor:  COLORS.red10,
      borderWidth:      2,
      pointBackgroundColor: COLORS.red,
      pointBorderColor:     COLORS.white,
      pointBorderWidth:     2,
      pointRadius:      4,
      pointHoverRadius: 6,
      tension: 0.3,
      fill: true
    }];

    var chart = new Chart(ctx, {
      type: 'line',
      data: {
        labels:   data.labels || [],
        datasets: datasets
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        interaction: {
          mode: 'index',
          intersect: false
        },
        scales: {
          x: {
            border: { display: false },
            grid:   { display: false },
            ticks: {
              font:  { family: FONT_BODY, size: 11 },
              color: COLORS.muted
            }
          },
          y: {
            border: { display: false },
            grid: {
              color: COLORS.border,
              lineWidth: 1
            },
            ticks: {
              font:  { family: FONT_BODY, size: 11 },
              color: COLORS.muted,
              callback: function (value) {
                return numberFmt.format(value);
              }
            },
            beginAtZero: data.beginAtZero !== undefined ? data.beginAtZero : false
          }
        },
        plugins: {
          legend:  defaultPlugins.legend,
          tooltip: defaultPlugins.tooltip,
          title:   titleConfig(data.title)
        }
      }
    });

    _instances[canvasId] = chart;
    return chart;
  }

  // ---------------------------------------------------------------
  // 3. Energy labels / generic — Bar chart
  // ---------------------------------------------------------------
  function renderBarChart(canvasId, data) {
    var ctx = document.getElementById(canvasId);
    if (!ctx) return null;
    data = data || {};

    destroyIfExists(canvasId);

    var horizontal = !!data.horizontal;

    var chart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: data.labels || [],
        datasets: [{
          label: data.label || '',
          data:  data.values || [],
          backgroundColor:      data.colors || COLORS.red60,
          hoverBackgroundColor:  COLORS.red,
          borderRadius: 2,
          barThickness: data.barThickness || 24,
          maxBarThickness: 40
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        indexAxis: horizontal ? 'y' : 'x',
        scales: {
          x: {
            border: { display: false },
            grid: {
              display: horizontal,
              color: COLORS.border,
              lineWidth: 1
            },
            ticks: {
              font:  { family: FONT_BODY, size: 11 },
              color: COLORS.muted,
              callback: function (value) {
                if (typeof value === 'number') return numberFmt.format(value);
                return value;
              }
            }
          },
          y: {
            border: { display: false },
            grid: {
              display: !horizontal,
              color: COLORS.border,
              lineWidth: 1
            },
            ticks: {
              font:  { family: FONT_BODY, size: 11 },
              color: COLORS.muted,
              callback: function (value) {
                if (typeof value === 'number') return numberFmt.format(value);
                return value;
              }
            }
          }
        },
        plugins: {
          legend: { display: false },
          tooltip: defaultPlugins.tooltip,
          title:   titleConfig(data.title)
        }
      }
    });

    _instances[canvasId] = chart;
    return chart;
  }

  // ---------------------------------------------------------------
  // Destroy a chart by canvas ID
  // ---------------------------------------------------------------
  function destroy(canvasId) {
    destroyIfExists(canvasId);
  }

  // ---------------------------------------------------------------
  // Auto-initialize from data-chart attributes
  // ---------------------------------------------------------------
  function autoInit() {
    var canvases = document.querySelectorAll('[data-chart]');
    if (!canvases.length) return;

    canvases.forEach(function (canvas) {
      // Ensure the canvas has an ID (generate one if missing)
      if (!canvas.id) {
        canvas.id = 'sill-chart-' + Math.random().toString(36).substr(2, 8);
      }

      var type   = canvas.dataset.chart;
      var config = {};

      try {
        config = JSON.parse(canvas.dataset.chartConfig || '{}');
      } catch (e) {
        console.warn('[SILLCharts] Invalid JSON in data-chart-config on #' + canvas.id);
        return;
      }

      switch (type) {
        case 'portfolio':
        case 'doughnut':
          renderPortfolioChart(canvas.id, config);
          break;
        case 'line':
          renderLineChart(canvas.id, config);
          break;
        case 'bar':
          renderBarChart(canvas.id, config);
          break;
        default:
          console.warn('[SILLCharts] Unknown chart type "' + type + '" on #' + canvas.id);
      }
    });
  }

  // Run auto-init when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', autoInit);
  } else {
    autoInit();
  }

  // ---------------------------------------------------------------
  // Public API
  // ---------------------------------------------------------------
  return {
    COLORS:                COLORS,
    renderPortfolioChart:  renderPortfolioChart,
    renderLineChart:       renderLineChart,
    renderBarChart:        renderBarChart,
    destroy:               destroy,
    autoInit:              autoInit
  };

})();
