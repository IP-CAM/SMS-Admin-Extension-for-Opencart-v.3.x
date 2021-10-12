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

    public function addSmsHistory($phone, $code, $expire) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "sms_module_history` SET `phone` = '" . $this->db->escape($phone) . "', `code` = '" . $this->db->escape($code) . "', `expire` = '" . $this->db->escape($expire) . "', `date_sending` = NOW()");
    }

    public function verifyCode($phone, $code) {
        $query = $this->db->query("SELECT `id` FROM `" . DB_PREFIX . "sms_module_history` WHERE `phone` = '" . $phone . "' AND `code` = '" . $this->db->escape($code) . "' AND `expire` > NOW()");

        return $query->row;
    }
}