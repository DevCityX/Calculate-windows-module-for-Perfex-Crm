(function () {
  const q = (s) => document.querySelector(s);
  const qa = (s) => Array.from(document.querySelectorAll(s));

  const controls = {
    tabs: qa('.wc-tab'),
    panels: {
      shape: q('#panel-shape'),
      sections: q('#panel-sections'),
      preview: q('#panel-preview'),
      proposal: q('#panel-proposal'),
    },
    profile: q('#wc-profile'),
    shape: q('#wc-shape'),
    width: q('#wc-width'),
    height: q('#wc-height'),
    cols: q('#wc-cols'),
    rows: q('#wc-rows'),
    qty: q('#wc-qty'),
    calcBtn: q('#wc-calc-btn'),
    svgWrap: q('#wc-svg-wrap'),
    svgWrap2: q('#wc-svg-wrap-2'),
    breakdown: q('#wc-breakdown'),
    sectionsGrid: q('#wc-sections-grid'),
    sashTypes: q('#wc-sash-types'),
    proposalId: q('#wc-proposal-id'),
    title: q('#wc-title'),
    saveBtn: q('#wc-save-btn'),
    saveStatus: q('#wc-save-status'),
    nextBtns: qa('[data-next]'),
    prevBtns: qa('[data-prev]'),
  };

  const SASH_TYPES = [
    { id: 'fix', name: 'Глухе', color: '#e3f2fd' },
    { id: 'left', name: 'Ліва ПВ', color: '#bbdefb' },
    { id: 'right', name: 'Права ПВ', color: '#90caf9' },
    { id: 'top', name: 'Верхнє ПВ', color: '#64b5f6' },
    { id: 'bottom', name: 'Фрамуга', color: '#42a5f5' },
  ];

  const clamp = (v, min, max) => Math.max(min, Math.min(max, v));
  const mmToM = (v) => v / 1000;

  let activeSashType = 'fix';
  let state = { sections: [], profile: null, config: {}, pricing: {}, total: 0, svg: '' };

  function notify(type, msg) {
    if (typeof alert_float === 'function') alert_float(type, msg);
    if (controls.saveStatus) {
      controls.saveStatus.className = type === 'success' ? 'text-success' : 'text-danger';
      controls.saveStatus.textContent = msg;
    }
  }

  function setTab(tab) {
    controls.tabs.forEach((el) => el.classList.toggle('active', el.dataset.tab === tab));
    Object.keys(controls.panels).forEach((k) => {
      controls.panels[k].style.display = k === tab ? '' : 'none';
    });
  }

  function getSelectedProfile() {
    const option = controls.profile && controls.profile.options[controls.profile.selectedIndex];
    if (!option || !option.value) return null;
    let meta = { openingSurcharge: 850, archFactor: 1.35, triangleFactor: 1.4 };
    try { meta = Object.assign(meta, JSON.parse(option.dataset.meta || '{}')); } catch (e) {}
    return { id: parseInt(option.value, 10), rate: parseFloat(option.dataset.rate || '0'), meta };
  }

  function initSections() {
    const cols = clamp(parseInt(controls.cols.value || '1', 10), 1, 5);
    const rows = clamp(parseInt(controls.rows.value || '1', 10), 1, 4);
    const size = cols * rows;
    if (state.sections.length !== size) {
      state.sections = Array(size).fill('fix');
    }
    controls.sectionsGrid.style.gridTemplateColumns = `repeat(${cols}, minmax(56px,1fr))`;
    controls.sectionsGrid.innerHTML = '';
    state.sections.forEach((type, idx) => {
      const t = SASH_TYPES.find((x) => x.id === type) || SASH_TYPES[0];
      const d = document.createElement('div');
      d.className = 'wc-sec';
      d.style.background = t.color;
      d.textContent = `${idx + 1}. ${t.name}`;
      d.onclick = () => {
        state.sections[idx] = activeSashType;
        initSections();
      };
      controls.sectionsGrid.appendChild(d);
    });
  }

  function renderSashTypes() {
    controls.sashTypes.innerHTML = '';
    SASH_TYPES.forEach((t) => {
      const d = document.createElement('div');
      d.className = `wc-sash ${activeSashType === t.id ? 'active' : ''}`;
      d.textContent = t.name;
      d.onclick = () => { activeSashType = t.id; renderSashTypes(); };
      controls.sashTypes.appendChild(d);
    });
  }

  function drawSvg(shape, cols, rows) {
    const vw = 420, vh = 320, pad = 16;
    const rw = vw - pad * 2, rh = vh - pad * 2;
    const cw = rw / cols, ch = rh / rows;
    let outline = `<rect x="${pad}" y="${pad}" width="${rw}" height="${rh}" fill="none" stroke="#111" stroke-width="3"/>`;
    if (shape === 'arch') outline = `<path d="M ${pad} ${vh-pad} L ${pad} ${pad+64} Q ${vw/2} ${pad-42} ${vw-pad} ${pad+64} L ${vw-pad} ${vh-pad} Z" fill="none" stroke="#111" stroke-width="3"/>`;
    if (shape === 'triangle') outline = `<path d="M ${vw/2} ${pad} L ${vw-pad} ${vh-pad} L ${pad} ${vh-pad} Z" fill="none" stroke="#111" stroke-width="3"/>`;
    if (shape === 'trapezoid') outline = `<path d="M ${pad+70} ${pad} L ${vw-pad-70} ${pad} L ${vw-pad} ${vh-pad} L ${pad} ${vh-pad} Z" fill="none" stroke="#111" stroke-width="3"/>`;

    let fill = '';
    for (let r=0; r<rows; r++) {
      for (let c=0; c<cols; c++) {
        const idx = r*cols + c;
        const t = SASH_TYPES.find((x) => x.id === (state.sections[idx] || 'fix')) || SASH_TYPES[0];
        fill += `<rect x="${pad + c*cw + 1}" y="${pad + r*ch + 1}" width="${cw-2}" height="${ch-2}" fill="${t.color}"/>`;
      }
    }

    let grid = '';
    for (let i=1;i<cols;i++) grid += `<line x1="${pad+cw*i}" y1="${pad}" x2="${pad+cw*i}" y2="${vh-pad}" stroke="#6b7280"/>`;
    for (let i=1;i<rows;i++) grid += `<line x1="${pad}" y1="${pad+ch*i}" x2="${vw-pad}" y2="${pad+ch*i}" stroke="#6b7280"/>`;

    return `<svg viewBox="0 0 ${vw} ${vh}" xmlns="http://www.w3.org/2000/svg">${fill}${outline}${grid}</svg>`;
  }

  function recalc() {
    const profile = getSelectedProfile();
    if (!profile) {
      controls.breakdown.innerHTML = '<div class="text-danger">Немає товарів для розрахунку.</div>';
      return;
    }

    const shape = controls.shape.value;
    const width = clamp(parseInt(controls.width.value || '1200', 10), 300, 5000);
    const height = clamp(parseInt(controls.height.value || '1400', 10), 300, 5000);
    const cols = clamp(parseInt(controls.cols.value || '1', 10), 1, 5);
    const rows = clamp(parseInt(controls.rows.value || '1', 10), 1, 4);
    const qty = clamp(parseInt(controls.qty.value || '1', 10), 1, 200);

    initSections();

    let area = mmToM(width) * mmToM(height);
    if (shape === 'triangle') area *= 0.5;
    if (shape === 'arch') area *= 0.85;
    if (shape === 'trapezoid') area *= 0.75;

    const openingCount = state.sections.filter((s) => s !== 'fix').length;
    let factor = 1;
    if (shape === 'arch') factor = profile.meta.archFactor;
    if (shape === 'triangle' || shape === 'trapezoid') factor = profile.meta.triangleFactor;

    const base = area * profile.rate;
    const openingExtra = openingCount * profile.meta.openingSurcharge;
    const single = (base + openingExtra) * factor;
    const total = single * qty;

    const svg = drawSvg(shape, cols, rows);
    controls.svgWrap.innerHTML = svg;
    controls.svgWrap2.innerHTML = svg;
    controls.breakdown.innerHTML = `<div class="window-kpis">
      <div class="kpi"><b>Площа</b><br>${area.toFixed(2)} м²</div>
      <div class="kpi"><b>Відкривань</b><br>${openingCount}</div>
      <div class="kpi"><b>База</b><br>${base.toFixed(2)} грн</div>
      <div class="kpi"><b>Надбавка</b><br>${openingExtra.toFixed(2)} грн</div>
      <div class="kpi"><b>Разом/шт</b><br>${single.toFixed(2)} грн</div>
      <div class="kpi"><b>Разом</b><br>${total.toFixed(2)} грн</div>
    </div>`;

    state.profile = profile;
    state.svg = svg;
    state.total = total;
    state.config = { shape, width, height, cols, rows, qty, sections: state.sections };
    state.pricing = { area, openingCount, base, openingExtra, factor, single, total };
  }

  function saveVisual() {
    if (!state.profile) return notify('danger', 'Спочатку зробіть розрахунок.');
    const proposalId = parseInt(controls.proposalId.value || '0', 10);
    if (proposalId < 1) return notify('danger', 'Вкажіть ID пропозиції.');

    const data = {
      proposal_id: proposalId,
      title: controls.title.value || 'Віконна конструкція',
      profile_item_id: state.profile.id,
      configuration_json: JSON.stringify(state.config),
      pricing_json: JSON.stringify(state.pricing),
      svg_markup: state.svg,
      total: state.total,
    };
    if (window.WINDOW_CALCULATOR_BOOTSTRAP.csrfName) {
      data[window.WINDOW_CALCULATOR_BOOTSTRAP.csrfName] = window.WINDOW_CALCULATOR_BOOTSTRAP.csrfHash;
    }

    controls.saveBtn.disabled = true;
    $.post(window.WINDOW_CALCULATOR_BOOTSTRAP.saveUrl, data)
      .done(function (res) {
        const r = typeof res === 'string' ? JSON.parse(res) : res;
        if (r.csrf_hash) window.WINDOW_CALCULATOR_BOOTSTRAP.csrfHash = r.csrf_hash;
        notify(r && r.success ? 'success' : 'danger', (r && r.message) || 'Помилка збереження.');
      })
      .fail(function () { notify('danger', 'Помилка запиту при збереженні.'); })
      .always(function () { controls.saveBtn.disabled = false; });
  }

  controls.tabs.forEach((t) => t.addEventListener('click', () => setTab(t.dataset.tab)));
  controls.nextBtns.forEach((b) => b.addEventListener('click', () => setTab(b.dataset.next)));
  controls.prevBtns.forEach((b) => b.addEventListener('click', () => setTab(b.dataset.prev)));

  ['change', 'input'].forEach((evt) => {
    [controls.profile, controls.shape, controls.width, controls.height, controls.cols, controls.rows, controls.qty].forEach((el) => {
      if (el) el.addEventListener(evt, recalc);
    });
  });

  controls.calcBtn.addEventListener('click', recalc);
  controls.saveBtn.addEventListener('click', saveVisual);

  renderSashTypes();
  recalc();
})();
