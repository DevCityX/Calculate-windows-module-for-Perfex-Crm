<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Window Calculator
Description: Calculator for window constructions with proposal visualization.
Version: 1.0.0
Requires at least: 2.3.*
*/

define('WINDOW_CALCULATOR_MODULE_NAME', 'window_calculator');

hooks()->add_action('admin_init', 'window_calculator_module_init_menu');
hooks()->add_filter('proposal_merge_fields', 'window_calculator_register_merge_fields');
hooks()->add_filter('merge_field_content', 'window_calculator_render_merge_field_content');

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
    ];

    return $fields;
}

function window_calculator_render_merge_field_content($content)
{
    if (!is_array($content) || !isset($content['key']) || $content['key'] !== '{window_calculator_visual}') {
        return $content;
    }

    $proposalId = isset($content['rel_id']) ? (int) $content['rel_id'] : 0;
    if ($proposalId < 1) {
        $content['content'] = '';

        return $content;
    }

    $CI = &get_instance();
    $CI->load->model('window_calculator/window_calculator_model');
    $layout = $CI->window_calculator_model->get_by_proposal($proposalId);

    $content['content'] = '';
    if ($layout) {
        $title = html_escape($layout['title']);
        $total = app_format_money((float) $layout['total'], get_base_currency()->name);
        $svg = (string) $layout['svg_markup'];
        $content['content'] = '<div class="window-calc-proposal"><h4 style="margin-bottom:8px;">' . $title . '</h4>'
            . '<div style="margin-bottom:8px;">' . $svg . '</div>'
            . '<p style="margin:0;"><strong>Сума:</strong> ' . $total . '</p></div>';
    }

    return $content;
}
