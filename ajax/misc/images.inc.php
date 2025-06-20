<?php
// +-------------------------------------------------+
// � 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: images.inc.php,v 1.5.4.1 2024/12/27 16:04:28 qvarin Exp $

if (stristr($_SERVER['REQUEST_URI'], ".inc.php")) die("no access");

global $class_path, $action;

require_once($class_path."/encoding_normalize.class.php");

switch($action){
	case 'get_images':
		$icons_name = array(
			'minus.gif', 'plus.gif', 'expand_all.gif', 'collapse_all.gif', 'patience.gif',
			'sort.png', 'icone_drag_notice.png', 'trash.png', 'drag_symbol.png', 'drag_symbol_empty.png',
			'cross.png', 'star.png', 'star_unlight.png', 'rss.png', 'translate.png', 'bottom-arrow.png', 'top-arrow.png',
			'tick.gif', 'error.gif'
		);
		$images = array();
		foreach($icons_name as $name) {
			$images[$name] = get_url_icon($name);
		}
		print encoding_normalize::json_encode($images);
		break;
}