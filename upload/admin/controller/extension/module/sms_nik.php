<?php
class ControllerExtensionModuleSMSNik extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/sms_nik');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');
		$this->load->model('extension/module/sms_nik');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
//			$this->model_setting_setting->editSetting('module_sms_nik', $this->request->post);
            $post = $this->request->post;

            if (utf8_strlen(trim($post['sms_code_lifetime'])) < 1) {
                $post['sms_code_lifetime'] = '15';
                $post['sms_code_lifetime_unit'] = 1;
            }
            if (utf8_strlen(trim($post['sms_code_timeout'])) < 1) {
                $post['sms_code_timeout'] = '60';
                $post['sms_code_timeout_unit'] = 0;
            }
            if (utf8_strlen(trim($post['sms_code_count'])) < 1) {
                $post['sms_code_count'] = '2';
                $post['sms_code_count_unit'] = 1;
            }

            $this->model_extension_module_sms_nik->saveSmsModuleSettings($post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/sms_nik', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/sms_nik', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        $sms_settings = $this->model_extension_module_sms_nik->getSmsModuleSettings();

        if (isset($this->request->post['login'])) {
            $data['login'] = $this->request->post['login'];
        } elseif (isset($sms_settings['login'])) {
            $data['login'] = $sms_settings['login'];
        } else {
            $data['login'] = '';
        }

        if (isset($this->request->post['api'])) {
            $data['api'] = $this->request->post['api'];
        } elseif (isset($sms_settings['login'])) {
            $data['api'] = $sms_settings['api'];
        } else {
            $data['api'] = '';
        }

        if (isset($this->request->post['sms_code_lifetime'])) {
            $data['sms_code_lifetime'] = $this->request->post['sms_code_lifetime'];
        } elseif (isset($sms_settings['sms_code_lifetime'])) {
            $data['sms_code_lifetime'] = $sms_settings['sms_code_lifetime'];
        } else {
            $data['sms_code_lifetime'] = '';
        }

        if (isset($this->request->post['sms_code_lifetime_unit'])) {
            $data['sms_code_lifetime_unit'] = $this->request->post['sms_code_lifetime_unit'];
        } elseif (isset($sms_settings['sms_code_lifetime_unit'])) {
            $data['sms_code_lifetime_unit'] = $sms_settings['sms_code_lifetime_unit'];
        } else {
            $data['sms_code_lifetime_unit'] = 0;
        }

        if (isset($this->request->post['sms_code_timeout'])) {
            $data['sms_code_timeout'] = $this->request->post['sms_code_timeout'];
        } elseif (isset($sms_settings['sms_code_timeout'])) {
            $data['sms_code_timeout'] = $sms_settings['sms_code_timeout'];
        } else {
            $data['sms_code_timeout'] = '';
        }

        if (isset($this->request->post['sms_code_timeout_unit'])) {
            $data['sms_code_timeout_unit'] = $this->request->post['sms_code_timeout_unit'];
        } elseif (isset($sms_settings['sms_code_timeout_unit'])) {
            $data['sms_code_timeout_unit'] = $sms_settings['sms_code_timeout_unit'];
        } else {
            $data['sms_code_timeout_unit'] = 0;
        }

        if (isset($this->request->post['sms_code_count'])) {
            $data['sms_code_count'] = $this->request->post['sms_code_count'];
        } elseif (isset($sms_settings['sms_code_count'])) {
            $data['sms_code_count'] = $sms_settings['sms_code_count'];
        } else {
            $data['sms_code_count'] = '';
        }

        if (isset($this->request->post['sms_code_count_unit'])) {
            $data['sms_code_count_unit'] = $this->request->post['sms_code_count_unit'];
        } elseif (isset($sms_settings['sms_code_count_unit'])) {
            $data['sms_code_count_unit'] = $sms_settings['sms_code_count_unit'];
        } else {
            $data['sms_code_count_unit'] = 0;
        }

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/sms_nik', $data));
	}

    public function install() {
        if ($this->user->hasPermission('modify', 'extension/module/sms_nik')) {
            $this->load->model('extension/module/sms_nik');

            $this->model_extension_module_sms_nik->install();
        }
    }

    public function uninstall() {
        if ($this->user->hasPermission('modify', 'extension/module/sms_nik')) {
            $this->load->model('extension/module/sms_nik');

            $this->model_extension_module_sms_nik->uninstall();
        }
    }


    protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/sms_nik')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}