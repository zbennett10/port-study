<?php 
	// Load Wordpress
	if ( !defined('ABSPATH') ) {
		//If wordpress isn't loaded load it up.
		$path = $_SERVER['DOCUMENT_ROOT'];
		include_once $path . '/wp-load.php';
	}	

	/** 
	 * This file is run via an AJAX request which is configured in an event handler for pdf button on this site and is setup to recieve a JSON, POST request that contains the name of a function
	 * which this script will then run.
	 * **/

	header('Content-Type: application/json');

    $responseToAJAXRequest = array();
	//$portName = "lima-peru";
	
	//if($_POST['portName']) $portName = $_POST['portName'];

	//HARD CODE FOR DEMO - GET RID OF THIS
	$portName = "lima-peru";

    if( !isset($_POST['functionName']) ) { $responseToAJAXRequest['error'] = 'No function name provided!'; }

    if( !isset($responseToAJAXRequest['error']) ) {
        switch($_POST['functionName']) {
            case 'createPortPDF':
               $responseToAJAXRequest['result'] = createPortPDF();
               break;

            default:
               $responseToAJAXRequest['error'] = 'Did not find function '.$_POST['functionName'];
               break;
        }

    }

    echo json_encode($responseToAJAXRequest);

	function findPostBySectionName($sectionName, $posts) {
		global $portName;
		$postCount = count($posts);
		for ($i = 0; $i < $postCount; $i++) {
			$currentPost = $posts[$i];
			$currentPostSectionName = str_replace($portName . '-', "", $currentPost->post_name);
			if($currentPostSectionName === strToLower($sectionName)) return $currentPost;
		}
	}

	function createPortPDF() {
		global $portName;
		global $PORT_STUDY_CATEGORIES;
		$portDisplayName = "Lima, Peru";
		function post_name_filter( $where, $wp_query ) {
					//inject current globals into function call
					global $wpdb;
					global $portName;

					//find posts where name includes the name of the current page - thus linking current port page with posts
					//TODO this must be altered to account for region and subregion pages
					$where .= ' AND ' . $wpdb->posts . '.post_name LIKE \'%' . $portName . '%\'';
					return $where;
				}

		$args = array(
			'post_type' => 'post',
			'posts_per_page' =>  25,
			'post_status' => 'publish',
			'orderby'     => 'date', 
			'order'       => 'ASC'
		);
		//add filter that is eventually used by 'get_posts' wp function call
		add_filter( 'posts_where', 'post_name_filter', 10, 2 );
		$wp_query = new WP_Query($args);
		
		class PortPDF extends TCPDF {
			public function Header() {
				$logo = '/wp-content/uploads/2018/03/NRL_logo_Black_10.jpg';
				$this->Image($logo, 10, 10, 15, '', 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
			}
		}
		
		$pdf = new PortPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

		// set document information
		$pdf->SetCreator('NRLMRY - Port Studies Group');
		$pdf->SetAuthor('Naval Research Lab - Monterey');
		$pdf->SetTitle($portDisplayName . " Port Study");
		$pdf->SetSubject($portDisplayName . ' Port Study');
		$pdf->SetKeywords($portDisplayName . ', Port Study, Naval Research Lab, Monterey, NRL, NRLMRY');

		// set default header data
		$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, $portDisplayName.' Port Study', 'by Naval Research Lab, Monterey - Port Studies Group');

		// set header and footer fonts
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		// set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

		// set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		// set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);


		// set some language-dependent strings (optional)
		if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
			require_once(dirname(__FILE__).'/lang/eng.php');
			$pdf->setLanguageArray($l);
		}

		//add port title page
		$pdf->SetFont('times', 'B', 20);
		$pdf->AddPage();
		$titlePageHTML = '<div style="line-height: 10;"><span style="text-align: center; font-size: 60px;">' . $portDisplayName . '</span></div>
						  <div style="line-height: -3;"><span style="text-align: center; font-size: 60px;">Port Study' . '</span></div>';
		$pdf->writeHTML($titlePageHTML, true, false, true, false, '');

		$posts = $wp_query->posts;
		$portStudyCategoryCount = count($PORT_STUDY_CATEGORIES);
		$totalSectionPagesRendered = 0;

		for ($i = 0; $i < $portStudyCategoryCount; $i++) {
			//add category page
			$currentCategory = $PORT_STUDY_CATEGORIES[$i];
			$pageNumber = $i + 2;
			
			$pdf->SetFont('times', 'B', 28);
			$pdf->AddPage();
			$pdf->Bookmark($currentCategory['name'], 0, 0, '', 'B', array(0,64,128));
			$pdf->Cell(0, 10, $currentCategory['name'], 'B', 1, 'L');
			$pdf->Ln(1);
			
			$subSectionCount = count($currentCategory['subHeadings']);
			for($j = 0; $j < $subSectionCount; $j++) {
				$pageOffset = $j + 1;
				if($totalSectionPagesRendered > 0) $pageOffset += $totalSectionPagesRendered;
				//generate table of contents on this page
				$currentSection = $currentCategory['subHeadings'][$j];
				$sectionLink = $pdf->AddLink();
				$pdf->SetLink($sectionLink, 0, $pageNumber + $pageOffset);
				$pdf->SetFont('times', 'N', 16);
				$pdf->setCellPaddings( 5, 0, 0, 0);
				$pdf->Cell(0, 10, $currentSection, 0, 1, 'L', false, $sectionLink);
			}
			//revert cell padding
			$pdf->setCellPaddings( 0, 0, 0, 0);

		
			for($k = 0; $k < $subSectionCount; $k++) {
				//add section
				$pdf->SetFont('times', 'B', 20);
				$currentSection = $currentCategory['subHeadings'][$k];
				$pdf->AddPage();
				$totalSectionPagesRendered++;
				$pdf->Bookmark($currentSection, 1, 0, '', '', array(0, 64, 128));
				$pdf->Cell(0, 10, $currentSection, 0, 1, 'L');
				
				$post = findPostBySectionName(strToLower(join('-', explode(" ", $currentSection))), $posts);

				$pdf->SetFont('times', 'N', 12);
				$pdf->Write(0, $post->post_content);
// 				$pdf->writeHTML($post->post_content, true, false, true, false, ''); //images should be embedded in posts as <img/> tags so we need to writeHTML
				$attachments= get_attached_media( 'image', $post->ID );
				foreach($attachments as $att_id => $attachment) {
					$imageURL = wp_get_attachment_url($attachment->ID);
					$pdf->writeHTML('<br pagebreak="true"/>', true, false, true, false, '');
					//increment page number for links
					$totalSectionPagesRendered++;
					$pdf->Image($imageURL, 0, 25, 200, 250, 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
				}
			}
		}		

		// add a new page for TOC
		$pdf->addTOCPage();
		$pdf->SetFont('times', 'B', 16);
		$pdf->MultiCell(0, 0,'Table Of Contents', 0, 'C', 0, 1, '', '', true, 0);
		$pdf->Ln();

		$pdf->SetFont('dejavusans', '', 12);
		$pdf->addTOC(2, 'times', '.', 'Top', 'B', array(0,0,0));
		$pdf->endTOCPage();

		//Close and output PDF document
		$fileName = __DIR__ . "/" . $portName . ".pdf";
		$pdf->Output($fileName, 'F');
		return $fileName;
	}
?>