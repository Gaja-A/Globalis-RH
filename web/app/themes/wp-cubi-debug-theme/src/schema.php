<?php

require_once '../../../vendor/autoload.php';

use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Common\Entity\Row;

add_action('init', __NAMESPACE__ . '\\register_post_type_event', 10);
add_action('init', __NAMESPACE__ . '\\register_post_type_registration', 10);

function register_post_type_event()
{
    $args = [
        'hierarchical'        => false,
        'public'              => true,
        'exclude_from_search' => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_nav_menus'   => false,
        'show_in_admin_bar'   => false,
        'menu_position'       => 25,
        'menu_icon'           => 'dashicons-calendar-alt',
        'capability_type'     => 'post',
        // 'capabilities'        => [],
        // 'map_meta_cap'        => false,
        'supports'            => ['title'],
        'has_archive'         => false,
        'rewrite'             => ['slug' => 'events', 'pages' => true, 'feeds' => false, 'with_front' => false],
        'query_var'           => false,
        // Extended
        'show_in_feed'         => false,
        'quick_edit'           => true,
        'dashboard_glance'     => true,
        'enter_title_here'     => null,
        'featured_image'       => null,
        'site_filters'         => null,
        'site_sortables'       => null,
        'archive'              => null,
        'admin_cols'           => [
            'event-date' => ['title' => 'Event date', 'sortable' => false, 'function' => function () {
                global $post;
                $event_date = get_field('event_date', $post);
                $event_time = get_field('event_time', $post);
                if (empty($event_date) || empty($event_time)) {
                    echo "&mdash;";
                    return;
                }
                echo $event_date . ' ' . $event_time;
            }],
            'registrations' => ['title' => 'Registrations', 'sortable' => false, 'function' => function () {
                global $post;
                global $wpdb;
                $sql_query = $wpdb->prepare("SELECT COUNT(`post_id`) as count FROM %i WHERE `meta_key` = 'registration_event_id' AND `meta_value` = %d", $wpdb->postmeta, $post->ID);
                $result = $wpdb->get_row($sql_query, ARRAY_A);
                echo $result['count'];
            }],
            'export' => ['title' => 'Export', 'sortable' => false, 'function' => function () {
                global $post;
                global $wpdb;
                
                $sql_query = $wpdb->prepare("SELECT GROUP_CONCAT(post_id) AS 'post_id' FROM %i WHERE `meta_value` = %d", $wpdb->postmeta, $post->ID);
                $post_ids = $wpdb->get_var($sql_query);

                $cols = ['registration_last_name', 'registration_first_name', 'registration_email', 'registration_phone'];
                $query =" SELECT post_id ";
                foreach($cols as $col) {
                    $query.=" , MAX(IF(meta_key='".$col."', meta_value, '')) AS '".$col."'";
                }
                $query.=" FROM %i ";
                $query.=" WHERE `post_id` IN (".$post_ids.") ";
                $query.=" AND  meta_key IN ('".implode("', '", $cols)."') ";
                $query.=" GROUP BY post_id ";
                $sql_query = $wpdb->prepare($query, $wpdb->postmeta);
                $result = $wpdb->get_results($sql_query);

                try {
                    $filePath = 'uploads/export_'.$post->ID.'.xlsx';
                    $writer = WriterEntityFactory::createXLSXWriter();
                    $writer->openToFile($filePath);

                    // Header
                    $header = [
                        ['Nom', 'Prénom', 'Email', 'Téléphone'],
                    ];
                    foreach ($header as $h) {
                        $head = [
                            WriterEntityFactory::createCell($h[0]),
                            WriterEntityFactory::createCell($h[1]),
                            WriterEntityFactory::createCell($h[2]),
                            WriterEntityFactory::createCell($h[3]),
                        ];
                        $singleRow = WriterEntityFactory::createRow($head);
                        $writer->addRow($singleRow);
                    }

                    // Data
                    foreach($result as $row) {
                        $data = [
                            [$row->registration_last_name, $row->registration_first_name, $row->registration_email, $row->registration_phone],
                        ];
                        foreach ($data as $d) {
                            $cells = [
                                WriterEntityFactory::createCell($d[0]),
                                WriterEntityFactory::createCell($d[1]),
                                WriterEntityFactory::createCell($d[2]),
                                WriterEntityFactory::createCell($d[3]),
                            ];
                            $singleRow = WriterEntityFactory::createRow($cells);
                            $writer->addRow($singleRow);
                        }
                    }
                    $writer->close();
                } catch(Exception $e) {
                    error_log($e->getMessage());
                }
                echo '<a class="button" href="uploads/export_'.$post->ID.'.xlsx" download>Export</a>';
            }],
        ],
        'admin_filters'        => [],
    ];

    $names = [
        'singular' => 'Event',
        'plural'   => 'Events',
        'slug'     => 'event',
    ];

    register_extended_post_type("events", $args, $names);
}

function register_post_type_registration()
{
    $args = [
        'hierarchical'        => false,
        'public'              => true,
        'exclude_from_search' => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_nav_menus'   => false,
        'show_in_admin_bar'   => false,
        'menu_position'       => 30,
        'menu_icon'           => 'dashicons-tickets',
        'capability_type'     => 'post',
        // 'capabilities'        => [],
        // 'map_meta_cap'        => false,
        'supports'            => false,
        'has_archive'         => false,
        'rewrite'             => ['slug' => 'registrations', 'pages' => true, 'feeds' => false, 'with_front' => false],
        'query_var'           => false,
        // Extended
        'show_in_feed'         => false,
        'quick_edit'           => true,
        'dashboard_glance'     => true,
        'enter_title_here'     => null,
        'featured_image'       => null,
        'site_filters'         => null,
        'site_sortables'       => null,
        'archive'              => null,
        'admin_cols'           => [
            'event' => ['title' => 'Event', 'sortable' => false, 'function' => function () {
                global $post;
                $registration_event_id = get_field('registration_event_id', $post);
                if (empty($registration_event_id)) {
                    echo "&mdash;";
                    return;
                }
                $event = get_post($registration_event_id);
                if (empty($event) || 'events' !== get_post_type($registration_event_id)) {
                    echo "&mdash;";
                    return;
                }
                ?>
                <a href="<?= get_edit_post_link($registration_event_id) ?>"><?= get_the_title($registration_event_id) ?></a>
                <?php
            }],
        ],
        'admin_filters'        => [],
    ];

    $names = [
        'singular' => 'Registration',
        'plural'   => 'Registrations',
        'slug'     => 'registration',
    ];

    register_extended_post_type("registrations", $args, $names);
}