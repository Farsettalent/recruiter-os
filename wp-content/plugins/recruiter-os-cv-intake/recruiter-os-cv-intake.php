<?php
/**
 * Plugin Name: Recruiter OS — CV Intake (GDPR Consent)
 * Description: Lightweight CV intake form with GDPR consent capture & UTC timestamp. Stores submissions to a custom post type and provides CSV export.
 * Version: 1.0.0
 * Author: Recruiter OS
 * License: GPLv2 or later
 */
if (!defined('ABSPATH')) { exit; }

class ROS_CV_Intake {
    const CPT = 'ros_cv_submission';
    const NONCE = 'ros_cv_intake_nonce';
    public function __construct() {
        add_action('init', [$this, 'register_cpt']);
        add_shortcode('recruiter_os_cv_form', [$this, 'render_form']);
        add_action('init', [$this, 'handle_form_post']);
        add_action('admin_menu', [$this, 'admin_menu']);
    }
    public function register_cpt() {
        register_post_type(self::CPT, [
            'labels' => [
                'name' => 'CV Submissions',
                'singular_name' => 'CV Submission'
            ],
            'public' => false,
            'show_ui' => true,
            'supports' => ['title'],
            'menu_icon' => 'dashicons-media-document'
        ]);
    }
    private function field($key) {
        return isset($_POST[$key]) ? sanitize_text_field(wp_unslash($_POST[$key])) : '';
    }
    public function handle_form_post() {
        if (!isset($_POST['ros_cv_intake_submit'])) { return; }
        if (!isset($_POST[self::NONCE]) || !wp_verify_nonce($_POST[self::NONCE], 'ros_cv_intake')) {
            wp_die('Security check failed.');
        }
        // Simple honeypot
        if (!empty($_POST['company'])) { return; }
        $name = $this->field('name');
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $phone = $this->field('phone');
        $postcode = $this->field('postcode');
        $rtw = $this->field('rtw');
        $licences = $this->field('licences');
        $experience = isset($_POST['experience']) ? wp_kses_post($_POST['experience']) : '';
        $consent = isset($_POST['consent']) && $_POST['consent'] === '1' ? 1 : 0;
        if (!$name || !$email || !$consent) {
            wp_redirect(add_query_arg('ros_status','missing', wp_get_referer()));
            exit;
        }
        $title = $name . ' — CV Submission';
        $post_id = wp_insert_post([
            'post_type' => self::CPT,
            'post_title' => $title,
            'post_status' => 'publish',
        ]);
        if (is_wp_error($post_id)) { return; }
        $consent_ts = gmdate('c');
        update_post_meta($post_id, 'ros_name', $name);
        update_post_meta($post_id, 'ros_email', $email);
        update_post_meta($post_id, 'ros_phone', $phone);
        update_post_meta($post_id, 'ros_postcode', $postcode);
        update_post_meta($post_id, 'ros_rtw', $rtw);
        update_post_meta($post_id, 'ros_licences', $licences);
        update_post_meta($post_id, 'ros_experience', wp_kses_post($experience));
        update_post_meta($post_id, 'ros_consent', $consent);
        update_post_meta($post_id, 'ros_consent_timestamp_utc', $consent_ts);
        update_post_meta($post_id, 'ros_ip', isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '');
        update_post_meta($post_id, 'ros_ua', isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '');
        // File upload
        if (!function_exists('media_handle_upload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
        if (!empty($_FILES['cv_file']['name'])) {
            $attach_id = media_handle_upload('cv_file', $post_id);
            if (!is_wp_error($attach_id)) {
                update_post_meta($post_id, 'ros_cv_attachment_id', intval($attach_id));
            }
        }
        // Email admin
        $admin = get_option('admin_email');
        $subject = 'New CV Submission (GDPR consent captured)';
        $body = sprintf(
            "Name: %s\nEmail: %s\nPhone: %s\nPostcode: %s\nRTW: %s\nLicences: %s\nConsent: %s\nTimestamp (UTC): %s\nEdit: %s",
            $name, $email, $phone, $postcode, $rtw, $licences, $consent ? 'Yes' : 'No', $consent_ts, admin_url('post.php?post='.$post_id.'&action=edit')
        );
        wp_mail($admin, $subject, $body);
        wp_redirect(add_query_arg('ros_status','ok', wp_get_referer()));
        exit;
    }
    public function render_form($atts = []) {
        ob_start();
        $status = isset($_GET['ros_status']) ? sanitize_text_field($_GET['ros_status']) : '';
        if ($status === 'ok') {
            echo '<div class="notice notice-success" style="padding:8px;border-left:4px solid #46b450;background:#f6fff6;">Thanks — your CV was received. We've captured your consent timestamp.</div>';
        } elseif ($status === 'missing') {
            echo '<div class="notice notice-error" style="padding:8px;border-left:4px solid #dc3232;background:#fff6f6;">Please complete required fields and tick the consent box.</div>';
        }
        ?>
        <form method="post" enctype="multipart/form-data" class="ros-cv-form" style="display:grid;gap:12px;max-width:720px">
            <?php wp_nonce_field('ros_cv_intake', self::NONCE); ?>
            <input type="text" name="company" value="" style="display:none" tabindex="-1" autocomplete="off">
            <label>Full name *<br><input type="text" name="name" required></label>
            <label>Email *<br><input type="email" name="email" required></label>
            <label>Phone<br><input type="text" name="phone"></label>
            <label>Postcode<br><input type="text" name="postcode"></label>
            <label>Right to Work (country / status)<br><input type="text" name="rtw"></label>
            <label>Licences / Certificates<br><input type="text" name="licences" placeholder="e.g. HGV C+E, CPC, Counterbalance"></label>
            <label>Experience summary<br><textarea name="experience" rows="5"></textarea></label>
            <label>CV upload (PDF/DOC/DOCX)<br><input type="file" name="cv_file" accept=".pdf,.doc,.docx"></label>
            <label style="display:flex;gap:8px;align-items:flex-start">
                <input type="checkbox" name="consent" value="1" required>
                <span>I agree to Farset Talent / Recruiter OS processing my personal data for recruitment purposes. I understand my consent and its <strong>UTC timestamp</strong> will be recorded.</span>
            </label>
            <button type="submit" name="ros_cv_intake_submit" value="1">Submit CV</button>
        </form>
        <?php
        return ob_get_clean();
    }
    public function admin_menu() {
        add_menu_page('CV Submissions', 'CV Submissions', 'list_users', 'ros-cv-intake', [$this, 'admin_page'], 'dashicons-list-view', 25);
    }
    private function esc_csv($v){ return '"' . str_replace('"','""',$v) . '"'; }
    public function admin_page() {
        if (isset($_GET['export']) && $_GET['export'] === 'csv' && current_user_can('list_users')) {
            $args = ['post_type'=>self::CPT,'posts_per_page'=>-1,'post_status'=>'publish'];
            $q = new WP_Query($args);
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="cv_submissions.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID','Name','Email','Phone','Postcode','RTW','Licences','Consent','Consent Timestamp (UTC)','IP','User Agent','CV Attachment ID']);
            foreach ($q->posts as $p) {
                $row = [
                    $p->ID,
                    get_post_meta($p->ID,'ros_name',true),
                    get_post_meta($p->ID,'ros_email',true),
                    get_post_meta($p->ID,'ros_phone',true),
                    get_post_meta($p->ID,'ros_postcode',true),
                    get_post_meta($p->ID,'ros_rtw',true),
                    get_post_meta($p->ID,'ros_licences',true),
                    get_post_meta($p->ID,'ros_consent',true) ? 'Yes' : 'No',
                    get_post_meta($p->ID,'ros_consent_timestamp_utc',true),
                    get_post_meta($p->ID,'ros_ip',true),
                    get_post_meta($p->ID,'ros_ua',true),
                    get_post_meta($p->ID,'ros_cv_attachment_id',true),
                ];
                fputcsv($out, $row);
            }
            fclose($out);
            exit;
        }
        echo '<div class="wrap"><h1>CV Submissions</h1>';
        echo '<p><a class="button button-primary" href="'.esc_url(admin_url('admin.php?page=ros-cv-intake&export=csv')).'">Export CSV</a></p>';
        $args = ['post_type'=>self::CPT,'posts_per_page'=>20,'post_status'=>'publish'];
        $q = new WP_Query($args);
        echo '<table class="widefat striped"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Consent</th><th>Timestamp (UTC)</th><th>CV</th><th>View</th></tr></thead><tbody>';
        foreach($q->posts as $p){
            $cv_id = get_post_meta($p->ID,'ros_cv_attachment_id',true);
            $cv_link = $cv_id ? wp_get_attachment_url($cv_id) : '';
            echo '<tr>';
            echo '<td>'.$p->ID.'</td>';
            echo '<td>'.esc_html(get_post_meta($p->ID,'ros_name',true)).'</td>';
            echo '<td>'.esc_html(get_post_meta($p->ID,'ros_email',true)).'</td>';
            echo '<td>'.(get_post_meta($p->ID,'ros_consent',true)?'Yes':'No').'</td>';
            echo '<td>'.esc_html(get_post_meta($p->ID,'ros_consent_timestamp_utc',true)).'</td>';
            echo '<td>'.($cv_link?'<a href="'.esc_url($cv_link).'" target="_blank">Download CV</a>':'—').'</td>';
            echo '<td><a href="'.esc_url(admin_url('post.php?post='.$p->ID.'&action=edit')).'">Open</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }
}
new ROS_CV_Intake();
