<?php
/**
 * CRM Bulk Email Campaign Queue Model
 * Handles queueing, sending, and tracking bulk emails via WP Cron.
 */
class Gee_Woo_CRM_Campaign_Queue {
    protected $table;
    protected $wpdb;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . 'gee_crm_campaign_queue';
    }

    /**
     * Run the migration to create the queue table
     */
    public function migrate() {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `campaign_id` bigint(20) unsigned NOT NULL,
            `contact_id` bigint(20) unsigned NOT NULL,
            `email` varchar(255) NOT NULL,
            `status` varchar(20) NOT NULL DEFAULT 'pending',
            `last_attempt_at` datetime DEFAULT NULL,
            `tries` int(11) NOT NULL DEFAULT 0,
            `error_message` text,
            PRIMARY KEY (`id`),
            KEY `campaign` (`campaign_id`),
            KEY `status` (`status`),
            KEY `contact` (`contact_id`)
        ) {$this->wpdb->get_charset_collate()};";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Insert queue rows (bulk)
     */
    public function bulk_insert($campaign_id, $recipients) {
        $data = array();
        foreach ($recipients as $recipient) {
            $data[] = $this->wpdb->prepare(
                "(%d, %d, %s, 'pending', NULL, 0, NULL)",
                $campaign_id, $recipient->id, $recipient->email
            );
        }
        if ($data) {
            $this->wpdb->query(
                "INSERT INTO `{$this->table}` (campaign_id, contact_id, email, status, last_attempt_at, tries, error_message) VALUES "
                . implode(',', $data)
            );
        }
    }

    /**
     * Get next batch of pending queue items
     */
    public function get_next_batch($campaign_id, $batch_size = 20) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM `{$this->table}` WHERE campaign_id = %d AND status = 'pending' ORDER BY id ASC LIMIT %d",
            $campaign_id, $batch_size
        ));
    }

    /**
     * Mark queue item as sent/failed
     */
    public function update_status($id, $status, $error = '') {
        $this->wpdb->update(
            $this->table,
            array(
                'status' => $status,
                'last_attempt_at' => current_time('mysql'),
                'tries' => $this->wpdb->get_var($this->wpdb->prepare("SELECT tries FROM `{$this->table}` WHERE id = %d", $id)) + 1,
                'error_message' => $error
            ),
            array('id' => $id),
            array('%s', '%s', '%d', '%s'),
            array('%d')
        );
    }

    /**
     * Get counts by status for progress reporting
     */
    public function get_counts($campaign_id) {
        $rows = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT status, COUNT(*) as count FROM `{$this->table}` WHERE campaign_id = %d GROUP BY status",
            $campaign_id
        ));
        $counts = array('pending' => 0, 'sent' => 0, 'failed' => 0);
        foreach ($rows as $row) {
            $counts[$row->status] = (int) $row->count;
        }
        return $counts;
    }
}

