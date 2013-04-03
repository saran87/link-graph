<?php

 	
 	ini_set('display_errors', 1);
	ini_set('allow_url_fopen', 1);

	// mine
	require_once('ScrapperLib.php');
	// somebody else's
	require_once('PorterStemmer.class.php');
	require_once('Cloud.class.php');

	/**
    * Link Scrapper class to scrap the url content and seperate the links 
    * and words
    * 
    * Usage:
    * 
    *  $linkScrapper = new LinkScrapper($url);
    *  $linkScrapper->ScrapIt();
    */
	class LinkScrapper
	{

		/**
        * URL for scrapping
        * @var $url - string
        */

		private $url;

		function __construct($url)
		{	
			$this->url = $url;
		}

		/**
        * ScrapIt - scarps the url
        * @return An associative array containing links, title  and its category(word,link,file)
        */

		function ScrapIt(){
			// Create an array of words that we want to exclude from our results
			$stop_words = "a,able,about,above,according,accordingly,across,actually,after,afterwards,again,against,ain't,all,allow,allows,almost,alone,along,already,also,although,always,am,among,amongst,an,and,another,any,anybody,anyhow,anyone,anything,anyway,anyways,anywhere,apart,appear,appreciate,appropriate,are,aren't,around,as,aside,ask,asking,associated,at,available,away,awfully,be,became,because,become,becomes,becoming,been,before,beforehand,behind,being,believe,below,beside,besides,best,better,between,beyond,both,brief,but,by,c'mon,c's,came,can,can't,cannot,cant,cause,causes,certain,certainly,changes,clearly,co,com,come,comes,concerning,consequently,consider,considering,contain,containing,contains,corresponding,could,couldn't,course,currently,definitely,described,despite,did,didn't,different,do,does,doesn't,doing,don't,done,down,downwards,during,each,edu,eg,eight,either,else,elsewhere,enough,entirely,especially,et,etc,even,ever,every,everybody,everyone,everything,everywhere,ex,exactly,example,except,far,few,fifth,first,five,followed,following,follows,for,former,formerly,forth,four,from,further,furthermore,get,gets,getting,given,gives,go,goes,going,gone,got,gotten,greetings,had,hadn't,happens,hardly,has,hasn't,have,haven't,having,he,he's,hello,help,hence,her,here,here's,hereafter,hereby,herein,hereupon,hers,herself,hi,him,himself,his,hither,hopefully,how,howbeit,however,i'd,i'll,i'm,i've,ie,if,ignored,immediate,in,inasmuch,inc,indeed,indicate,indicated,indicates,inner,insofar,instead,into,inward,is,isn't,it,it'd,it'll,it's,its,itself,just,keep,keeps,kept,know,knows,known,last,lately,later,latter,latterly,least,less,lest,let,let's,like,liked,likely,little,look,looking,looks,ltd,mainly,many,may,maybe,me,mean,meanwhile,merely,might,more,moreover,most,mostly,much,must,my,myself,name,namely,nd,near,nearly,necessary,need,needs,neither,never,nevertheless,new,next,nine,no,nobody,non,none,noone,nor,normally,not,nothing,novel,now,nowhere,obviously,of,off,often,oh,ok,okay,old,on,once,one,ones,only,onto,or,other,others,otherwise,ought,our,ours,ourselves,out,outside,over,overall,own,particular,particularly,per,perhaps,placed,please,plus,possible,presumably,probably,provides,que,quite,qv,rather,rd,re,really,reasonably,regarding,regardless,regards,relatively,respectively,right,said,same,saw,say,saying,says,second,secondly,see,seeing,seem,seemed,seeming,seems,seen,self,selves,sensible,sent,serious,seriously,seven,several,shall,she,should,shouldn't,since,six,so,some,somebody,somehow,someone,something,sometime,sometimes,somewhat,somewhere,soon,sorry,specified,specify,specifying,still,sub,such,sup,sure,t's,take,taken,tell,tends,th,than,thank,thanks,thanx,that,that's,thats,the,their,theirs,them,themselves,then,thence,there,there's,thereafter,thereby,therefore,therein,theres,thereupon,these,they,they'd,they'll,they're,they've,think,third,this,thorough,thoroughly,those,though,three,through,throughout,thru,thus,to,together,too,took,toward,towards,tried,tries,truly,try,trying,twice,two,un,under,unfortunately,unless,unlikely,until,unto,up,upon,us,use,used,useful,uses,using,usually,value,various,very,via,viz,vs,want,wants,was,wasn't,way,we,we'd,we'll,we're,we've,welcome,well,went,were,weren't,what,what's,whatever,when,whence,whenever,where,where's,whereafter,whereas,whereby,wherein,whereupon,wherever,whether,which,while,whither,who,who's,whoever,whole,whom,whose,why,will,willing,wish,with,within,without,won't,wonder,would,would,wouldn't,yes,yet,you,you'd,you'll,you're,you've,your,yours,yourself,yourselves,zero";
			$stop_words = explode(',',$stop_words); 
			$excluded_words = array('wordsWeDoNotWant');

			//1-calling function which returns specified url(web page) content in an array
			$webPage = ScrapperLib::get_web_page($path = $this->url);
			$page = $webPage['content'];
			$linkArray["name"] = $this->url;
			$linkArray["children"] = ScrapperLib::extract_links($page,$this->url);
			
			$linkArray["children"] = array_splice($linkArray["children"],0,20);

			//filter the link list for the files
			$fileArray = array_filter($linkArray["children"],function($value){
				$link = strtolower($value["link"]);
				$url = parse_url($link);
				
				if(isset($url["host"]))
					$link = str_replace($url["host"],"",$link);
					$link = preg_replace(array("/(?:f|ht)tps?/","/:/","/\?(.*)/"),"",$link);
					$webExtension = array(".jsp",".php",".aspx",".asp",".html",".xhtml",".htm",".cfm",".do",".com","mailto",".py");
				
					foreach($webExtension as $key=>$extension){
					
						if(strpos($link,$extension) !== false){
							//echo $link . "---" . $extension;
							return false;
						}
					}

					if($link){
						//echo $link . "<br/>";
						if(strpos($link,".") !== false){
							if(!preg_match("/(\.|\/)$/",$link)){
								return true;
							}
						}
					}
					return false;
			});

			$linkArray["files"] = $fileArray;

			//2-sanitize the string by removing html tags,numbers,punctuation marks,encoding string to utf8 format and convertingthe whole string to lower case letters.
			$page = ScrapperLib::strip_html_tags($page);

			$page = html_entity_decode($page,ENT_QUOTES,'utf-8');
			$page = ScrapperLib::strip_punctuation($page);
			$page = ScrapperLib::strip_numbers($page);
			$page = ScrapperLib::strip_symbols($page);
			$page = strtolower( $page);
			
			//3-spliting the string into array of words using the pattern one or more spaces
			$words = preg_split('/\s+/', $page );
			
			//4-Contents of arrays stop_Words and excluded_words are filtered/removed from the array of words  
			$words = array_diff($words, $stop_words);
			$words = array_diff($words, $excluded_words);
			/*
			//6-Words in the array are counted to from a new array with elminated duplicate words, word as key and count as value.
			$wordcounts = array();
			foreach( $words as $word ){
			  if ( strlen( $word ) > 1 ) {
			    if ( !array_key_exists( $word, $wordcounts ) )
			      $wordcounts[ $word ] = 0;
			    $wordcounts[ $word ] += 1;
			  }
			}
			arsort( $wordcounts, SORT_NUMERIC );
			display_array($wordcounts);*/
			//7-Find counts of maxmimum occurance word and minimum occurance word. 
			// Ratio of 18 to range (max - min) is found to distribute the weight of a word from 1-18,which helps in deciding fontsize of a word
			/*$min = 1000000;
			$max = -1000000;
			foreach( $wordcounts as $word => $count ) {
			  if ( $count > $max )
			    $max = $count;
			  else if ( $count < $min )
			    $min = $count;
			}
		
			//to avoid divide by zero warnign in line 101
			if($max == $min) $max++;
			$ratio = 18.0 / ( $max - $min );
	
			// 8 - Create a new array with the keys of wordcounts array and the created array is sorted.
			// Then each word in the sorted array is printed with fontsize ranging from 9 to 27 calculated using ratio and number of occurance
			// each word is linked to its wikipedia page 
			/*echo "<div style='width:600px;'>\n";
			$wc = array_keys( $wordcounts );
			sort( $wc );*/
			/*
			foreach( $words as $word ) {
				if ( strlen( $word ) > 1 ){
					$link["link"] = "http://en.wikipedia.org/wiki/" . $word;
					$link["name"] = $word;
					$link["type"] = "words";
					//$linkArray[] = $link;
				}
			}

			// 9 - An object for PTagCloud is by calling it constructer with number of top list words to displayed as a parameter
			// The word string is splitted with space to from an array of words and added to the cloud object
			// The width of words to displayed on the screen is set using the seWidth method in PTagCloud class.
			// The top words are printed to the screen by printing the string returned by emitCloud methos in cloud object
			/*$cloud = new PTagCloud(50);
			$text_content = implode(" ",$words);
			$cloud->addTagsFromText($text_content);
			$cloud->setWidth("500px");
			echo $cloud->emitCloud();*/
			return $linkArray;
		}
	}
?>