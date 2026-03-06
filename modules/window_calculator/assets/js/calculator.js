(function () {
  const q = (s) => document.querySelector(s);

  const controls = {
    profile: q('#wc-profile'),
    shape: q('#wc-shape'),
    width: q('#wc-width'),
    height: q('#wc-height'),
    cols: q('#wc-cols'),
    rows: q('#wc-rows'),
    openings: q('#wc-openings'),
    qty: q('#wc-qty'),
    calcBtn: q('#wc-calc-btn'),
    svgWrap: q('#wc-svg-wrap'),
    breakdown: q('#wc-breakdown'),
    proposalId: q('#wc-proposal-id'),
    title: q('#wc-title'),
    saveBtn: q('#wc-save-btn'),
  };

  const clamp = (v, min, max) => Math.max(min, Math.min(max, v));
  const mmToM = (v) => v / 1000;

  let state = {
    area: 0,
    total: 0,
    svg: '',
    pricing: {},
  };

  function getSelectedProfile() {
    const option = controls.profile.options[controls.profile.selectedIndex];
    const rate = parseFloat(option.dataset.rate || '0');
    let meta = { openingSurcharge: 850, archFactor: 1.35, triangleFactor: 1.4 };

    try {
      meta = Object.assign(meta, JSON.parse(option.dataset.meta || '{}'));
    } catch (e) {
      // ignore
    }

    return {
      id: parseInt(option.value, 10),
      name: option.textContent,
      rate,
      meta,
    };
  }

  function areaByShape(shape, w, h) {
    const base = mmToM(w) * mmToM(h);
    if (shape === 'triangle') return base / 2;
    if (shape === 'arch') return base * 0.85;
    if (shape === 'trapezoid') return base * 0.75;
    return base;
  }

  function drawSvg(shape, w, h, cols, rows) {
    const vw = 400;
    const vh = 300;
    const pad = 16;
    const rw = vw - pad * 2;
    const rh = vh - pad * 2;
    const cellW = rw / cols;
    const cellH = rh / rows;

    let outline = `<rect x="${pad}" y="${pad}" width="${rw}" height="${rh}" fill="none" stroke="#111827" stroke-width="3"/>`;

    if (shape === 'arch') {
      outline = `<path d="M ${pad} ${vh - pad} L ${pad} ${pad + 60} Q ${vw / 2} ${pad - 40} ${vw - pad} ${pad + 60} L ${vw - pad} ${vh - pad} Z" fill="none" stroke="#111827" stroke-width="3"/>`;
    } else if (shape === 'triangle') {
      outline = `<path d="M ${vw / 2} ${pad} L ${vw - pad} ${vh - pad} L ${pad} ${vh - pad} Z" fill="none" stroke="#111827" stroke-width="3"/>`;
    } else if (shape === 'trapezoid') {
      outline = `<path d="M ${pad + 60} ${pad} L ${vw - pad - 60} ${pad} L ${vw - pad} ${vh - pad} L ${pad} ${vh - pad} Z" fill="none" stroke="#111827" stroke-width="3"/>`;
    }

    let grids = '';
    for (let i = 1; i < cols; i++) {
      grids += `<line x1="${pad + cellW * i}" y1="${pad}" x2="${pad + cellW * i}" y2="${vh - pad}" stroke="#9ca3af"/>`;
    }
    for (let i = 1; i < rows; i++) {
      grids += `<line x1="${pad}" y1="${pad + cellH * i}" x2="${vw - pad}" y2="${pad + cellH * i}" stroke="#9ca3af"/>`;
    }

    return `<svg viewBox="0 0 ${vw} ${vh}" xmlns="http://www.w3.org/2000/svg">${outline}${grids}</svg>`;
  }

  function recalc() {
    const profile = getSelectedProfile();
    const shape = controls.shape.value;
    const width = clamp(parseInt(controls.width.value || '1200', 10), 300, 5000);
    const height = clamp(parseInt(controls.height.value || '1400', 10), 300, 5000);
    const cols = clamp(parseInt(controls.cols.value || '1', 10), 1, 5);
    const rows = clamp(parseInt(controls.rows.value || '1', 10), 1, 4);
    const openings = clamp(parseInt(controls.openings.value || '0', 10), 0, 20);
    const qty = clamp(parseInt(controls.qty.value || '1', 10), 1, 200);

    const area = areaByShape(shape, width, height);
    let shapeFactor = 1;
    if (shape === 'arch') shapeFactor = profile.meta.archFactor;
    if (shape === 'triangle' || shape === 'trapezoid') shapeFactor = profile.meta.triangleFactor;

    const base = area * profile.rate;
    const openingsExtra = openings * profile.meta.openingSurcharge;
    const single = (base + openingsExtra) * shapeFactor;
    const total = single * qty;
    const svg = drawSvg(shape, width, height, cols, rows);

    state = {
      area,
      total,
      svg,
      pricing: { base, openingsExtra, shapeFactor, single, qty },
      config: { shape, width, height, cols, rows, openings, qty, profile: profile.id },
      profile,
    };

    controls.svgWrap.innerHTML = svg;
    controls.breakdown.innerHTML = `
      <div class="window-kpis">
        <div class="kpi"><b>Площа</b><br>${area.toFixed(2)} м²</div>
        <div class="kpi"><b>База</b><br>${base.toFixed(2)} грн</div>
        <div class="kpi"><b>Надбавка за стулки</b><br>${openingsExtra.toFixed(2)} грн</div>
        <div class="kpi"><b>Коеф. форми</b><br>${shapeFactor.toFixed(2)}</div>
        <div class="kpi"><b>Разом / шт</b><br>${single.toFixed(2)} грн</div>
        <div class="kpi"><b>Разом</b><br>${total.toFixed(2)} грн</div>
      </div>
    `;
  }

  async function saveVisual() {
    const proposalId = parseInt(controls.proposalId.value || '0', 10);
    if (proposalId < 1) {
      alert('Вкажіть ID пропозиції');
      return;
    }

    const payload = new URLSearchParams();
    payload.set('proposal_id', String(proposalId));
    payload.set('title', controls.title.value || 'Віконна конструкція');
    payload.set('profile_item_id', String(state.profile.id));
    payload.set('configuration_json', JSON.stringify(state.config));
    payload.set('pricing_json', JSON.stringify(state.pricing));
    payload.set('svg_markup', state.svg);
    payload.set('total', String(state.total));

    const response = await fetch(window.WINDOW_CALCULATOR_BOOTSTRAP.saveUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: payload.toString(),
      credentials: 'same-origin',
    });

    const result = await response.json();
    if (result.success) {
      alert('Візуал і розрахунок збережені в пропозицію.');
      return;
    }

    alert('Помилка збереження.');
  }

  controls.calcBtn.addEventListener('click', recalc);
  controls.saveBtn.addEventListener('click', saveVisual);

  recalc();
})();
