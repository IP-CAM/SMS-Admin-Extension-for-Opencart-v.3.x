<?php
class ModelExtensionModuleSMSNik extends Model {
	public function install() {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "sms_module` (
              `login` varchar(40) NOT NULL,
              `api` varchar(255) NOT NULL,
              `sms_code_lifetime` varchar(10) NOT NULL,
              `sms_code_lifetime_unit` INT(3) NOT NULL,
              `sms_code_timeout` varchar(10) NOT NULL,
              `sms_code_timeout_unit` INT(3) NOT NULL,
              `sms_code_count` varchar(10) NOT NULL,
              `sms_code_count_unit` INT(3) NOT NULL,
              PRIMARY KEY (`api`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
		");
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "sms_module_history` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `phone` varchar(40) NOT NULL,
              `code` varchar(40) NOT NULL,
              `expire` datetime NOT NULL,
              `date_sending` datetime NOT NULL,
              `ip` varchar(40) NOT NULL,
              `status` TINYINT(1) NOT NULL DEFAULT 0,
              PRIMARY KEY (`id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
		");
	}

	public function uninstall() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "sms_module`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "sms_module_history`");
	}

	public function saveSmsModuleSettings($data) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "sms_module`");

        $this->db->query("INSERT INTO " . DB_PREFIX . "sms_module SET `login` = '" . $this->db->escape($data['login']) . "', `api` = '" . $this->db->escape($data['api']) . "', `sms_code_lifetime` = '" . $this->db->escape($data['sms_code_lifetime']) . "', `sms_code_lifetime_unit` = '" . $this->db->escape($data['sms_code_lifetime_unit']) . "', `sms_code_timeout` = '" . $this->db->escape($data['sms_code_timeout']) . "', `sms_code_timeout_unit` = '" . $this->db->escape($data['sms_code_timeout_unit']) . "', `sms_code_count` = '" . $this->db->escape($data['sms_code_count']) . "', `sms_code_count_unit` = '" . $this->db->escape($data['sms_code_count_unit']) . "'");
    }

    public function getSmsModuleSettings() {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "sms_module`");

        return $query->row;
    }
}
