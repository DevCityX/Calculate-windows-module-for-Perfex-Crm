<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<link rel="stylesheet" href="<?php echo module_dir_url('window_calculator', 'assets/css/calculator.css'); ?>">
<div id="wrapper">
  <div class="content">
    <div class="window-app">
      <div class="wc-topbar">
        <div class="wc-title">Калькулятор вікон (Win-Win style)</div>
        <div class="wc-sub">Форма → Секції → Перегляд → Пропозиція</div>
      </div>

      <div class="wc-card">
        <div class="wc-tabs">
          <button class="wc-tab active" data-tab="shape" type="button">1. Форма</button>
          <button class="wc-tab" data-tab="sections" type="button">2. Секції</button>
          <button class="wc-tab" data-tab="preview" type="button">3. Перегляд</button>
          <button class="wc-tab" data-tab="proposal" type="button">4. Пропозиція</button>
        </div>

        <div class="wc-body">
          <div class="wc-tab-panel" id="panel-shape">
            <div class="wc-grid">
              <div class="wc-panel">
                <div class="wc-panel-head">Параметри конструкції</div>
                <div class="wc-panel-body">
                  <div class="wc-field">
                    <label>Профільна система (товар)</label>
                    <select id="wc-profile" class="form-control">
                      <?php if (empty($profiles)) { ?>
                        <option value="">Немає доступних товарів у таблиці items</option>
                      <?php } else { ?>
                        <?php foreach ($profiles as $profile) { ?>
                          <option value="<?php echo (int) $profile['id']; ?>" data-rate="<?php echo (float) $profile['rate']; ?>" data-meta='<?php echo json_encode($profile['meta']); ?>'>
                            <?php echo html_escape($profile['name']); ?> — <?php echo app_format_money((float) $profile['rate'], get_base_currency()->name); ?>/м²
                          </option>
                        <?php } ?>
                      <?php } ?>
                    </select>
                  </div>

                  <div class="wc-field-grid">
                    <div class="wc-field">
                      <label>Форма</label>
                      <select id="wc-shape" class="form-control">
                        <option value="rect">Прямокутник</option>
                        <option value="arch">Арка</option>
                        <option value="triangle">Трикутник</option>
                        <option value="trapezoid">Трапеція</option>
                      </select>
                    </div>
                    <div class="wc-field"><label>Ширина (мм)</label><input type="number" class="form-control" id="wc-width" value="1200" min="300" max="5000"></div>
                    <div class="wc-field"><label>Висота (мм)</label><input type="number" class="form-control" id="wc-height" value="1400" min="300" max="5000"></div>
                    <div class="wc-field"><label>К-ть колонок</label><input type="number" class="form-control" id="wc-cols" value="2" min="1" max="5"></div>
                    <div class="wc-field"><label>К-ть рядів</label><input type="number" class="form-control" id="wc-rows" value="1" min="1" max="4"></div>
                    <div class="wc-field"><label>Кількість</label><input type="number" class="form-control" id="wc-qty" value="1" min="1" max="200"></div>
                  </div>
                </div>
              </div>
              <div class="wc-panel">
                <div class="wc-panel-head">Швидкий перегляд форми</div>
                <div class="wc-panel-body"><div id="wc-svg-wrap" class="wc-svg-wrap"></div></div>
              </div>
            </div>
            <div class="wc-actions"><button class="btn btn-primary" type="button" data-next="sections">Далі: Секції →</button></div>
          </div>

          <div class="wc-tab-panel" id="panel-sections" style="display:none">
            <div class="wc-grid">
              <div class="wc-panel">
                <div class="wc-panel-head">Тип секції</div>
                <div class="wc-panel-body">
                  <div class="wc-sash-types" id="wc-sash-types"></div>
                  <div class="text-muted mtop10">Оберіть тип і клацайте по секціях.</div>
                </div>
              </div>
              <div class="wc-panel">
                <div class="wc-panel-head">Секції конструкції</div>
                <div class="wc-panel-body"><div id="wc-sections-grid" class="wc-sections-grid"></div></div>
              </div>
            </div>
            <div class="wc-actions split"><button class="btn" type="button" data-prev="shape">← Форма</button><button class="btn btn-primary" type="button" data-next="preview">Далі: Перегляд →</button></div>
          </div>

          <div class="wc-tab-panel" id="panel-preview" style="display:none">
            <div class="wc-grid">
              <div class="wc-panel"><div class="wc-panel-head">Конструкція</div><div class="wc-panel-body"><div id="wc-svg-wrap-2" class="wc-svg-wrap"></div></div></div>
              <div class="wc-panel"><div class="wc-panel-head">Вартість</div><div class="wc-panel-body"><div id="wc-breakdown"></div></div></div>
            </div>
            <div class="wc-actions split"><button class="btn" type="button" data-prev="sections">← Секції</button><button class="btn btn-primary" id="wc-calc-btn" type="button">Перерахувати</button><button class="btn btn-primary" type="button" data-next="proposal">Далі: Пропозиція →</button></div>
          </div>

          <div class="wc-tab-panel" id="panel-proposal" style="display:none">
            <div class="wc-panel">
              <div class="wc-panel-head">Збереження в пропозицію</div>
              <div class="wc-panel-body">
                <div class="wc-field-grid">
                  <div class="wc-field"><label>ID пропозиції</label><input type="number" class="form-control" id="wc-proposal-id" placeholder="Напр. 25"></div>
                  <div class="wc-field"><label>Назва візуалу</label><input type="text" class="form-control" id="wc-title" value="Віконна конструкція"></div>
                </div>
                <button class="btn btn-success mtop15" id="wc-save-btn" type="button">Зберегти візуал у пропозицію</button>
                <p class="text-muted mtop10">Merge-field: <code>{window_calculator_visual}</code></p>
                <div id="wc-save-status"></div>
              </div>
            </div>
            <div class="wc-actions"><button class="btn" type="button" data-prev="preview">← Перегляд</button></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
window.WINDOW_CALCULATOR_BOOTSTRAP = {
  saveUrl: '<?php echo admin_url('window_calculator/save_visual'); ?>',
  csrfName: '<?php echo $this->security->get_csrf_token_name(); ?>',
  csrfHash: '<?php echo $this->security->get_csrf_hash(); ?>'
};
</script>
<script src="<?php echo module_dir_url('window_calculator', 'assets/js/calculator.js'); ?>"></script>
<?php init_tail(); ?>
