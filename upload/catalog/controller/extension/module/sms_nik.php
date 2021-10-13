<?php
class ControllerExtensionModuleSMSNik extends Controller {
	public function index($err_msg = '') {
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

        if (isset($err_msg)) {
            $data['error_sms_code'] = $err_msg;
        } else {
            $data['error_sms_code'] = '';
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

            $settings = $this->model_extension_module_sms_nik->getSmsModuleSettings();

            $ip = $this->request->server['REMOTE_ADDR'];

            $hist = $this->model_extension_module_sms_nik->getSmsHistoryByIp($ip);

            date_default_timezone_set('Europe/Moscow');

            $current_time = time();
            if (isset($settings['sms_code_timeout']) && isset($settings['sms_code_timeout_unit'])) {
                $timeout = $this->convertExpireTimeToSeconds($settings['sms_code_timeout'], $settings['sms_code_timeout_unit']);
            } else {
                // set 1 minute timeout time
                $timeout = 60;
            }

            if (isset($hist['date_sending'])) {
                $timeout_date = strtotime($hist['date_sending']) + $timeout;
            } else {
                $timeout_date = time() - 1;
            }

            if ($timeout_date < $current_time) {
                do {
                    $code = $this->generateCode();

                    $history = $this->model_extension_module_sms_nik->getHistoryByCode($code);
                } while(!empty($history));

                if (isset($settings['sms_code_lifetime']) && isset($settings['sms_code_lifetime_unit'])) {
                    $sms_code_lifetime = $settings['sms_code_lifetime'];
                    $sms_code_lifetime_unit = $settings['sms_code_lifetime_unit'];
                } else {
                    // set 10 minute expire time
                    $sms_code_lifetime = 10;
                    $sms_code_lifetime_unit = 1;
                }

                $expire_time_seconds = $this->convertExpireTimeToSeconds($sms_code_lifetime, $sms_code_lifetime_unit);

                $expire_date = date('Y-m-d H:i:s', time() + $expire_time_seconds);

                $data = array();

                $data['phone'] = $this->request->get['phone'];
                $data['code'] = $code;
                $data['expire'] = $expire_date;
                $data['ip'] = $ip;

                $this->model_extension_module_sms_nik->addSmsHistory($data);

                $json['code'] = $code;
            } else {
                $now = time();
                $then = strtotime($hist['date_sending']);
                $seconds_timeout_left = $timeout_date - $now;

//                print_r($seconds_timeout_left);

                $time_left = 0;

                if ($seconds_timeout_left <= 60) {
                    $time_left = sprintf($this->language->get('text_seconds_left'), $seconds_timeout_left);
                } else if ($seconds_timeout_left > 60 && $seconds_timeout_left < 3600) {
                    $time_left = sprintf($this->language->get('text_minutes_left'), ($seconds_timeout_left / 60));
                } else if ($seconds_timeout_left >= 3600) {
                    $time_left = sprintf($this->language->get('text_hours_left'), ($seconds_timeout_left / 3600));
                }

                $json['time_left'] = $time_left;
            }
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
            case '0':
                $seconds = $time;
                break;
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