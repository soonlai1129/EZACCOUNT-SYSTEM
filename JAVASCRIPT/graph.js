// ===== Enhanced Graph (keeps your original hooks) =====
// Requires: Chart.js v3+ loaded before this file.
//
// IDs used:
// - Canvas:  #earningsChart
// - Selects: #granularity, #outletFilter (optional), #shiftFilter (optional)
// Backend:   sales_aggregate.php?granularity=... [&outlet_id=] [&shift=]

let earningsChart;

(function initEnhancedGraph(){
  const canvas = document.getElementById('earningsChart');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');

  // Money formatters
  const fmtMoney = (n) => {
    const num = Number(n) || 0;
    return 'RM ' + num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  };
  const fmtTick = (n) => {
    const num = Number(n) || 0;
    if (Math.abs(num) >= 1_000_000) return 'RM ' + (num/1_000_000).toFixed(1).replace(/\.0$/,'') + 'M';
    if (Math.abs(num) >= 1_000)     return 'RM ' + (num/1_000).toFixed(1).replace(/\.0$/,'') + 'k';
    return 'RM ' + num.toLocaleString();
  };

  // Dynamic gradient based on value
  function makeDynamicGradient(ctx, canvas, data) {
    const hasNegative = data.some(value => value < 0);
    const start = hasNegative ? '#F59E0B' : '#10B981';
    const g = ctx.createLinearGradient(0, 0, 0, canvas.height);
    const toRGBA = (hex, a=1) => {
      const v = hex.replace('#',''); const n = parseInt(v,16);
      const r=(n>>16)&255, g=(n>>8)&255, b=n&255; return `rgba(${r},${g},${b},${a})`;
    };
    g.addColorStop(0,   toRGBA(start, .35));
    g.addColorStop(0.6, toRGBA(start, .12));
    g.addColorStop(1,   toRGBA(start, .03));
    return g;
  }

  function buildUrl(){
    const g = (document.getElementById('granularity')?.value || 'month');
    const qs = new URLSearchParams({ granularity: g });

    const outletSel = document.getElementById('outletFilter');
    if (outletSel && outletSel.value) qs.set('outlet_id', outletSel.value);

    const shiftSel = document.getElementById('shiftFilter');
    const shiftVal = shiftSel ? (shiftSel.value || '').trim() : '';
    if (shiftVal === 'Morning' || shiftVal === 'Evening') qs.set('shift', shiftVal);

    return `sales_aggregate.php?${qs.toString()}`;
  }

  // Inject range buttons (client-side slice: All / Short / Long)
  function ensureRangeButtons(){
    const controls = document.querySelector('.graph-controls');
    if (!controls) return;
    if (document.querySelector('.graph-range')) return;

    const range = document.createElement('div');
    range.className = 'graph-range';
    range.innerHTML = `
      <button class="range-btn active" data-range="all">All</button>
      <button class="range-btn" data-range="short">Short</button>
      <button class="range-btn" data-range="long">Long</button>
    `;
    controls.appendChild(range);

    range.addEventListener('click', (e) => {
      const btn = e.target.closest('.range-btn');
      if (!btn) return;
      document.querySelectorAll('.graph-range .range-btn').forEach(b=>b.classList.remove('active'));
      btn.classList.add('active');
      loadChart().catch(console.error);
    });
  }

  function sliceByGranularity(labels, nums, g, key){
    if (key === 'all') return {labels, nums};
    let n;
    if (g === 'day')        n = (key==='short') ? 7  : 30;
    else if (g === 'week')  n = (key==='short') ? 8  : 12;
    else if (g === 'month') n = (key==='short') ? 6  : 12;
    else if (g === 'quarter') n = (key==='short') ? 4 : 8;
    else if (g === 'year')  n = (key==='short') ? 5  : 10;
    else n = 12;
    const start = Math.max(0, labels.length - n);
    return { labels: labels.slice(start), nums: nums.slice(start) };
  }

  async function loadChart(){
    const res = await fetch(buildUrl(), { cache:'no-store' });
    if (!res.ok) throw new Error('Failed to load data');
    const { labels = [], values = [] } = await res.json();
    const nums = (values || []).map(v => Number(v) || 0);

    const g = (document.getElementById('granularity')?.value || 'month');
    const chosen = document.querySelector('.graph-range .range-btn.active')?.dataset.range || 'all';
    const { labels: L, nums: N } = sliceByGranularity(labels, nums, g, chosen);

    if (earningsChart) earningsChart.destroy();

    earningsChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: Array.isArray(L) ? L : [],
        datasets: [{
          label: 'Net Earnings',
          data: N,
          backgroundColor: makeDynamicGradient(ctx, canvas, N),
          fill: true,
          borderWidth: 3,
          tension: 0.35,
          pointRadius: 0,
          pointHoverRadius: 5,
          pointHoverBorderWidth: 2,
          pointHoverBorderColor: '#fff',
          // DYNAMIC SEGMENT COLORING - This is the key fix
          segment: {
            borderColor: (ctx) => {
              if (ctx.p0.parsed.y === null || ctx.p1.parsed.y === null) {
                return '#10B981'; // Default color for null values
              }
              // Use the current point's value to determine color
              const currentValue = ctx.p1.parsed.y;
              return currentValue >= 0 ? '#10B981' : '#F59E0B';
            },
            backgroundColor: (ctx) => {
              if (ctx.p0.parsed.y === null || ctx.p1.parsed.y === null) {
                return 'rgba(16, 185, 129, 0.1)';
              }
              const currentValue = ctx.p1.parsed.y;
              return currentValue >= 0 ? 'rgba(16, 185, 129, 0.1)' : 'rgba(245, 158, 11, 0.1)';
            }
          }
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        animation: { duration: 900, easing: 'easeOutQuart' },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: 'rgba(17,24,39,.95)',
            titleColor: '#fff',
            bodyColor: '#e5e7eb',
            padding: 12,
            displayColors: false,
            callbacks: {
              label: (ctx) => {
                const curr = Number(ctx.parsed.y) || 0;
                const i = ctx.dataIndex;
                const prev = i > 0 ? Number(ctx.dataset.data[i-1]) || 0 : curr;
                const diff = curr - prev;
                const arrow = diff > 0 ? '▲' : diff < 0 ? '▼' : '•';
                return ` ${arrow} ${fmtMoney(curr)}`;
              }
            }
          }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: {
              autoSkip: true,
              maxTicksLimit: 10,
              maxRotation: 48,
              minRotation: 28
            }
          },
          y: {
            grid: { color: 'rgba(0,0,0,.06)' },
            ticks: { callback: fmtTick }
          }
        }
      }
    });
  }

  // Initialize when visible (perf-friendly) and wire filters
  document.addEventListener('DOMContentLoaded', () => {
    ensureRangeButtons();

    ['granularity','outletFilter','shiftFilter'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.addEventListener('change', () => loadChart().catch(console.error));
    });

    const target = document.querySelector('.graph-wrapper') || canvas;
    const io = new IntersectionObserver((entries) => {
      if (entries.some(e => e.isIntersecting)) {
        loadChart().catch(console.error);
        io.disconnect();
      }
    }, { rootMargin: '100px' });
    io.observe(target);
  });

  // Keep chart crisp on resize
  window.addEventListener('resize', () => { if (earningsChart) earningsChart.resize(); });
})();