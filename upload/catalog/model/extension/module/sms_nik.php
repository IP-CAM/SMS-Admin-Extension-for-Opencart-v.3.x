<?php
class ModelExtensionModuleSMSNik extends Model {
    public function getSmsModuleSettings() {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "sms_module`");

        return $query->row;
    }

    public function getHistoryByCode($code) {
        $query = $this->db->query("SELECT `id` FROM `" . DB_PREFIX . "sms_module_history` WHERE `code` = '" . $this->db->escape($code) . "'");

        return $query->row;
    }

    public function addSmsHistory($data) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "sms_module_history` SET `phone` = '" . $this->db->escape($data['phone']) . "', `code` = '" . $this->db->escape($data['code']) . "', `expire` = '" . $this->db->escape($data['expire']) . "', `ip` = '" . $this->db->escape($data['ip']) . "', `date_sending` = NOW()");
    }

    public function verifyCode($phone, $code) {
        $query = $this->db->query("SELECT `id` FROM `" . DB_PREFIX . "sms_module_history` WHERE `phone` = '" . $this->db->escape($phone) . "' AND `code` = '" . $this->db->escape($code) . "' AND `expire` > NOW() AND `status` = '0'");

        return $query->row;
    }

    public function acceptCode($phone, $code) {
        $this->db->query("UPDATE `" . DB_PREFIX . "sms_module_history` SET `status` = '1' WHERE `phone` = '" . $this->db->escape($phone) . "' AND `code` = '" . $this->db->escape($code) . "' AND `expire` > NOW()");
    }

    public function getSmsHistoryByIp($ip, $block_time_left, $block_count) {
        $query = $this->db->query("SELECT `date_sending` FROM `" . DB_PREFIX . "sms_module_history` WHERE `ip` = '" . $ip . "' AND `date_sending` > '" . $block_time_left . "' ORDER BY `id` DESC LIMIT " . (int)$block_count); // AND `status` = '0'

        return $query->rows;
    }

    public function blockOtherSms($phone) {
        $this->db->query("UPDATE `" . DB_PREFIX . "sms_module_history` SET `status` = '1' WHERE `phone` = '" . $this->db->escape($phone) . "'");
    }

    public function getCustomerByPhone($phone) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer WHERE telephone = '" . $this->db->escape($phone) . "'");

        return $query->row;
    }
}