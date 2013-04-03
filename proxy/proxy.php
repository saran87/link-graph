<?php

//get the query
$q = isset($_GET["q"]) ? $_GET["q"] : "";

//if query is specified process it otherwise return error message
if($q){
	//append http:// to the url if not specfied in the url
	$q = addHttp($q);
	//include link_graph php file
	require_once('graph/link_graph.php');
	//Create link scrapper object with url 
	$linkScrapper = new LinkScrapper($q);

	//scrap the link and encode the returned array to json
	ouputJson($linkScrapper->ScrapIt());

}else{
	ouputJson(array("error"=>"link not specified"));
}



/**
 * ouputJson
 * output json to response
 * @param $data array of data with key/value to be ouput as json
 * @return void
 */

function ouputJson ($data){
	
	header("Expires: Mon, 26 Jul 2017 05:00:00 GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	//MUST change the content-type
	header("Content-Type:application/json");
	// This will become the response value for the XMLHttpRequest object
	echo json_encode($data);
}


function addHttp($url) {
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) { 
       		$url = "http://" . $url;
    }
    return $url;
}
?>