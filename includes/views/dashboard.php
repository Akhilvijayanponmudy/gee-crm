<?php
global $wpdb;

// 1. Stats
$contact_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gee_crm_contacts" );
$tag_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gee_crm_tags" );
$segment_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gee_crm_segments" );
$emails_sent_30 = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gee_crm_campaign_logs WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND status='sent'" );

// 2. Recent Emails
$recent_campaigns = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}gee_crm_campaigns WHERE status='sent' ORDER BY sent_at DESC LIMIT 5" );

// 3. Graph Data
// A. Emails Sent over last 30 days
$email_stats = $wpdb->get_results( "
    SELECT DATE(sent_at) as date, COUNT(*) as count 
    FROM {$wpdb->prefix}gee_crm_campaign_logs 
    WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND status='sent'
    GROUP BY DATE(sent_at)
    ORDER BY date ASC
");

// Pre-fill dates
$dates = [];
$counts = [];
for($i=29; $i>=0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $dates[$d] = 0;
}
foreach($email_stats as $stat) {
    if(isset($dates[$stat->date])) $dates[$stat->date] = $stat->count;
}
$labels_email = array_keys($dates);
$data_email = array_values($dates);

// B. Contact Growth
$contact_growth = $wpdb->get_results( "
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM {$wpdb->prefix}gee_crm_contacts 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
// Cumulative? Or just daily? "Contacts growth" usually implies total over time or new users per day. User said "Contacts growth", I'll show new contacts per day for simplicity.
$dates_c = $dates; // Reuse keys
foreach($contact_growth as $stat) {
    if(isset($dates_c[$stat->date])) $dates_c[$stat->date] = $stat->count;
}
$data_contacts = array_values($dates_c);

?>

<div class="gee-crm-stats">
    <div class="gee-crm-stat-grid">
        <div class="gee-crm-stat-box">
            <div class="gee-crm-stat-number"><?php echo $contact_count; ?></div>
            <div class="gee-crm-stat-label">Total Contacts</div>
        </div>
        <div class="gee-crm-stat-box">
            <div class="gee-crm-stat-number"><?php echo $emails_sent_30; ?></div>
            <div class="gee-crm-stat-label">Emails Sent (30d)</div>
        </div>
        <div class="gee-crm-stat-box">
            <div class="gee-crm-stat-number"><?php echo $tag_count; ?></div>
            <div class="gee-crm-stat-label">Tags</div>
        </div>
        <div class="gee-crm-stat-box">
            <div class="gee-crm-stat-number"><?php echo $segment_count; ?></div>
            <div class="gee-crm-stat-label">Segments</div>
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns: 2fr 1fr; gap:20px;">
    <!-- Main Col -->
    <div>
        <div class="gee-crm-card">
            <h3>Email Performance (Last 30 Days)</h3>
            <canvas id="emailChart" height="100"></canvas>
        </div>
        
        <div class="gee-crm-card">
            <h3>New Contacts (Last 30 Days)</h3>
            <canvas id="contactChart" height="100"></canvas>
        </div>
    </div>
    
    <!-- Sidebar Col -->
    <div>
        <div class="gee-crm-card">
            <h3>Recent Campaigns</h3>
            <ul style="list-style:none; padding:0; margin:0;">
                <?php if($recent_campaigns): ?>
                    <?php foreach($recent_campaigns as $c): ?>
                        <li style="border-bottom:1px solid #eee; padding:10px 0;">
                            <strong><?php echo esc_html($c->subject); ?></strong><br>
                            <small class="text-muted"><?php echo $c->sent_at; ?></small>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li style="color:#666;">No campaigns sent yet.</li>
                <?php endif; ?>
            </ul>
            <div style="margin-top:10px;">
                <a href="?page=gee-woo-crm&view=campaigns" class="gee-crm-btn gee-crm-btn-primary" style="width:100%; text-align:center;">View All Campaigns</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Email Chart
    const ctx = document.getElementById('emailChart');
    if(ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels_email); ?>,
                datasets: [{
                    label: 'Emails Sent',
                    data: <?php echo json_encode($data_email); ?>,
                    borderColor: '#0073aa',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: { scales: { y: { beginAtZero: true } } }
        });
    }

    // Contact Chart
    const ctx2 = document.getElementById('contactChart');
    if(ctx2) {
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($labels_email); ?>, // Same dates
                datasets: [{
                    label: 'New Contacts',
                    data: <?php echo json_encode($data_contacts); ?>,
                    backgroundColor: '#46b450',
                }]
            },
            options: { scales: { y: { beginAtZero: true } } }
        });
    }
});
</script>
