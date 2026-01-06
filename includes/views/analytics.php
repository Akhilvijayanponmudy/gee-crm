<?php
require_once GEE_WOO_CRM_PATH . 'includes/models/class-gee-woo-crm-campaign.php';

global $wpdb;
$campaign_model = new Gee_Woo_CRM_Campaign();

// Overall Statistics
$total_campaigns = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gee_crm_campaigns WHERE status = 'sent'" );
$total_emails_sent = $wpdb->get_var( "SELECT SUM(total_sent) FROM {$wpdb->prefix}gee_crm_campaigns WHERE status = 'sent'" );
$total_emails_failed = $wpdb->get_var( "SELECT SUM(total_failed) FROM {$wpdb->prefix}gee_crm_campaigns WHERE status = 'sent'" );
$total_recipients = $wpdb->get_var( "SELECT SUM(total_recipients) FROM {$wpdb->prefix}gee_crm_campaigns WHERE status = 'sent'" );

$success_rate = $total_recipients > 0 ? round( ( $total_emails_sent / $total_recipients ) * 100, 2 ) : 0;
$failure_rate = $total_recipients > 0 ? round( ( $total_emails_failed / $total_recipients ) * 100, 2 ) : 0;

// Last 30 days stats
$emails_sent_30 = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gee_crm_campaign_logs WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND status='sent'" );
$emails_failed_30 = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gee_crm_campaign_logs WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND status='failed'" );

// Get all sent campaigns with stats
$campaigns = $wpdb->get_results( "
    SELECT 
        c.*,
        COUNT(l.id) as log_count,
        SUM(CASE WHEN l.status = 'sent' THEN 1 ELSE 0 END) as sent_count,
        SUM(CASE WHEN l.status = 'failed' THEN 1 ELSE 0 END) as failed_count
    FROM {$wpdb->prefix}gee_crm_campaigns c
    LEFT JOIN {$wpdb->prefix}gee_crm_campaign_logs l ON c.id = l.campaign_id
    WHERE c.status = 'sent'
    GROUP BY c.id
    ORDER BY c.sent_at DESC
" );

// Campaign performance over time (last 30 days)
$campaign_performance = $wpdb->get_results( "
    SELECT 
        DATE(c.sent_at) as date,
        COUNT(DISTINCT c.id) as campaigns_count,
        SUM(c.total_sent) as emails_sent,
        SUM(c.total_failed) as emails_failed,
        SUM(c.total_recipients) as recipients
    FROM {$wpdb->prefix}gee_crm_campaigns c
    WHERE c.status = 'sent' 
    AND c.sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(c.sent_at)
    ORDER BY date DESC
" );

// Prepare chart data
$dates = [];
$campaign_counts = [];
$sent_counts = [];
$failed_counts = [];
for( $i = 29; $i >= 0; $i-- ) {
    $d = date( 'Y-m-d', strtotime( "-$i days" ) );
    $dates[$d] = 0;
    $campaign_counts[$d] = 0;
    $sent_counts[$d] = 0;
    $failed_counts[$d] = 0;
}

foreach( $campaign_performance as $perf ) {
    if( isset( $dates[$perf->date] ) ) {
        $campaign_counts[$perf->date] = intval( $perf->campaigns_count );
        $sent_counts[$perf->date] = intval( $perf->emails_sent );
        $failed_counts[$perf->date] = intval( $perf->emails_failed );
    }
}

$chart_labels = array_keys( $dates );
$chart_campaigns = array_values( $campaign_counts );
$chart_sent = array_values( $sent_counts );
$chart_failed = array_values( $failed_counts );

// Status breakdown
$status_breakdown = $wpdb->get_results( "
    SELECT 
        status,
        COUNT(*) as count
    FROM {$wpdb->prefix}gee_crm_campaign_logs
    WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY status
" );

$status_data = [];
$status_labels = [];
foreach( $status_breakdown as $status ) {
    $status_labels[] = ucfirst( $status->status );
    $status_data[] = intval( $status->count );
}
?>

<div class="gee-crm-stats">
    <div class="gee-crm-stat-grid">
        <div class="gee-crm-stat-box">
            <div class="gee-crm-stat-number"><?php echo number_format( $total_campaigns ); ?></div>
            <div class="gee-crm-stat-label">Total Campaigns Sent</div>
        </div>
        <div class="gee-crm-stat-box">
            <div class="gee-crm-stat-number"><?php echo number_format( $total_emails_sent ?: 0 ); ?></div>
            <div class="gee-crm-stat-label">Total Emails Sent</div>
        </div>
        <div class="gee-crm-stat-box">
            <div class="gee-crm-stat-number"><?php echo number_format( $total_emails_failed ?: 0 ); ?></div>
            <div class="gee-crm-stat-label">Total Failed</div>
        </div>
        <div class="gee-crm-stat-box">
            <div class="gee-crm-stat-number"><?php echo $success_rate; ?>%</div>
            <div class="gee-crm-stat-label">Success Rate</div>
        </div>
        <div class="gee-crm-stat-box">
            <div class="gee-crm-stat-number"><?php echo number_format( $emails_sent_30 ); ?></div>
            <div class="gee-crm-stat-label">Sent (Last 30 Days)</div>
        </div>
        <div class="gee-crm-stat-box">
            <div class="gee-crm-stat-number"><?php echo number_format( $emails_failed_30 ); ?></div>
            <div class="gee-crm-stat-label">Failed (Last 30 Days)</div>
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns: 2fr 1fr; gap:20px; margin-top:20px;">
    <!-- Main Column -->
    <div>
        <div class="gee-crm-card">
            <h3>Campaign Performance (Last 30 Days)</h3>
            <canvas id="campaignPerformanceChart" height="100"></canvas>
        </div>
        
        <div class="gee-crm-card" style="margin-top:20px;">
            <h3>Email Status Breakdown (Last 30 Days)</h3>
            <canvas id="statusChart" height="100"></canvas>
        </div>
    </div>
    
    <!-- Sidebar Column -->
    <div>
        <div class="gee-crm-card">
            <h3>Top Performing Campaigns</h3>
            <?php if( ! empty( $campaigns ) ): ?>
                <div style="max-height:400px; overflow-y:auto;">
                    <?php foreach( array_slice( $campaigns, 0, 5 ) as $campaign ): 
                        $campaign_success_rate = $campaign->total_recipients > 0 ? round( ( $campaign->total_sent / $campaign->total_recipients ) * 100, 1 ) : 0;
                    ?>
                        <div style="border-bottom:1px solid #eee; padding:15px 0;">
                            <strong><?php echo esc_html( $campaign->name ); ?></strong><br>
                            <small style="color:#666;"><?php echo esc_html( $campaign->subject ); ?></small><br>
                            <div style="margin-top:8px; font-size:12px;">
                                <span style="color:#46b450;">✓ <?php echo number_format( $campaign->total_sent ); ?> sent</span>
                                <?php if( $campaign->total_failed > 0 ): ?>
                                    <span style="color:#dc3232; margin-left:10px;">✗ <?php echo number_format( $campaign->total_failed ); ?> failed</span>
                                <?php endif; ?>
                                <br>
                                <span style="color:#666;">Success Rate: <?php echo $campaign_success_rate; ?>%</span>
                                <br>
                                <span style="color:#999;"><?php echo $campaign->sent_at ? date( 'M j, Y g:i A', strtotime( $campaign->sent_at ) ) : 'N/A'; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color:#666;">No campaigns sent yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Campaign Details Table -->
<div class="gee-crm-card" style="margin-top:20px;">
    <h3>All Campaign Analytics</h3>
    <div style="overflow-x:auto;">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Campaign Name</th>
                    <th>Subject</th>
                    <th>Recipients</th>
                    <th>Sent</th>
                    <th>Failed</th>
                    <th>Success Rate</th>
                    <th>Sent Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if( ! empty( $campaigns ) ): ?>
                    <?php foreach( $campaigns as $campaign ): 
                        $campaign_success_rate = $campaign->total_recipients > 0 ? round( ( $campaign->total_sent / $campaign->total_recipients ) * 100, 1 ) : 0;
                        $success_color = $campaign_success_rate >= 90 ? '#46b450' : ( $campaign_success_rate >= 70 ? '#ffb900' : '#dc3232' );
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html( $campaign->name ); ?></strong></td>
                            <td><?php echo esc_html( $campaign->subject ); ?></td>
                            <td><?php echo number_format( $campaign->total_recipients ); ?></td>
                            <td style="color:#46b450;"><strong><?php echo number_format( $campaign->total_sent ); ?></strong></td>
                            <td style="color:#dc3232;"><?php echo number_format( $campaign->total_failed ); ?></td>
                            <td>
                                <span style="color:<?php echo $success_color; ?>; font-weight:bold;">
                                    <?php echo $campaign_success_rate; ?>%
                                </span>
                            </td>
                            <td><?php echo $campaign->sent_at ? date( 'M j, Y g:i A', strtotime( $campaign->sent_at ) ) : 'N/A'; ?></td>
                            <td>
                                <a href="?page=gee-woo-crm&view=campaigns&action=view&id=<?php echo $campaign->id; ?>" class="gee-crm-btn" style="font-size:12px; padding:4px 8px;">View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align:center; color:#666; padding:20px;">
                            No campaigns have been sent yet. <a href="?page=gee-woo-crm&view=campaigns&action=new">Create your first campaign</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Campaign Performance Chart
    const ctx1 = document.getElementById('campaignPerformanceChart');
    if(ctx1) {
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: <?php echo json_encode( $chart_labels ); ?>,
                datasets: [
                    {
                        label: 'Emails Sent',
                        data: <?php echo json_encode( $chart_sent ); ?>,
                        borderColor: '#46b450',
                        backgroundColor: 'rgba(70, 180, 80, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Emails Failed',
                        data: <?php echo json_encode( $chart_failed ); ?>,
                        borderColor: '#dc3232',
                        backgroundColor: 'rgba(220, 50, 50, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                }
            }
        });
    }

    // Status Breakdown Chart
    const ctx2 = document.getElementById('statusChart');
    if(ctx2 && <?php echo ! empty( $status_data ) ? 'true' : 'false'; ?>) {
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode( $status_labels ); ?>,
                datasets: [{
                    data: <?php echo json_encode( $status_data ); ?>,
                    backgroundColor: [
                        '#46b450',
                        '#dc3232',
                        '#ffb900'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    }
                }
            }
        });
    }
});
</script>

