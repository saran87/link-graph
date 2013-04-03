<?php
// <= PHP 5
$q = $_GET["q"];

$q = addhttp($q);

$htmlContent = file_get_contents($q, true);

$doc = new DOMDocument();
$doc->loadHTML($htmlContent);

$anchors = $doc->getElementsByTagName('a');
print_r($anchors);
$count = 0;
$linkArray = [];
foreach ($anchors as $key => $node) {
	# code...
	$text = getTextContent($node);
	if($node->hasAttributes() ){
			$href = getLink($node->attributes);
			$href = getFullUrl ($href,$q);
			$str  =  "<a href=$href>" . $text . "</a><br/>";
			$linkArray[] = $str;
			$count++;
	}

}

echo "count :" . $count;

print_r($linkArray);


function getTextContent($node){

	if( $node->textContent != ""){
		return $node->textContent;
	}
	else{
		if($node->hasChildNodes()){
			foreach ($node->childNodes as $key => $childNode) {
				# code...
				getText($childNode);
			}
		}
		else{
			return "test";
		}	
	}

}

function getLink($attributes){


	foreach ($attributes as $key => $attr) {
		# code...
		if($attr->name == "href")
			return $attr->value;
		
	}
}

function getFullUrl($url,$baseURL){

	if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
       	if(!preg_match("~^/~i", $url)){
       		$url = "/" . $url;
       	}
       	$url = $baseURL . $url;
    }

    return $url;
}

function addhttp($url) {
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) { 
       		$url = "http://" . $url;
    }
    return $url;
}
?>