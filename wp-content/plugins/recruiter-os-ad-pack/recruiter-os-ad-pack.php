<?php
/**
 * Plugin Name: Recruiter OS — Advert Generator Test Pack
 * Description: Creates a Job Ad custom post type and seeds 5 sample ads across niches. Shortcode: [ros_job_ad id="123"].
 * Version: 1.0.0
 * Author: Recruiter OS
 * License: GPLv2 or later
 */
if (!defined('ABSPATH')) { exit; }

class ROS_Ad_Pack {
    const CPT = 'ros_job_ad';
    public function __construct(){
        add_action('init', [$this, 'register_cpt']);
        register_activation_hook(__FILE__, [$this, 'activate']);
        add_shortcode('ros_job_ad', [$this, 'shortcode']);
    }
    public function register_cpt(){
        register_post_type(self::CPT, [
            'labels'=>['name'=>'Job Ads','singular_name'=>'Job Ad'],
            'public'=>true,
            'show_ui'=>true,
            'has_archive'=>false,
            'supports'=>['title','editor'],
            'menu_icon'=>'dashicons-megaphone'
        ]);
    }
    public function activate(){
        $samples = [
            ['HGV Class 2 Driver — Days (Belfast)','- **Role:** HGV Class 2 Driver (Days)\n- **Hours:** Mon–Fri, 07:00–16:00\n- **Pay:** £13.50–£14.50 p/h + OT\n- **Location:** Belfast & Greater NI multi-drop\n- **Must-haves:** Valid C licence, CPC, Digi Card, 6+ months multi-drop\n- **Apply:** Send CV and licence details to careers@example.com'],
            ['Cyber Security Analyst (SOC) — Belfast','- **Role:** SOC Analyst (Tier 1–2)\n- **Hours:** Shift-based (24/7 rota)\n- **Salary:** £35k–£45k + benefits\n- **Location:** Belfast (hybrid)\n- **Must-haves:** SIEM (Splunk/Elastic), incident response, right to work\n- **Apply:** Submit CV + brief incident write-up.'],
            ['RGN Staff Nurse — Belfast Trust','- **Role:** Registered General Nurse (Band 5)\n- **Hours:** 37.5 per week, rotating shifts\n- **Pay:** NHS Band 5 + enhancements\n- **Location:** Belfast Trust sites\n- **Must-haves:** NMC pin, Acute experience, up-to-date training\n- **Apply:** Upload CV + availability.'],
            ['Assistant Accountant — SME Manufacturing','- **Role:** Assistant Accountant\n- **Hours:** Full-time, flex start\n- **Salary:** £28k–£33k DOE\n- **Location:** Antrim\n- **Must-haves:** Part-qualified, Excel, costings experience\n- **Apply:** Send CV + notice period.'],
            ['Warehouse Team Leader — Night Shift','- **Role:** Warehouse Team Leader\n- **Hours:** Sun–Thu, 22:00–06:00\n- **Pay:** £15.25 p/h + shift premium\n- **Location:** Lisburn\n- **Must-haves:** People leadership, MHE, H&S mindset\n- **Apply:** Apply via site form.']
        ];
        foreach ($samples as $s){
            $exists = get_page_by_title($s[0], OBJECT, self::CPT);
            if ($exists) { continue; }
            wp_insert_post([
                'post_type'=>self::CPT,
                'post_status'=>'publish',
                'post_title'=>$s[0],
                'post_content'=>$s[1]
            ]);
        }
    }
    public function shortcode($atts){
        $atts = shortcode_atts(['id'=>0], $atts);
        $post = get_post(intval($atts['id']));
        if (!$post || $post->post_type !== self::CPT) { return ''; }
        $content = wp_kses_post(wpautop($post->post_content));
        return '<div class="ros-job-ad" style="border:1px solid #e6e6e6;padding:16px;border-radius:8px">' . $content . '</div>';
    }
}
new ROS_Ad_Pack();
