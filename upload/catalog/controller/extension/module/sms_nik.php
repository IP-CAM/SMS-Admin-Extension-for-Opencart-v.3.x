<?php
class ControllerExtensionModuleSMSNik extends Controller {
	public function index() {
        $this->load->language('extension/module/sms_nik');

        $this->load->model('extension/module/sms_nik');

        $data = array();
        $settings = $this->model_extension_module_sms_nik->getSmsModuleSettings();

        if (isset($settings['sms_code_timeout']) && isset($settings['sms_code_timeout_unit'])) {
            $sms_code_timeout_unit = $this->language->get('text_time_' . $settings['sms_code_timeout_unit']);

            $data['text_sms_code_timeout'] = sprintf($this->language->get('text_sms_code_timeout'), $settings['sms_code_timeout'], $sms_code_timeout_unit);
        }

        if (isset($settings['sms_code_lifetime']) && isset($settings['sms_code_lifetime_unit'])) {
            $sms_code_lifetime_unit = $this->language->get('text_time_' . $settings['sms_code_lifetime_unit']);

            $data['text_sms_code_lifetime'] = sprintf($this->language->get('text_sms_code_lifetime'), $settings['sms_code_lifetime'], $sms_code_lifetime_unit);
        }


        return $this->load->view('extension/module/sms_nik', $data);

//
//        if (isset($settings['login']) && isset($settings['api'])) {
//            $cwd = getcwd();
//            $dir = (strcmp(VERSION,'3.0.0.0')>=0) ? 'library/Redsms' : '';
//            chdir( DIR_SYSTEM.$dir );
//            require_once( 'RedsmsApiSimple.php' );
//
//            $redsmsApi = new RedsmsApiSimple($settings['login'], $settings['api']);
//
//
//        }


//        $req = "http://sms.ru/sms/send?api_id=" . $this->config->get('module_sms_alert_id') . "&to=" . $this->config->get('module_sms_alert_tel') . "&text=".urlencode($this->language->get('text_order') . $order_id);
//        file_get_contents($req);

        // тест запроса
        // $this->log->write($req);
	}

	public function sendActivationCode() {
	    $json = array();

        if (isset($this->request->get['phone']) && $this->request->server['REQUEST_METHOD'] == 'GET') {
            $this->load->language('extension/module/sms_nik');
            $this->load->model('extension/module/sms_nik');

            do {
                $code = $this->generateCode();

                $history = $this->model_extension_module_sms_nik->getHistoryByCode($code);
            } while(!empty($history));

            $settings = $this->model_extension_module_sms_nik->getSmsModuleSettings();

            if (isset($settings['sms_code_lifetime']) && isset($settings['sms_code_lifetime_unit'])) {
                $sms_code_timeout = $settings['sms_code_lifetime'];
                $sms_code_timeout_unit = $settings['sms_code_lifetime_unit'];
            } else {
                // set 10 minute expire time
                $sms_code_timeout = 10;
                $sms_code_timeout_unit = 1;
            }

            $expire_time_seconds = $this->convertExpireTimeToSeconds($sms_code_timeout, $sms_code_timeout_unit);

            date_default_timezone_set('Europe/Moscow');
            $expire_date = date('Y-m-d H:i:s', time() + $expire_time_seconds);

            $this->model_extension_module_sms_nik->addSmsHistory($this->request->get['phone'], $code, $expire_date);

            $json['code'] = $code;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function generateCode($length = 5) {
        $this->load->model('extension/module/sms_nik');

        $arr = array('1','2','3','4','5','6','7','8','9','0');

        $code = "";

        for ($i = 0; $i < $length; $i++) {
            $index = rand(0, count($arr) - 1);
            $code .= $arr[$index];
        }

        return $code;
    }

    private function convertExpireTimeToSeconds($time, $unitTime) {
        $seconds = 0;
        switch ($unitTime) {
            case '1':
                $seconds = $time * 60;
                break;
            case '2':
                $seconds = $time * (60 * 60);
                break;
            default:
                break;
        }

        return $seconds;
    }
}