<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Window_calculator extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('window_calculator/window_calculator_model');
    }

    public function index()
    {
        if (!has_permission('proposals', '', 'view')) {
            access_denied('Window calculator');
        }

        $data['title'] = 'Window Calculator';
        $data['profiles'] = $this->window_calculator_model->get_profile_items();
        $this->load->view('window_calculator/calculator', $data);
    }

    public function profile_items()
    {
        if (!is_staff_member()) {
            ajax_access_denied();
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'items' => $this->window_calculator_model->get_profile_items(),
            ]));
    }

    public function save_visual()
    {
        if (!is_staff_member()) {
            ajax_access_denied();
        }

        $payload = $this->input->post();
        $proposalId = isset($payload['proposal_id']) ? (int) $payload['proposal_id'] : 0;

        if ($proposalId < 1) {
            $this->output
                ->set_status_header(422)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'Вкажіть коректний ID пропозиції.',
                    'csrf_hash' => $this->security->get_csrf_hash(),
                ]));

            return;
        }

        $record = [
            'proposal_id' => $proposalId,
            'title' => (string) ($payload['title'] ?? 'Віконна конструкція'),
            'profile_item_id' => (int) ($payload['profile_item_id'] ?? 0),
            'configuration_json' => (string) ($payload['configuration_json'] ?? '{}'),
            'svg_markup' => (string) ($payload['svg_markup'] ?? ''),
            'pricing_json' => (string) ($payload['pricing_json'] ?? '{}'),
            'total' => (float) ($payload['total'] ?? 0),
        ];

        $id = $this->window_calculator_model->upsert_proposal_layout($record);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => $id > 0,
                'id' => $id,
                'message' => $id > 0 ? 'Візуал успішно збережено в пропозицію.' : 'Не вдалося зберегти візуал.',
                'csrf_hash' => $this->security->get_csrf_hash(),
            ]));
    }
}
