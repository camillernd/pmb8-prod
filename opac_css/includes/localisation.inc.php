<?php
// +-------------------------------------------------+
// © 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: localisation.inc.php,v 1.15.14.1 2024/05/22 08:00:07 jparis Exp $

if (stristr($_SERVER['REQUEST_URI'], ".inc.php")) die("no access");

if ( ! defined('LOCALISATION_INC') ) {
	define ('LOCALISATION_INC', 1);

// récupération classe de gestion de fichier XML
require_once($base_path.'/classes/XMLlist.class.php');

// variables
$msg = "";
if (!$lang) $lang=$opac_default_lang ; 
if ($lang == "ar") {
	$fichier = "./styles/".$css."rtl/".$css."rtl.css";
	if ((@fopen($fichier, "r")))
		$css = $css . "rtl";
}

function set_language($lang) {
	global $msg;
	global $base_path;
	global $messages;
	global $messages_last_modified;

	$messages_last_modified = filectime($base_path."/includes/messages/$lang.xml");

	$messages = new XMLlist($base_path."/includes/messages/$lang.xml", 0);
	$messages->analyser();
	$msg = $messages->table;
}	
// localisation
set_language($lang);


} // fin de définition de LOCALISATION_INC
