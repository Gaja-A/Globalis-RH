<?php

namespace Globalis\WP\Test;

define('REGISTRATION_ACF_KEY_LAST_NAME', 'field_64749cfff238e');
define('REGISTRATION_ACF_KEY_FIRST_NAME', 'field_64749d4bf238f');

add_filter('wp_insert_post_data', __NAMESPACE__ . '\\save_auto_title', 99, 2);
add_action('edit_form_after_title', __NAMESPACE__ . '\\display_custom_title_field');
add_action('save_post', __NAMESPACE__ . '\\sendEventMail');

// Send event mail to subscriber
function sendEventMail($post_id) {
    $status = get_post_status($post_id);

    if($status == 'publish') {
        $to = get_post_meta($post_id, 'registration_email');
        
        $event_id = get_post_meta($post_id, 'registration_event_id');
        $event_name = get_the_title( $event_id[0] );
        $event_date = get_post_meta($event_id[0], 'event_date');
        $event_time = get_post_meta($event_id[0], 'event_time');
        $event_pdf_entrance_ticket = get_post_meta($event_id[0], 'event_pdf_entrance_ticket');
        $event_pdf = get_post_meta($event_pdf_entrance_ticket[0], '_wp_attached_file');
        $event_date_year = substr_replace($event_date[0], '-', 4, 0);
        $event_date_format = substr_replace($event_date_year, '-', 7, 0);

        $subject = "Your ".$event_name.' details';
        $file = '../../media/'.$event_pdf[0];
       
        $htmlContent = "Here are the details of your event <strong>".$event_name."</strong>.";
        $htmlContent.= " Your event takes place on ".$event_date_format." at ".$event_time[0];
        $htmlContent.= " Please find enclosed the entrance ticket.";

        $headers = "MIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n"; 
        $message = "Content-Type: text/html; charset=\"UTF-8\"\n" . "Content-Transfer-Encoding: 7bit\n\n" . $htmlContent . "\n\n";  
        
        if(!empty($file) > 0) { 
            if(is_file($file)) { 
                $fp =    @fopen($file,"rb"); 
                $data =  @fread($fp,filesize($file)); 
        
                @fclose($fp); 
                $data = chunk_split(base64_encode($data)); 
                $message .= "Content-Type: application/octet-stream; name=\"".basename($file)."\"\n" .  
                    "Content-Description: ".basename($file)."\n" . 
                    "Content-Disposition: attachment;\n" . " filename=\"".basename($file)."\"; size=".filesize($file).";\n" .  
                    "Content-Transfer-Encoding: base64\n\n" . $data . "\n\n"; 
            } 
        } 
        $mail = @mail($to[0], $subject, $message, $headers);  
    }
}

function save_auto_title($data, $postarr)
{
    if (! $data['post_type'] === 'registrations') {
        return $data;
    }
    if ('auto-draft' == $data['post_status']) {
        return $data;
    }

    if (!isset($postarr['acf'][REGISTRATION_ACF_KEY_LAST_NAME]) || !isset($postarr['acf'][REGISTRATION_ACF_KEY_FIRST_NAME])) {
        return $data;
    }

    $data['post_title'] = "#" . $postarr['ID'] .  " (" . $postarr['acf'][REGISTRATION_ACF_KEY_LAST_NAME] . " " . $postarr['acf'][REGISTRATION_ACF_KEY_FIRST_NAME] . ")";

    $data['post_name']  = wp_unique_post_slug(sanitize_title(str_replace('/', '-', $data['post_title'])), $postarr['ID'], $postarr['post_status'], $postarr['post_type'], $postarr['post_parent']);

    return $data;
}

function display_custom_title_field($post)
{
    if ($post->post_type !== 'registrations' || $post->post_status === 'auto-draft') {
        return;
    }
    ?>
    <h1><?= $post->post_title ?></h1>
    <?php
}
