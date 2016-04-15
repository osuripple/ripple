<?php
/**
 * BBcode helper class.
 *
 * @category   Helper
 *
 * @author     Chema <chema@garridodiaz.com>
 * @copyright  (c) 2012
 * @license    GPL v3
 */
class bbcode {
	/**
	 * This function parses BBcode tag to HTML code (XHTML transitional 1.0).
	 *
	 * It parses (only if it is in valid format e.g. an email must to be
	 * as example@example.ext or similar) the text with BBcode and
	 * translates in the relative html code.
	 *
	 * @param string $text
	 * @param bool   $advanced his var describes if the parser run in advanced mode (only *simple* bbcode is parsed).
	 *
	 * @return string
	 */
	public static function tohtml($text, $advanced = false, $charset = 'utf-8') {
		//special chars
		$text = htmlspecialchars($text, ENT_QUOTES, $charset);
		/*
		 * This array contains the main static bbcode.
		 *
		 * @var array
		*/
		$basic_bbcode = ['[b]', '[/b]', '[i]', '[/i]', '[u]', '[/u]', '[s]', '[/s]', '[ul]', '[/ul]', '[li]', '[/li]', '[ol]', '[/ol]', '[center]', '[/center]', '[left]', '[/left]', '[right]', '[/right]', '[hr]', '[/hr]', ':peppy:', ':barney:', ':akerino:', ':foka:', ':kappy:', ':creepypeppy:', ':peppyfiero:', ':djpeppy:', ':kappa:'];
		/*
		 * This array contains the main static bbcode's html.
		 *
		 * @var array
		*/
		$basic_html = ['<b>', '</b>', '<i>', '</i>', '<u>', '</u>', '<s>', '</s>', '<ul>', '</ul>', '<li>', '</li>', '<ol>', '</ol>', '<div style="text-align: center; display: inline;">', '</div>', '<div style="text-align: left; display: inline;">', '</div>', '<div style="text-align: right; display: inline;">', '</div>', '<hr>', '<hr>', '<img src="./images/emoticons/peppy.png">', '<img src="./images/emoticons/barney.png">', '<img src="./images/emoticons/akerino.png">', '<img src="./images/emoticons/foka.png">', '<img src="./images/emoticons/kappy.png">', '<img src="./images/emoticons/creepypeppy.png">', '<img src="./images/emoticons/peppyfiero.png">', '<img src="./images/emoticons/djpeppy.png">', '<img src="./images/emoticons/kappa.png">'];
		/*
		 *
		 * Parses basic bbcode, used str_replace since seems to be the fastest
		*/
		$text = str_replace($basic_bbcode, $basic_html, $text);
		//advanced BBCODE
		if ($advanced) {
			/*
			 * This array contains the advanced static bbcode.
			 *
			 * @var array
			*/
			$advanced_bbcode = ['#\[color=([a-zA-Z]*|\#?[0-9a-fA-F]{6})](.+)\[/color\]#Usi', '#\[size=([0-9][0-9]?)](.+)\[/size\]#Usi', '#\[quote](\r\n)?(.+?)\[/quote]#si', '#\[quote=(.*?)](\r\n)?(.+?)\[/quote]#si', '#\[url](.+)\[/url]#Usi', '#\[url=(.+)](.+)\[/url\]#Usi', '#\[email]([\w\.\-]+@[a-zA-Z0-9\-]+\.?[a-zA-Z0-9\-]*\.\w{1,4})\[/email]#Usi', '#\[email=([\w\.\-]+@[a-zA-Z0-9\-]+\.?[a-zA-Z0-9\-]*\.\w{1,4})](.+)\[/email]#Usi', '#\[img](.+)\[/img]#Usi', '#\[img=(.+)](.+)\[/img]#Usi', '#\[code](\r\n)?(.+?)(\r\n)?\[/code]#si', '#\[youtube]http://[a-z]{0,3}.youtube.com/watch\?v=([0-9a-zA-Z]{1,11})\[/youtube]#Usi', '#\[youtube]([0-9a-zA-Z]{1,11})\[/youtube]#Usi'];
			/*
			 * This array contains the advanced static bbcode's html.
			 *
			 * @var array
			*/
			$advanced_html = ['<span style="color: $1">$2</span>', '<font size=$1>$2</font>', "<div class=\"quote\"><span class=\"quoteby\">Disse:</span>\r\n$2</div>", "<div class=\"quote\"><span class=\"quoteby\">Disse <b>$1</b>:</span>\r\n$3</div>", '<a rel="nofollow" target="_blank" href="$1">$1</a>', '<a rel="nofollow" target="_blank" href="$1">$2</a>', '<a href="mailto: $1">$1</a>', '<a href="mailto: $1">$2</a>', '<img src="$1" alt="$1" />', '<img src="$1" alt="$2" />', '<div class="code">$2</div>', '<iframe width="560" height="315" src="https://www.youtube.com/embed/$1" frameborder="0" allowfullscreen></iframe>', '<iframe width="560" height="315" src="https://www.youtube.com/embed/$1" frameborder="0" allowfullscreen></iframe>'];
			$text = preg_replace($advanced_bbcode, $advanced_html, $text);
		}
		//before return convert line breaks to HTML
		return self::purify(self::nl2br($text));
	}

	/**
	 * Inserts HTML line breaks before all newlines in a string.
	 *
	 * @param string $var
	 */
	public static function nl2br($var) {
		return str_replace(['\\r\\n', '\r\\n', 'r\\n', '\r\n', '\n', '\r'], '<br />', nl2br($var));
	}

	/**
	 * Passes HTML to HTMLPurifier so that we don't have memes.
	 *
	 * @param string $var
	 */
	public static function purify($var) {
		$config = HTMLPurifier_Config::createDefault();
		$config->set('HTML.SafeIframe', true);
		$config->set('URI.SafeIframeRegexp', '%^(https?:)?//(www\.youtube(?:-nocookie)?\.com/embed/)%');
		$purifier = new HTMLPurifier($config);
		$clean_html = $purifier->purify($var);

		return $clean_html;
	}

	/**
	 * removes bbcode from text.
	 *
	 * @param string $text
	 *
	 * @return string text cleaned
	 */
	public static function remove($text) {
		return strip_tags(str_replace(['[', ']'], ['<', '>'], $text));
	}
}
