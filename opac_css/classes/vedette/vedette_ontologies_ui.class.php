<?php
// +-------------------------------------------------+
// | 2002-2007 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: vedette_ontologies_ui.class.php,v 1.3 2021/01/21 09:42:08 jlaurent Exp $

if (stristr($_SERVER['REQUEST_URI'], ".class.php")) die("no access");

require_once($include_path."/templates/vedette/vedette_ontologies.tpl.php");

class vedette_ontologies_ui extends vedette_element_ui{
	
	/**
	 * Boite de s�lection de l'�l�ment
	 *
	 * @return string
	 * @access public
	 */
	public static function get_form($params = array(), $suffix = "") {
		global $vedette_ontologies_tpl;
		
		$tpl = $vedette_ontologies_tpl["vedette_ontologies_selector" . $suffix];
		$tpl = str_replace("!!ontology_label!!", $params['label'],$tpl);
		$tpl = str_replace("!!ontology_id!!", $params['id_ontology'],$tpl);
		$tpl = str_replace("!!ontology_num!!", $params['num'],$tpl);
		$tpl = str_replace("!!ontology_pmbname!!", $params['pmbname'],$tpl);
		return $tpl;
	}
	
	/**
	 * Renvoie le code javascript pour la cr�ation du s�l�cteur
	 *
	 * @return string
	 */
	public static function get_create_box_js($params= array(), $suffix = ""){
		global $vedette_ontologies_tpl;
		$json_data ='';
		if (!empty($suffix)){
		    $selector_data = array();
		    $selector_data['type'] = 'onto';
		    $json_data = encoding_normalize::json_encode($selector_data);
		}
		$tpl = $vedette_ontologies_tpl["vedette_ontologies_script".$suffix];
		$tpl = str_replace("!!ontology_label!!", $params['label'],$tpl);
		$tpl = str_replace("!!ontology_id!!", $params['id_ontology'],$tpl);
		$tpl = str_replace("!!ontology_num!!", $params['num'],$tpl);
		$tpl = str_replace("!!ontology_pmbname!!", $params['pmbname'],$tpl);
		$tpl = str_replace("!!selector_data!!", urlencode($json_data), $tpl);
		return $tpl;
	}
	
	/**
	 * Renvoie le nom de la classe JS
	 *
	 * @return string
	 */
	public static function get_js_class_name($params= array()){
		return "vedette_ontologies".$params['num'];
	}
	
	/**
	 * Renvoie les donn�es (id objet, type)
	 *
	 * @return void
	 * @access public
	 */
	public static function get_from_form($params = array()){
	
	}
}
