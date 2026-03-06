<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<link rel="stylesheet" href="<?php echo module_dir_url('window_calculator', 'assets/css/calculator.css'); ?>">
<div id="wrapper">
    <div class="content">
        <div class="panel_s">
            <div class="panel-body window-calc-app">
                <h4 class="no-margin">Калькулятор вікон для пропозицій</h4>
                <p class="text-muted mtop5">Профільні системи беруться з товарів Perfex CRM (таблиця items).</p>

                <div class="window-calc-grid mtop20">
                    <div>
                        <label>Профільна система (товар)</label>
                        <select id="wc-profile" class="form-control">
                            <?php foreach ($profiles as $profile) { ?>
                                <option value="<?php echo (int) $profile['id']; ?>" data-rate="<?php echo (float) $profile['rate']; ?>" data-meta='<?php echo json_encode($profile['meta']); ?>'>
                                    <?php echo html_escape($profile['name']); ?> — <?php echo app_format_money((float) $profile['rate'], get_base_currency()->name); ?>/м²
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div>
                        <label>Форма</label>
                        <select id="wc-shape" class="form-control">
                            <option value="rect">Прямокутник</option>
                            <option value="arch">Арка</option>
                            <option value="triangle">Трикутник</option>
                            <option value="trapezoid">Трапеція</option>
                        </select>
                    </div>
                    <div>
                        <label>Ширина (мм)</label>
                        <input type="number" class="form-control" id="wc-width" value="1200" min="300" max="5000">
                    </div>
                    <div>
                        <label>Висота (мм)</label>
                        <input type="number" class="form-control" id="wc-height" value="1400" min="300" max="5000">
                    </div>
                    <div>
                        <label>К-ть колонок</label>
                        <input type="number" class="form-control" id="wc-cols" value="2" min="1" max="5">
                    </div>
                    <div>
                        <label>К-ть рядів</label>
                        <input type="number" class="form-control" id="wc-rows" value="1" min="1" max="4">
                    </div>
                    <div>
                        <label>Відкривань (стулок)</label>
                        <input type="number" class="form-control" id="wc-openings" value="1" min="0" max="20">
                    </div>
                    <div>
                        <label>Кількість конструкцій</label>
                        <input type="number" class="form-control" id="wc-qty" value="1" min="1" max="200">
                    </div>
                </div>

                <div class="window-calc-actions mtop20">
                    <button class="btn btn-primary" id="wc-calc-btn">Перерахувати</button>
                </div>

                <div class="window-calc-preview mtop20">
                    <div id="wc-svg-wrap"></div>
                    <div id="wc-breakdown" class="mtop15"></div>
                </div>

                <hr>

                <div class="window-calc-grid">
                    <div>
                        <label>ID пропозиції</label>
                        <input type="number" class="form-control" id="wc-proposal-id" placeholder="Напр. 25">
                    </div>
                    <div>
                        <label>Назва візуалу</label>
                        <input type="text" class="form-control" id="wc-title" value="Віконна конструкція">
                    </div>
                </div>
                <button class="btn btn-success mtop15" id="wc-save-btn">Зберегти візуал у пропозицію</button>
                <p class="text-muted mtop10">Потім у шаблоні пропозиції використайте merge-field: <code>{window_calculator_visual}</code>.</p>
            </div>
        </div>
    </div>
</div>

<script>
    window.WINDOW_CALCULATOR_BOOTSTRAP = {
        saveUrl: '<?php echo admin_url('window_calculator/save_visual'); ?>'
    };
</script>
<script src="<?php echo module_dir_url('window_calculator', 'assets/js/calculator.js'); ?>"></script>
<?php init_tail(); ?>
