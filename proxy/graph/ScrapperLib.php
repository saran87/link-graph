<?php

	/**
    * ScrapperLib - Contains supporting methods for Link scrapping
    * 
    * 
    * Usage:
    * 
    *  $ScrapperLib::MethodName()
    * 
    */

	 /**
	 * Copyright (c) 2008, David R. Nadeau, NadeauSoftware.com.
	 * All rights reserved.
	 *
	 * Redistribution and use in source and binary forms, with or without
	 * modification, are permitted provided that the following conditions
	 * are met:
	 *
	 *	* Redistributions of source code must retain the above copyright
	 *	  notice, this list of conditions and the following disclaimer.
	 *
	 *	* Redistributions in binary form must reproduce the above
	 *	  copyright notice, this list of conditions and the following
	 *	  disclaimer in the documentation and/or other materials provided
	 *	  with the distribution.
	 *
	 *	* Neither the names of David R. Nadeau or NadeauSoftware.com, nor
	 *	  the names of its contributors may be used to endorse or promote
	 *	  products derived from this software without specific prior
	 *	  written permission.
	 *
	 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
	 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
	 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
	 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
	 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
	 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
	 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
	 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
	 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
	 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY
	 * WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY
	 * OF SUCH DAMAGE.
	 */

	/*
	 * This is a BSD License approved by the Open Source Initiative (OSI).
	 * See:  http://www.opensource.org/licenses/bsd-license.php
	 */

	class ScrapperLib
	{
		/**
		 * Get a file on the web.  The file may be an (X)HTML page, an image, etc.
		 * Return an associative array containing the page header, contents,
		 * and HTTP status code.
		 *
		 * Values in the returned array are as defined by the CURL curl_getinfo()
		 * function, and include:
		 *
		 * 	"url"		the last effective URL after redirects
		 * 	"http_code"	the last error/status code
		 * 	"content_type"	the content type from the header
		 *
		 * This function adds a few more:
		 *
		 * 	"content"	the page content (text, image, etc.)
		 * 	"errno"		the CURL error code
		 * 	"errmsg"	the CURL error message
		 *
		 * On success, "errno" is 0, "http_code" is 200, and "content" has the
		 * web page.
		 *
		 * On an error with the URL, such as a redirect limit, or timeout,
		 * "errno" will be non-zero and "errmsg" will contain an error message.
		 * There other fields will be missing.
		 *
		 * On an error with the web site, such as a missing page, no permissions,
		 * or no service, "errno" will be 0, "http_code" will be the HTTP error
		 * code, and "content" will be missing.
		 *
		 * Parameters:
		 * 	url		the URL of the page to get
		 *
		 * Return values:
		 * 	An associative array containing the page text and error codes,
		 * 	as described above.
		 *
		 * See also:
		 *	http://nadeausoftware.com/articles/2007/06/php_tip_how_get_web_page_using_curl
		 */
		public static function get_web_page( $url )
		{
			$options = array(
				CURLOPT_RETURNTRANSFER => true,     // return web page
				CURLOPT_HEADER         => false,    // don't return headers
				CURLOPT_FOLLOWLOCATION => true,     // follow redirects
				CURLOPT_ENCODING       => "",       // handle compressed
				CURLOPT_USERAGENT      => "spider", // who am i
				CURLOPT_AUTOREFERER    => true,     // set referer on redirect
				CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
				CURLOPT_TIMEOUT        => 120,      // timeout on response
				CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
			);

			$ch      = curl_init( $url );
			curl_setopt_array( $ch, $options );
			$content = curl_exec( $ch );
			$err     = curl_errno( $ch );
			$errmsg  = curl_error( $ch );
			$header  = curl_getinfo( $ch );
			curl_close( $ch );

			$header['errno']   = $err;
			$header['errmsg']  = $errmsg;
			$header['content'] = $content;
			return $header;
		}

		/**
		 * Strip out (X)HTML tags and invisible content.  This function
		 * is useful as a prelude to tokenizing the visible text of a page
		 * for use in a search engine or spam detector/remover.
		 *
		 * Unlike PHP's built-in strip_tags() function, this function will
		 * remove invisible parts of a web page that normally should not be
		 * indexed or passed through a spam filter.  This includes style
		 * blocks, scripts, applets, embedded objects, and everything in the
		 * page header.
		 *
		 * In anticipation of tokenizing the visible text, this function
		 * detects (X)HTML block tags (such as divs, paragraphs, and table
		 * cells) and inserts a carriage return before each one.  This
		 * insures that after tags are removed, words before and after the
		 * tag are not erroneously joined into a single word.
		 *
		 * Parameters:
		 * 	text		the (X)HTML text to strip
		 *
		 * Return values:
		 * 	the stripped text
		 *
		 * See:
		 * 	http://nadeausoftware.com/articles/2007/09/php_tip_how_strip_html_tags_web_page
		 */
		public static function strip_html_tags( $text )
		{
			// PHP's strip_tags() function will remove tags, but it
			// doesn't remove scripts, styles, and other unwanted
			// invisible text between tags.  Also, as a prelude to
			// tokenizing the text, we need to insure that when
			// block-level tags (such as <p> or <div>) are removed,
			// neighboring words aren't joined.
			$text = preg_replace(
				array(
					// Remove invisible content
					'@<head[^>]*?>.*?</head>@siu',
					'@<style[^>]*?>.*?</style>@siu',
					'@<script[^>]*?.*?</script>@siu',
					'@<object[^>]*?.*?</object>@siu',
					'@<embed[^>]*?.*?</embed>@siu',
					'@<applet[^>]*?.*?</applet>@siu',
					'@<noframes[^>]*?.*?</noframes>@siu',
					'@<noscript[^>]*?.*?</noscript>@siu',
					'@<noembed[^>]*?.*?</noembed>@siu',

					// Add line breaks before & after blocks
					'@<((br)|(hr))@iu',
					'@</?((address)|(blockquote)|(center)|(del))@iu',
					'@</?((div)|(h[1-9])|(ins)|(isindex)|(p)|(pre))@iu',
					'@</?((dir)|(dl)|(dt)|(dd)|(li)|(menu)|(ol)|(ul))@iu',
					'@</?((table)|(th)|(td)|(caption))@iu',
					'@</?((form)|(button)|(fieldset)|(legend)|(input))@iu',
					'@</?((label)|(select)|(optgroup)|(option)|(textarea))@iu',
					'@</?((frameset)|(frame)|(iframe))@iu',
				),
				array(
					' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
					"\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0",
					"\n\$0", "\n\$0",
				),
				$text );

			// Remove all remaining tags and comments and return.
			return strip_tags( $text );
		}

		/**
		 * Strip numbers and number-related characters from UTF-8 text.
		 *
		 * Characters stripped from the text include all digits, currency symbols,
		 * and periods or commas surrounded by digits.  Fractions and supercripts
		 * are removed, along with roman numerals (if they use the special Unicode
		 * characters).  Letters, punctuation, and other symbols are left as-is.
		 *
		 * Parameters:
		 * 	text		the UTF-8 text to strip
		 *
		 * Return values:
		 * 	the stripped UTF-8 text.
		 *
		 * See also:
		 *	http://nadeausoftware.com/articles/2007/10/php_tip_how_strip_numbers_web_page
		 */
		public static function strip_numbers( $text )
		{
			$urlchars      = '\.,:;\'=+\-_\*%@&\/\\\\?!#~\[\]\(\)';
			$notdelim      = '\p{L}\p{M}\p{N}\p{Pc}\p{Pd}' . $urlchars;
			$predelim      = '((?<=[^' . $notdelim . '])|^)';
			$postdelim     = '((?=[^'  . $notdelim . '])|$)';
			 
			$fullstop      = '\x{002E}\x{FE52}\x{FF0E}';
			$comma         = '\x{002C}\x{FE50}\x{FF0C}';
			$arabsep       = '\x{066B}\x{066C}';
			$numseparators = $fullstop . $comma . $arabsep;
			$plus          = '\+\x{FE62}\x{FF0B}\x{208A}\x{207A}';
			$minus         = '\x{2212}\x{208B}\x{207B}\p{Pd}';
			$slash         = '[\/\x{2044}]';
			$colon         = ':\x{FE55}\x{FF1A}\x{2236}';
			$units         = '%\x{FF05}\x{FE64}\x{2030}\x{2031}';
			$units        .= '\x{00B0}\x{2103}\x{2109}\x{23CD}';
			$units        .= '\x{32CC}-\x{32CE}';
			$units        .= '\x{3300}-\x{3357}';
			$units        .= '\x{3371}-\x{33DF}';
			$units        .= '\x{33FF}';
			$percents      = '%\x{FE64}\x{FF05}\x{2030}\x{2031}';
			$ampm          = '([aApP][mM])';
			  
			$digits        = '[\p{N}' . $numseparators . ']+';
			$sign          = '[' . $plus . $minus . ']?';
			$exponent      = '([eE]' . $sign . $digits . ')?';
			$prenum        = $sign . '[\p{Sc}#]?' . $sign;
			$postnum       = '([\p{Sc}' . $units . $percents . ']|' . $ampm . ')?';
			$number        = $prenum . $digits . $exponent . $postnum;
			$fraction      = $number . '(' . $slash . $number . ')?';
			$numpair       = $fraction . '([' . $minus . $colon . $fullstop . ']' . $fraction . ')*';

			return preg_replace(
				array(
				// Match delimited numbers
					'/' . $predelim . $numpair . $postdelim . '/u',
				// Match consecutive white space
					'/ +/u',
				),
				' ',
				$text );
		}

		/**
		 * Strip punctuation characters from UTF-8 text.
		 *
		 * Characters stripped from the text include characters in the following
		 * Unicode categories:
		 *
		 * 	Separators
		 * 	Control characters
		 *	Formatting characters
		 *	Surrogates
		 *	Open and close quotes
		 *	Open and close brackets
		 *	Dashes
		 *	Connectors
		 *	Numer separators
		 *	Spaces
		 *	Other punctuation
		 *
		 * Exceptions are made for punctuation characters that occur withn URLs
		 * (such as [ ] : ; @ & ? and others), within numbers (such as . , % # '),
		 * and within words (such as - and ').
		 *
		 * Parameters:
		 * 	text		the UTF-8 text to strip
		 *
		 * Return values:
		 * 	the stripped UTF-8 text.
		 *
		 * See also:
		 * 	http://nadeausoftware.com/articles/2007/9/php_tip_how_strip_punctuation_characters_web_page
		 */
		public static function strip_punctuation( $text )
		{
			$urlbrackets    = '\[\]\(\)';
			$urlspacebefore = ':;\'_\*%@&?!' . $urlbrackets;
			$urlspaceafter  = '\.,:;\'\-_\*@&\/\\\\\?!#' . $urlbrackets;
			$urlall         = '\.,:;\'\-_\*%@&\/\\\\\?!#' . $urlbrackets;

			$specialquotes = '\'"\*<>';

			$fullstop      = '\x{002E}\x{FE52}\x{FF0E}';
			$comma         = '\x{002C}\x{FE50}\x{FF0C}';
			$arabsep       = '\x{066B}\x{066C}';
			$numseparators = $fullstop . $comma . $arabsep;

			$numbersign    = '\x{0023}\x{FE5F}\x{FF03}';
			$percent       = '\x{066A}\x{0025}\x{066A}\x{FE6A}\x{FF05}\x{2030}\x{2031}';
			$prime         = '\x{2032}\x{2033}\x{2034}\x{2057}';
			$nummodifiers  = $numbersign . $percent . $prime;

			return preg_replace(
				array(
				// Remove separator, control, formatting, surrogate,
				// open/close quotes.
					'/[\p{Z}\p{Cc}\p{Cf}\p{Cs}\p{Pi}\p{Pf}]/u',
				// Remove other punctuation except special cases
					'/\p{Po}(?<![' . $specialquotes .
						$numseparators . $urlall . $nummodifiers . '])/u',
				// Remove non-URL open/close brackets, except URL brackets.
					'/[\p{Ps}\p{Pe}](?<![' . $urlbrackets . '])/u',
				// Remove special quotes, dashes, connectors, number
				// separators, and URL characters followed by a space
					'/[' . $specialquotes . $numseparators . $urlspaceafter .
						'\p{Pd}\p{Pc}]+((?= )|$)/u',
				// Remove special quotes, connectors, and URL characters
				// preceded by a space
					'/((?<= )|^)[' . $specialquotes . $urlspacebefore . '\p{Pc}]+/u',
				// Remove dashes preceded by a space, but not followed by a number
					'/((?<= )|^)\p{Pd}+(?![\p{N}\p{Sc}])/u',
				// Remove consecutive spaces
					'/ +/',
				),
				' ',
				$text );
		}

		/**
		 * Strip symbol characters from UTF-8 text.
		 *
		 * Characters stripped from the text include characters in the following
		 * Unicode categories:
		 *
		 * 	Modifier symbols
		 * 	Private use symbols
		 * 	Math symbols
		 * 	Other symbols
		 *
		 * Exceptions are made for math symbols embedded within numbers (such as
		 * + - /), math symbols used within URLs (such as = ~), units of measure
		 * symbols, and ideograph parts.  Currency symbols are not removed.
		 *
		 * Parameters:
		 * 	text		the UTF-8 text to strip
		 *
		 * Return values:
		 * 	the stripped UTF-8 text.
		 *
		 * See also:
		 *	http://nadeausoftware.com/articles/2007/09/php_tip_how_strip_symbol_characters_web_page
		 */
		public static function strip_symbols( $text )
		{
			$plus   = '\+\x{FE62}\x{FF0B}\x{208A}\x{207A}';
			$minus  = '\x{2012}\x{208B}\x{207B}';

			$units  = '\\x{00B0}\x{2103}\x{2109}\\x{23CD}';
			$units .= '\\x{32CC}-\\x{32CE}';
			$units .= '\\x{3300}-\\x{3357}';
			$units .= '\\x{3371}-\\x{33DF}';
			$units .= '\\x{33FF}';

			$ideo   = '\\x{2E80}-\\x{2EF3}';
			$ideo  .= '\\x{2F00}-\\x{2FD5}';
			$ideo  .= '\\x{2FF0}-\\x{2FFB}';
			$ideo  .= '\\x{3037}-\\x{303F}';
			$ideo  .= '\\x{3190}-\\x{319F}';
			$ideo  .= '\\x{31C0}-\\x{31CF}';
			$ideo  .= '\\x{32C0}-\\x{32CB}';
			$ideo  .= '\\x{3358}-\\x{3370}';
			$ideo  .= '\\x{33E0}-\\x{33FE}';
			$ideo  .= '\\x{A490}-\\x{A4C6}';

			return preg_replace(
				array(
				// Remove modifier and private use symbols.
					'/[\p{Sk}\p{Co}]/u',
				// Remove math symbols except + - = ~ and fraction slash
					'/\p{Sm}(?<![' . $plus . $minus . '=~\x{2044}])/u',
				// Remove + - if space before, no number or currency after
					'/((?<= )|^)[' . $plus . $minus . ']+((?![\p{N}\p{Sc}])|$)/u',
				// Remove = if space before
					'/((?<= )|^)=+/u',
				// Remove + - = ~ if space after
					'/[' . $plus . $minus . '=~]+((?= )|$)/u',
				// Remove other symbols except units and ideograph parts
					'/\p{So}(?<![' . $units . $ideo . '])/u',
				// Remove consecutive white space
					'/ +/',
				),
				' ',
				$text );
		}

		/**
		 * Extract Links(<a>) from HTML string.
		 *
		 * 
		 * Parameters:
		 * 	text		the HTML text to parse
		 *
		 * Return values:
		 * 	An associated array with links,title and category (link,file) 
		 *
		 */
		public static function extract_links( $text,$url)
		{
			preg_match_all("/(<(\s*a)[^>]*?href\s*=\s*\"(.*?)\".*?>)(.*?)(<\s*\/\\2\s*>)/i", $text, $matches, PREG_SET_ORDER);
			
			$count = 0;
			$url = self::getBaseURL($url);
			$linkArray = array();

			foreach ($matches as $val) {
				if(!preg_match("~javascript:~i", $val[3])){
					$count++;
					$link["link"] = self::getFullUrl($val[3],$url);
					$link["name"] = self::strip_html_tags($val[4]);
					$link["type"] = "links";
				    $linkArray[] = $link;
				}
			}

			return $linkArray;
		}

		/**
		 * Get Full Url - Changes relative url (/path/path/) to full path(http://domian/path/path)
		 *
		 * 
		 * Parameters:
		 * 	url - relative url 
		 *  baseUrl - Base url with domain
		 *
		 * Return value:
		 * 	 Full Url
		 *
		 */ 

		private static function getFullUrl($url,$baseURL){

			if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
				
				if (!preg_match("~^\/*www.~i", $url)){
		       		
		       		if(!preg_match("~^/~i", $url)){
		       			$url = "/" . $url;
		       		}
		       		$url = $baseURL . $url;

		       }else{
		       		$url = preg_replace(array("/^\/\/*/"),"",$url);
		       }
		    }
    		return $url;
		}

		/**
		 * Get Full Url - Changes relative url (/path/path/) to full path(http://domian/path/path)
		 *
		 * 
		 * Parameters:
		 * 	url - relative url 
		 *  baseUrl - Base url with domain
		 *
		 * Return value:
		 * 	 Full Url
		 *
		 */ 

		private static function getBaseUrl($url){
			
			if (preg_match("/^((?:f|ht)tps?.*)\/?/i", $url,$matches)) {
		       	if (preg_match("/^((?:f|ht)tps?.+\/)/i", $matches[0])) {
		       		$url = $matches[1];
		       	}
		    }

    		return $url;
		}
	}
?>