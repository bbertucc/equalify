<?php
/**
 * Name: WAVE
 * Description: Counts WCAG 2.1 errors and links to page reports.
 */

/**
 * WAVE Fields
 */
function wave_fields(){

    $wave_fields = array(
        
        // These fields are added to the database.
        'db' => [

                // Meta values.
                'meta' => [
                    array(
                        'name'     => 'wave_key',
                        'value'     => '',
                    )
                ],

                // Pages fields.
                'pages' => [
                    array(
                        'name' => 'wave_wcag_2_1_errors',
                        'type'  => 'VARCHAR(20)'
                    )
                ]
            
        ],

        // These fields are HTML fields on the settings view.
        'settings' => [

            // Meta settings.
            'meta' => [
                array(
                    'name'     => 'wave_key',
                    'label'    => 'WAVE Account Key',
                    'type'     => 'text',
                )
            ]

        ]

    );

    // Return fields
    return $wave_fields;

}

/**
 * WAVE Scans
 */
function wave_scans($url){

    // Add DB and required functions.
    require_once '../config.php';
    require_once '../models/db.php';

    // Get WAVE data.
    $override_https = array(
        "ssl"=>array(
            "verify_peer"=> false,
            "verify_peer_name"=> false,
        )
    );
    $wave_url = 'https://wave.webaim.org/api/request?key='.DataAccess::get_meta_value('wave_key').'&url='.$url;
    $wave_json = file_get_contents($wave_url, false, stream_context_create($override_https));
    $wave_json_decoded = json_decode($wave_json, true);      

    // Fallback if WAVE scan doesn't workm
    if(!empty($wave_json_decoded['status']['error']))
        throw new Exception('WAVE error:"'.$wave_json_decoded['status']['error'].'"');

    // Remove previously saved alerts before creating 
    // alerts because users can do WCAG fixes between
    // scans.
    $alerts_filter = [
        array(
            'name'   =>  'url',
            'value'  =>  $url
        ),
        array(
            'name'   =>  'integration_uri',
            'value'  =>  'wave'
        )
    ];
    DataAccess::delete_alerts($alerts_filter);

    // Get WAVE page errors.
    $wave_errors = $wave_json_decoded['categories']['error']['count'];

    // Set optional alerts.
    if($wave_errors >= 1){
        $site = DataAccess::get_page_site($url);
        DataAccess::add_alert('page', $url, $site, 'wave', 'error', 'WCAG 2.1 page errors found! See <a href="https://wave.webaim.org/report#/'.$page->url.'" target="_blank">WAVE report</a>.');
    }

    // Update page data.
    DataAccess::update_page_data($url, 'wave_wcag_2_1_errors', $wave_errors);
        
}