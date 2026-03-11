# Window Calculator module for Perfex CRM

Цей репозиторій містить модуль `window_calculator` для Perfex CRM, який:

- розраховує вартість віконної конструкції за геометрією, кількістю стулок та кількістю;
- бере профільні системи з товарів Perfex (`tblitems`);
- будує SVG-візуал конструкції;
- зберігає розрахунок і візуал до пропозиції;
- дозволяє виводити результат в шаблоні пропозиції через merge-field `{window_calculator_visual}`.

## Швидке встановлення

1. Скопіювати папку `modules/window_calculator` у ваш Perfex CRM.
2. Увімкнути модуль в адмінці Perfex.
3. Перейти в `Window Calculator` (лівий сайдбар).
4. У шаблоні пропозиції використати `{window_calculator_visual}`.

## Налаштування товарів (профілів)

Модуль бере активні товари з `tblitems`. Поля:

- `description` — назва профілю;
- `rate` — базова ціна за м²;
- `long_description` — optional JSON з коефіцієнтами:

```json
{
  "openingSurcharge": 850,
  "archFactor": 1.35,
  "triangleFactor": 1.4
}
```

Якщо JSON не заданий, застосовуються значення за замовчуванням.
