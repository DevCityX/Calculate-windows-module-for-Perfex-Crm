<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

if (!$CI->db->table_exists(db_prefix() . 'window_calculator_layouts')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "window_calculator_layouts` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `proposal_id` INT UNSIGNED NOT NULL,
        `title` VARCHAR(191) NOT NULL DEFAULT '',
        `profile_item_id` INT UNSIGNED NOT NULL,
        `configuration_json` LONGTEXT NULL,
        `svg_markup` LONGTEXT NULL,
        `pricing_json` LONGTEXT NULL,
        `total` DECIMAL(15,2) NOT NULL DEFAULT 0,
        `datecreated` DATETIME NOT NULL,
        `addedfrom` INT UNSIGNED NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `proposal_id_unique` (`proposal_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}
