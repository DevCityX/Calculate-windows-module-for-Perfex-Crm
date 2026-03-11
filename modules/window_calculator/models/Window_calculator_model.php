<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Window_calculator_model extends App_Model
{
    private $itemsSchema;

    public function get_profile_items()
    {
        $itemsTable = db_prefix() . 'items';
        $schema = $this->resolve_items_schema($itemsTable);

        if (!$schema) {
            return [];
        }

        $descriptionColumn = $schema['description'] !== null ? $schema['description'] : $schema['name'];
        $rateSelect = $schema['rate'] !== null ? $schema['rate'] : '0';
        $unitSelect = $schema['unit'] !== null ? $schema['unit'] : "''";

        $this->db->select(
            $schema['id'] . ' as profile_id, '
            . $schema['name'] . ' as profile_name, '
            . $descriptionColumn . ' as profile_description, '
            . $rateSelect . ' as profile_rate, '
            . $unitSelect . ' as profile_unit'
        );
        $this->db->from($itemsTable);
        $this->db->order_by($schema['name'], 'asc');

        $result = $this->db->get()->result_array();

        return array_values(array_filter(array_map(function ($item) {
            $name = trim((string) ($item['profile_name'] ?? ''));
            if ($name === '') {
                return null;
            }

            return [
                'id' => (int) $item['profile_id'],
                'name' => $name,
                'rate' => (float) $item['profile_rate'],
                'unit' => (string) ($item['profile_unit'] ?? ''),
                'meta' => $this->extract_meta($item['profile_description'] ?? ''),
            ];
        }, $result)));
    }

    public function upsert_proposal_layout($data)
    {
        $table = db_prefix() . 'window_calculator_layouts';
        $existing = $this->db->get_where($table, ['proposal_id' => $data['proposal_id']])->row_array();

        if ($existing) {
            $this->db->where('proposal_id', $data['proposal_id']);
            $this->db->update($table, $data);

            return (int) $existing['id'];
        }

        $data['datecreated'] = date('Y-m-d H:i:s');
        $data['addedfrom'] = get_staff_user_id();

        $this->db->insert($table, $data);

        return (int) $this->db->insert_id();
    }

    public function get_by_proposal($proposalId)
    {
        return $this->db->get_where(db_prefix() . 'window_calculator_layouts', [
            'proposal_id' => $proposalId,
        ])->row_array();
    }

    private function extract_meta($description)
    {
        $decoded = json_decode((string) $description, true);
        if (is_array($decoded)) {
            return [
                'openingSurcharge' => isset($decoded['openingSurcharge']) ? (float) $decoded['openingSurcharge'] : 850,
                'archFactor' => isset($decoded['archFactor']) ? (float) $decoded['archFactor'] : 1.35,
                'triangleFactor' => isset($decoded['triangleFactor']) ? (float) $decoded['triangleFactor'] : 1.4,
            ];
        }

        return [
            'openingSurcharge' => 850,
            'archFactor' => 1.35,
            'triangleFactor' => 1.4,
        ];
    }

    private function resolve_items_schema($table)
    {
        if ($this->itemsSchema !== null) {
            return $this->itemsSchema;
        }

        $pick = function (array $candidates) use ($table) {
            foreach ($candidates as $column) {
                if ($this->db->field_exists($column, $table)) {
                    return $column;
                }
            }

            return null;
        };

        $schema = [
            'id' => $pick(['itemid', 'id', 'item_id']),
            'name' => $pick(['description', 'name', 'title', 'item_name']),
            'description' => $pick(['long_description', 'description', 'details', 'note']),
            'rate' => $pick(['rate', 'price', 'unit_price', 'selling_price']),
            'unit' => $pick(['unit', 'measure_unit']),
        ];

        if ($schema['id'] === null || $schema['name'] === null) {
            $this->itemsSchema = false;

            return $this->itemsSchema;
        }

        $this->itemsSchema = $schema;

        return $this->itemsSchema;
    }
}
