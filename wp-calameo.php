<?
/*
    Plugin Name: WP Calameo
    Description: Embed Calameo publications & miniCalameo inside a post
    Version: 1.01
    Author: Calameo
*/

/*  Copyright 2009 Calameo  (email : contact@calameo.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

function calameo_parse( $content )
{
	// Parse for full Calaméo viewers
	$content = preg_replace_callback("/\[calameo ([^]]*)\]/i", "calameo_render", $content);

	return $content;
}

function calameo_render( $tags )
{
	// Parsing attributes
	if ( !preg_match_all('/([^= ]+)=([\S]*)/i', $tags[1], $matches) ) return '';

	$attributes = array();

	$count = count($matches[1]);

	for ( $i = 0 ; $i < $count ; $i++ )
	{
		$attributes[$matches[1][$i]] = $matches[2][$i];
	}

	// Get code from URL
	if ( !empty($attributes['url']) )
	{
		if ( preg_match('~calameo\.com/(read|books)/([0-9a-z]+)~i', $attributes['url'], $regs) )
		{
			$attributes['code'] = $regs[2];
		}
	}

	// Checking attributes
	if ( empty($attributes['code']) ) return '';
	if ( empty($attributes['mode']) ) $attributes['mode'] = 'book';
	if ( empty($attributes['page']) ) $attributes['page'] = 1;
	if ( empty($attributes['wmode']) ) $attributes['wmode'] = '';
	if ( empty($attributes['title']) ) $attributes['title'] = 'View this publication on Calam&eacute;o';

	// Language
	$language = preg_match('/$([a-z]+)/i', get_bloginfo('language'));

	$languages = array(
					   'en'=>'en',
					   'fr'=>'fr',
					   'es'=>'es',
					   'de'=>'de',
					   'it'=>'it',
					   'pt'=>'pt',
					   'ru'=>'ru',
					   'ja'=>'jp',
					   'zh'=>'cn',
					   'kr'=>'kr'
					  );

	if ( empty($attributes['lang']) )
	{
		$attributes['lang'] = ( !empty($language) && !empty($languages[$language]) ) ? $languages[$language] : 'en';
	}

	// Prepare viewer and link URLs
	$book_url = 'http://calameo.com/books/' . $attributes['code'] . ( !empty($attributes['authid']) ? '?authid=' . $attributes['authid'] : '' );
	$home_url = 'http://calameo.com';
	$publish_url = 'http://calameo.com/upload';
	$browse_url = 'http://calameo.com/browse/weekly/?o=7&w=DESC';

	// Preparing Flashvars
	$flashvars  = 'bkcode=' . $attributes['code'];
	$flashvars .= '&amp;langid=' . $attributes['lang'];
	$flashvars .= '&amp;page=' . $attributes['page'];

	switch ( $attributes['mode'] )
	{
		case 'mini':
			if ( empty($attributes['width']) )$attributes['width'] = '240';
			if ( empty($attributes['height']) ) $attributes['height'] = '150';

			if ( empty($attributes['clickto']) ) $attributes['clickto'] = 'public';
			if ( empty($attributes['clicktarget']) ) $attributes['clicktarget'] = '_self';
			if ( empty($attributes['clicktourl']) ) $attributes['clicktourl'] = '';
			if ( empty($attributes['autoflip']) ) $attributes['autoflip'] = '0';
			if ( empty($attributes['showarrows']) ) $attributes['showarrows'] = '1';

			$viewer_url = 'http://v.calameo.com/2.0/cmini.swf';

			$flashvars .= '&amp;clickTo=' . urlencode($attributes['clickto']);
			$flashvars .= '&amp;clickTarget=' . urlencode($attributes['clicktarget']);
			$flashvars .= '&amp;clickToUrl=' . urlencode($attributes['clicktourl']);
			$flashvars .= '&amp;autoFlip=' . max(0, intval($attributes['autoflip']));
			$flashvars .= '&amp;showArrows=' . ( !empty($attributes['showarrows']) ? '1' : '0' );
			$flashvars .= '&amp;mode=embed';

			break;

		case 'book':
		default:
			if ( empty($attributes['width']) )$attributes['width'] = '';
			if ( empty($attributes['height']) ) $attributes['height'] = '400';

			if ( empty($attributes['view']) ) $flashvars .= '&amp;viewModeAtStart=' . $attributes['view'];
			if ( !empty($attributes['authid']) ) $flashvars .= '&amp;authid=' . $attributes['authid'];

			$viewer_url = 'http://v.calameo.com/2.0/cviewer.swf';

			break;
	}

	// Sizes and units
	$attributes['widthUnit'] = ( strpos($attributes['width'], '%') ) ? '' : 'px';
	$attributes['heightUnit'] = ( strpos($attributes['height'], '%') ) ? '' : 'px';

	// Generate HTML embed code
	$html = '<div style="' . ( empty($attributes['styles']) ? 'text-align: center; width:' . $attributes['width'] . $attributes['widthUnit'] . '; margin: 12px auto;' : $attributes['styles'] ) . '">';

	if ( empty($attributes['hidelinks']) ) $html .= '<div style="margin: 4px 0px;"><a href="' . $book_url . '">' . $attributes['title'] . '</a></div>';

	$html .= '<object id="' . $attributes['code'] . '-' . mktime() . '-' . rand(1000,9999) . '" style="width:' . $attributes['width'] . $attributes['widthUnit'] . ';height:' . $attributes['height'] . $attributes['heightUnit'] . '" >';
	$html .= '<param name="movie" value="' . $viewer_url . '?' . $flashvars . '" />';
	$html .= '<param name="quality" value="high" />';
	$html .= '<param name="scale" value="noscale" />';
	$html .= '<param name="loop" value="false" />';
	$html .= '<param name="salign" value="t" />';
	$html .= '<param name="allowscriptaccess" value="always" />';
	$html .= '<param name="allowfullscreen" value="true" />';
	$html .= '<param name="menu" value="false" />';

	if ( !empty($attributes['wmode']) ) $html .= '<param name="wmode" value="' . $attributes['wmode'] . '" />';

	$html .= '<embed src="' . $viewer_url . '" type="application/x-shockwave-flash" style="width:' . $attributes['width'] . $attributes['widthUnit'] . ';height:' . $attributes['height'] . $attributes['heightUnit'] . '" flashvars="' . $flashvars . '" quality="high" scale="noscale" loop="false" salign="t" allowscriptaccess="always" allowfullscreen="true" menu="false" ' . ( !empty($attributes['wmode']) ? 'wmode="' . $attributes['wmode'] . '"' : '' ) . ' />';
	$html .= '</object>';

	if ( empty($attributes['hidelinks']) ) $html .= '<div style="margin: 4px 0px; font-size: 90%;"><a rel="nofollow" href="' . $publish_url . '">Publish</a> at <a href="' . $home_url . '">Calam&eacute;o</a> or <a href="' . $browse_url . '">browse</a> the library.</div>';

	$html .= '</div>';

	//

	return $html;
}

add_filter('the_content', 'calameo_parse');

?>