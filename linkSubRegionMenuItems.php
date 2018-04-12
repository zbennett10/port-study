<?php 
	/**
	 * This script grabs menu items for subregions and links them to their correspoding subregion page - updating them in the DB.
	 **/
	
	// Load Wordpress
	if ( !defined('ABSPATH') ) {
		//If wordpress isn't loaded load it up.
		$path = $_SERVER['DOCUMENT_ROOT'];
		include_once $path . '/wp-load.php';
	}	
	
	/**
	 * Uncomment the line below to enable function to run when a GET request is made to this php file.
	 * */
	linkSubRegionMenuItems();
	
	
	function linkSubRegionMenuItems() {
        global $PORT_STUDY_SUBREGIONS;
        $portMenuItems = wp_get_nav_menu_items("Top Level");
		highlight_string("<?php\n\$data =\n" . var_export($portMenuItems) . ";\n?>");
		
		foreach($PORT_STUDY_SUBREGIONS as $subregionObj) {
		$subregion = (array) $subregionObj;
		$subregionTitle = $subregion["name"];
        $parentRegionName = strtolower($subregion["parentId"]);

        //get sub region menu item


        //get sub region page
        $subRegionPageTitle = ucwords(str_replace("-", " ", $parentRegionName)) . " - " . $subregionTitle,
		$subRegionPage = (array) get_page_by_title($subRegionPageTitle);
		$subRegionPageID = $subRegionPage["ID"];
			
		// $subregionPage = array(
		// 		   'post_title'     => ucwords(str_replace("-", " ", $parentRegionName)) . " - " . $subregionTitle,
        //            'post_type'      => 'page',
        //            'post_name'      => $subregionPageName,
        //            'post_content'   => '',
		// 		   'post_parent'    => $parentRegionPageID,
        //            'post_status'    => 'publish',
        //            'comment_status' => 'closed',
        //            'ping_status'    => 'closed',
        //            'post_author'    => 1,
        //            'menu_order'     => 0
		// );
		
		//wp_insert_post($subregionPage);
		}
    }
	
	
?>