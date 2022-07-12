<?php

/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * We use this document to process a site, so it's ready 
 * to be delivered to integrations. 
 * 
 * As always, we must remember that every function should 
 * be designed to be as effcient as possible so that 
 * Equalify works for everyone.
**********************************************************/

/**
 * Process Sites
 */
function process_sites(){

    // Let's log our process for the CLI.
    echo "\n\n\n> Processing sites...";

    // We don't know where helpers are being called, so 
    // we must set the directory if it isn't already set.
    if(!defined('__ROOT__'))
        define('__ROOT__', dirname(dirname(__FILE__)));

    // We'll use the directory to include required files.
    require_once(__ROOT__.'/config.php');
    require_once(__ROOT__.'/models/db.php');
    require_once(__ROOT__.'/models/adders.php');

    // The main purpose of this process is to declare the 
    // 'scanable_pages' meta, which may have been created.
    $scanable_pages = unserialize(
        DataAccess::get_meta_value('scanable_pages')
    );
    if(empty($scanable_pages)){

        // This must be an array so we can properly us it
        // again from the db.
        $scanable_pages = array();

    }

    // We only run this process on active sites.
    $filtered_to_active_sites = array(
        array(
            'name' => 'status',
            'value' => 'active'
        )
    );
    $active_sites = DataAccess::get_db_entries( 'sites',
        $filtered_to_active_sites
    )['content'];

    // Log our progress for CLI.
    $active_sites_count = count($active_sites);
    echo "\n> $active_sites_count active site";
    if($active_sites_count > 1 ){
        echo 's';
    }
    echo ':';

    // We run this process if there are sites ready to
    // process.
    if(!empty($active_sites)){

        // Each site is processed individually.
        foreach($active_sites as $site){

            // Log our progress.
            echo "\n>>> Processing \"$site->url\".";

            // Processing a site means adding its 
            // site_pages as scanable_pages meta.
            if($site->type == 'single_page'){
                $site_pages = single_page_adder(
                    $site->url
                );
            }
            if($site->type == 'xml'){
                $site_pages = xml_site_adder(
                    $site->url
                );
            }
            if($site->type == 'wordpress'){
                $site_pages = wordpress_site_adder(
                    $site->url
                );
            }
            foreach ($site_pages as $page){
                array_push($scanable_pages, $page);        
            }

        }

        // When we're done we push the scannable_pages
        // to the DB.
        DataAccess::update_meta_value( 'scanable_pages', 
            serialize($scanable_pages)
        );

        // Finally, let's log our progress for CLIs.
        $pages_count = count($scanable_pages);
        echo "\n> Found $pages_count scanable pages.";
        
    }

}