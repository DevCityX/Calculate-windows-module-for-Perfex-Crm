<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Window Calculator
Description: Calculator for window constructions with proposal visualization.
Version: 1.0.1
Requires at least: 2.3.*
*/

define('WINDOW_CALCULATOR_MODULE_NAME', 'window_calculator');

hooks()->add_action('admin_init', 'window_calculator_module_init_menu');
hooks()->add_filter('proposal_merge_fields', 'window_calculator_register_merge_fields');
hooks()->add_filter('merge_field_content', 'window_calculator_render_merge_field_content', 10, 3);

register_activation_hook(WINDOW_CALCULATOR_MODULE_NAME, 'window_calculator_module_activation_hook');

function window_calculator_module_activation_hook()
{
    require_once __DIR__ . '/install.php';
}

function window_calculator_module_init_menu()
{
    $CI = &get_instance();

    if (has_permission('proposals', '', 'view')) {
        $CI->app_menu->add_sidebar_menu_item('window-calculator', [
            'name'     => 'Window Calculator',
            'icon'     => 'fa fa-window-maximize',
            'href'     => admin_url('window_calculator'),
            'position' => 36,
        ]);
    }
}

function window_calculator_register_merge_fields($fields)
{
    $fields[] = [
        'name'      => 'Window visual (SVG)',
        'key'       => '{window_calculator_visual}',
        'available' => ['proposals'],
        'value'     => '',
    ];

    return $fields;
}

function window_calculator_render_merge_field_content($value, $mergeField = [], $relation = [])
{
    $normalize = function ($raw) {
        if (is_string($raw)) {
            return $raw;
        }

        if (is_scalar($raw)) {
            return (string) $raw;
        }

        if (is_array($raw)) {
            if (isset($raw['value']) && (is_scalar($raw['value']) || $raw['value'] === null)) {
                return (string) $raw['value'];
            }

            if (isset($raw['content']) && (is_scalar($raw['content']) || $raw['content'] === null)) {
                return (string) $raw['content'];
            }
        }

        return '';
    };

    $extract = function ($key, $default = '') use ($value, $mergeField, $relation) {
        foreach ([$mergeField, $relation, $value] as $ctx) {
            if (is_array($ctx) && array_key_exists($key, $ctx)) {
                return $ctx[$key];
            }
        }

        return $default;
    };

    $fieldKey = (string) $extract('key', '');
    if ($fieldKey !== '{window_calculator_visual}') {
        return $normalize($value);
    }

    $proposalId = 0;
    foreach (['rel_id', 'proposal_id', 'id'] as $idKey) {
        $idValue = $extract($idKey, null);
        if ($idValue !== null && $idValue !== '') {
            $proposalId = (int) $idValue;
            if ($proposalId > 0) {
                break;
            }
        }
    }

    if ($proposalId < 1) {
        return '';
    }

    $CI = &get_instance();
    $CI->load->model('window_calculator/window_calculator_model');
    $layout = $CI->window_calculator_model->get_by_proposal($proposalId);

    if (!$layout) {
        return '';
    }

    $title = html_escape((string) $layout['title']);
    $total = app_format_money((float) $layout['total'], get_base_currency()->name);
    $svg = (string) $layout['svg_markup'];

    return '<div class="window-calc-proposal"><h4 style="margin-bottom:8px;">' . $title . '</h4>'
        . '<div style="margin-bottom:8px;">' . $svg . '</div>'
        . '<p style="margin:0;"><strong>Сума:</strong> ' . $total . '</p></div>';
}

