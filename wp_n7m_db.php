<?php

namespace Imagined;

class WP_N7M_DB extends WP_N7M
{
    public $wpdb;
    public $table;
    public $charset;

    public function __construct()
    {
        // Use WordPress DataBase
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $this->wpdb->prefix . WP_N7M::N7M;
        $this->charset = $this->wpdb->get_charset_collate();
    }

    public function createTable()
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS `$this->table`
        (
            `id` mediumint(9) NOT NULL AUTO_INCREMENT,
            `numeronym` varchar(55) DEFAULT '' NOT NULL,
            `redirect` varchar(55) DEFAULT '' NOT NULL,
            `count` mediumint(9) NOT NULL DEFAULT '0',
            `last` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		    PRIMARY KEY (`id`)
		);
SQL;
        require_once(ABSPATH . implode(DIRECTORY_SEPARATOR, ['wp-admin', 'includes', 'upgrade.php']));
        dbDelta($sql);
        $this->wpdb->query("TRUNCATE TABLE `$this->table`;");
    }

    public function saveNumeronym($request)
    {
        $numeronym = $this->generateN7M($request);
        if (!empty($request) && !empty($numeronym)) {
            $this->wpdb->insert(
                $this->table,
                [
                    'numeronym' => $numeronym,
                    'redirect' => $request
                ]
            );
        }
    }

    public function getAllNumeronyms()
    {
        return $this->wpdb->get_col("SELECT DISTINCT `numeronym` FROM `$this->table`;");
    }

    public function getNumeronym($request = '')
    {
        if (empty($request)) {
            $request = parent::getRequest($_SERVER['REQUEST_URI']);
        }
        $row = $this->wpdb->get_row("SELECT * FROM `$this->table` WHERE `redirect` = '$request';");
        return $row->numeronym;
    }

    public function getRedirect($request = '')
    {
        if (empty($request)) {
            $request = parent::getRequest($_SERVER['REQUEST_URI']);
        }
        $row = $this->wpdb->get_row("SELECT * FROM `$this->table` WHERE `numeronym` = '$request';");
        $this->wpdb->update(
            $this->table,
            ['count' => ($row->count + 1), 'last' => date('Y-m-d H:i:s')],
            ['id' => $row->id]
        );
        return $row->redirect;
    }

}