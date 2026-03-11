<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Window_calculator_model extends App_Model
{
    public function get_profile_items()
    {
        $itemsTable = db_prefix() . 'items';

        $this->db->select('itemid, description, long_description, rate, unit');
        $this->db->from($itemsTable);
        $this->db->where('active', 1);
        $this->db->order_by('description', 'asc');

        $result = $this->db->get()->result_array();

        return array_map(function ($item) {
            return [
                'id' => (int) $item['itemid'],
                'name' => $item['description'],
                'rate' => (float) $item['rate'],
                'unit' => $item['unit'],
                'meta' => $this->extract_meta($item['long_description']),
            ];
        }, $result);
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
}
