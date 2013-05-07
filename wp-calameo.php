<?php
/*
    Plugin Name: WP Calameo
    Description: Embed Calameo publications & miniCalameo inside a post
    Version: 2.0.4
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
	if ( empty($attributes['mode']) ) $attributes['mode'] = '';
	if ( empty($attributes['page']) ) $attributes['page'] = 1;
	if ( empty($attributes['wmode']) ) $attributes['wmode'] = '';
	if ( empty($attributes['title']) ) $attributes['title'] = 'View this publication on Calam&eacute;o';
	if ( !isset($attributes['showsharemenu']) ) $attributes['showsharemenu'] = 1;
	
	$attributes['showsharemenu'] = ( $attributes['showsharemenu'] == '1' || $attributes['showsharemenu'] == 'true' ) ? 'true' : 'false';

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
	$viewer_url = '//v.calameo.com/';

	// Preparing Flashvars
	$flashvars  = 'bkcode=' . $attributes['code'];
	$flashvars .= '&amp;language=' . $attributes['lang'];
	$flashvars .= '&amp;page=' . $attributes['page'];
	$flashvars .= '&amp;showsharemenu=' . $attributes['showsharemenu'];
	
	switch ( $attributes['mode'] )
	{
		case 'mini':
			if ( empty($attributes['width']) )			$attributes['width'] = '240';
			if ( empty($attributes['height']) )			$attributes['height'] = '150';

			if ( empty($attributes['clickto']) )		$attributes['clickto'] = 'public';
			if ( empty($attributes['clicktarget']) )	$attributes['clicktarget'] = '_self';
			if ( empty($attributes['clicktourl']) )		$attributes['clicktourl'] = '';
			if ( empty($attributes['autoflip']) )		$attributes['autoflip'] = '0';

			if ( empty($attributes['wmode']) )			$attributes['wmode'] = 'transparent';

			$flashvars .= '&amp;clickTo=' . urlencode($attributes['clickto']);
			$flashvars .= '&amp;clickTarget=' . urlencode($attributes['clicktarget']);
			$flashvars .= '&amp;clickToUrl=' . urlencode($attributes['clicktourl']);
			$flashvars .= '&amp;autoFlip=' . max(0, intval($attributes['autoflip']));
			$flashvars .= '&amp;mode=mini';

			break;

		case 'book':
		case 'viewer':
		
			$flashvars .= '&amp;mode=viewer';

		default:
			if ( empty($attributes['width']) )$attributes['width'] = '100%';
			if ( empty($attributes['height']) ) $attributes['height'] = '400';

			break;
	}

	if ( !empty($attributes['authid']) )				$flashvars .= '&amp;authid=' . $attributes['authid'];
	if ( !empty($attributes['view']) )					$flashvars .= '&amp;view=' . $attributes['view'];
	if ( !empty($attributes['wmode']) )					$flashvars .= '&amp;wmode=' . $attributes['wmode'];
	if ( !empty($attributes['allowminiskin']) )			$flashvars .= '&amp;allowminiskin=' . $attributes['allowminiskin'];
	if ( !empty($attributes['skinurl']) )				$flashvars .= '&amp;skinurl=' . $attributes['skinurl'];
	if ( !empty($attributes['styleurl']) )				$flashvars .= '&amp;styleurl=' . $attributes['styleurl'];
	if ( !empty($attributes['shareurl']) )				$flashvars .= '&amp;shareurl=' . $attributes['shareurl'];
	if ( !empty($attributes['locales']) )				$flashvars .= '&amp;locales=' . $attributes['locales'];
	if ( !empty($attributes['volume']) )				$flashvars .= '&amp;volume=' . $attributes['volume'];
	if ( !empty($attributes['pagefxopacity']) )			$flashvars .= '&amp;pagefxopacity=' . $attributes['pagefxopacity'];
	if ( !empty($attributes['pagefxopacityonzoom']) )	$flashvars .= '&amp;pagefxopacityonzoom=' . $attributes['pagefxopacityonzoom'];
	if ( !empty($attributes['ip']) )					$flashvars .= '&amp;ip=' . $attributes['ip'];
	if ( !empty($attributes['apikey']) )				$flashvars .= '&amp;apikey=' . $attributes['apikey'];
	if ( !empty($attributes['expires']) )				$flashvars .= '&amp;expires=' . $attributes['expires'];
	if ( !empty($attributes['signature']) )				$flashvars .= '&amp;signature=' . $attributes['signature'];

	// Sizes and units
	$attributes['widthUnit'] = ( strpos($attributes['width'], '%') ) ? '' : 'px';
	$attributes['heightUnit'] = ( strpos($attributes['height'], '%') ) ? '' : 'px';

	// Generate HTML embed code
	$html = '<div style="' . ( empty($attributes['styles']) ? 'text-align: center; width:' . $attributes['width'] . $attributes['widthUnit'] . '; margin: 12px auto;' : $attributes['styles'] ) . '">';

	if ( empty($attributes['hidelinks']) ) $html .= '<div style="margin: 4px 0px;"><a href="' . $book_url . '">' . $attributes['title'] . '</a></div>';

	$id = 'calameo-viewer-' . $attributes['code'] . '-' . mktime() . '-' . rand(1000,9999);

	$html .= '<iframe src="' . $viewer_url . '?' . $flashvars . '" width="' . $attributes['width'] . '" height="' . $attributes['height'] . '" style="width:' . $attributes['width'] . $attributes['widthUnit'] . ';height:' . $attributes['height'] . $attributes['heightUnit'] . '" frameborder="0" scrolling="no" allowtransparency allowfullscreen></iframe>';

	if ( empty($attributes['hidelinks']) ) $html .= '<div style="margin: 4px 0px; font-size: 90%;"><a rel="nofollow" href="' . $publish_url . '">Publish</a> at <a href="' . $home_url . '">Calam&eacute;o</a> or <a href="' . $browse_url . '">browse</a> the library.</div>';

	$html .= '</div>';

	//

	return $html;
}

add_filter('the_content', 'calameo_parse');

?>