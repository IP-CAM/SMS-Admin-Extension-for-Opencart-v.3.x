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
	}

	public function sendActivationCode() {
	    $json = array();

        if (isset($this->request->get['phone']) && $this->request->server['REQUEST_METHOD'] == 'GET') {
            $this->load->language('extension/module/sms_nik');
            $this->load->model('extension/module/sms_nik');
            date_default_timezone_set('Europe/Moscow');

            $customer = $this->model_extension_module_sms_nik->getCustomerByPhone($this->request->get['phone']);

            if (empty($customer)) {
                $settings = $this->model_extension_module_sms_nik->getSmsModuleSettings();

                $ip = $this->request->server['REMOTE_ADDR'];

                if (isset($settings['sms_code_count']) && isset($settings['sms_code_count_unit'])) {
                    $block_time = $this->convertCountTimeToSeconds($settings['sms_code_count_unit']);
                    $block_count = $settings['sms_code_count'];
                } else {
                    // set 1 hour available interval for sending sms
                    $block_time = 3600;
                    $block_count = 2;
                }

                $current_time = time();

                $time_block_left = date('Y-m-d H:i:s', ($current_time - $block_time));

                $hist = $this->model_extension_module_sms_nik->getSmsHistoryByIp($ip, $time_block_left, $block_count);

                if (count($hist) < (int)$block_count) {
                    if (isset($settings['sms_code_timeout']) && isset($settings['sms_code_timeout_unit'])) {
                        $timeout = $this->convertExpireTimeToSeconds($settings['sms_code_timeout'], $settings['sms_code_timeout_unit']);
                    } else {
                        // set 1 minute timeout time
                        $timeout = 60;
                    }

                    if (isset($hist[0]['date_sending'])) {
                        $timeout_date = strtotime($hist[0]['date_sending']) + $timeout;
                    } else {
                        $timeout_date = time() - 1;
                    }

                    if ($timeout_date < $current_time) {
                        do {
                            $code = $this->generateCode();

                            $history = $this->model_extension_module_sms_nik->getHistoryByCode($code);
                        } while (!empty($history));

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

                        $this->model_extension_module_sms_nik->blockOtherSms($this->request->get['phone']);

                        $this->model_extension_module_sms_nik->addSmsHistory($data);

                        $data['message'] = sprintf($this->language->get('text_sms_message'), $code);

                        if (isset($settings['login']) && isset($settings['api'])) {
                            $answer = $this->sendSMS($settings['login'], $settings['api'], $data);
                            print_r($answer);
                        }

                        $json['code'] = 1;
                    } else {
                        $now = time();
                        $seconds_timeout_left = $timeout_date - $now;

                        $time_left = 0;

                        if ($seconds_timeout_left <= 60) {
                            $time_left = sprintf($this->language->get('text_seconds_left'), $seconds_timeout_left);
                        } else if ($seconds_timeout_left > 60 && $seconds_timeout_left < 3600) {
                            $time_left = sprintf($this->language->get('text_minutes_left'), ($seconds_timeout_left / 60));
                        } else if ($seconds_timeout_left >= 3600) {
                            $time_left = sprintf($this->language->get('text_hours_left'), ($seconds_timeout_left / 3600));
                        }

                        $json['msg'] = $time_left;
                    }
                } else {
                    $now = time();

                    $block_time_stop = strtotime($hist[count($hist) - 1]['date_sending']) + $block_time;

                    $block_time_left = $block_time_stop - $now;

                    $time_left = 0;

                    if ($block_time_left <= 60) {
                        $time_left = sprintf($this->language->get('text_seconds_left'), $block_time_left);
                    } else if ($block_time_left > 60 && $block_time_left < 3600) {
                        $time_left = sprintf($this->language->get('text_minutes_left'), ($block_time_left / 60));
                    } else if ($block_time_left > 3600 && $block_time_left < (3600 * 24)) {
                        $time_left = sprintf($this->language->get('text_hours_left'), ($block_time_left / 3600));
                    } else if ($block_time_left > (3600 * 24)) {
                        $time_left = sprintf($this->language->get('text_days_left'), ($block_time_left / (3600 * 24)));
                    }

                    $json['msg'] = $time_left;
                }
            } else {
                $json['msg'] = $this->language->get('error_phone_exist');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function sendSMS($login, $api, $data) {
        $cwd = getcwd();
        $dir = (strcmp(VERSION,'3.0.0.0')>=0) ? 'library/Redsms' : '';
        chdir( DIR_SYSTEM.$dir );
        require_once( 'RedsmsApiSimple.php' );

        $redsmsApi = new RedsmsApiSimple($login, $api);

        return $redsmsApi->sendSMS($data['phone'], $data['message'], 'REDSMS.RU');
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

    private function convertCountTimeToSeconds($unitTime) {
        $seconds = 1;
        switch ($unitTime) {
            case '0':
                $seconds = $seconds * 60;
                break;
            case '1':
                $seconds = $seconds * (60 * 60);
                break;
            case '2':
                $seconds = $seconds * (60 * 60 * 24);
                break;
            default:
                break;
        }

        return $seconds;
    }
}