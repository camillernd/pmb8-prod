<?php
// +-------------------------------------------------+
// � 2002-2011 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: onto_ontopmb_datatype_marclist_selector_ui.class.php,v 1.3 2020/10/14 10:45:31 arenou Exp $

if (stristr($_SERVER['REQUEST_URI'], ".class.php")) die("no access");

require_once $class_path.'/onto/common/onto_common_datatype_ui.class.php';
require_once $include_path.'/templates/onto/ontopmb/onto_ontopmb_datatype_marclist_selector_ui.tpl.php';


/**
 * class onto_common_datatype_resource_selector_ui
 * 
 */
class onto_ontopmb_datatype_marclist_selector_ui extends onto_common_datatype_ui {

// 	protected static $options = array(
// 		// 	);
	
	
	/** Aggregations: */

	/** Compositions: */

	 /*** Attributes: ***/


	/**
	 * 
	 *
	 * @param Array() class_uris URI des classes de l'ontologie list�es dans le s�lecteur

	 * @return void
	 * @access public
	 */
	public function __construct( $class_uris ) {
	} // end of member function __construct

	/**
	 * 
	 *
	 * @param string class_uri URI de la classe d'instances � lister

	 * @param integer page Num�ro de page � afficher

	 * @return Array()
	 * @access public
	 */
	public function get_list( $class_uri,  $page ) {
	} // end of member function get_list

	/**
	 * Recherche
	 *
	 * @param string user_query Chaine de recherche dans les labels

	 * @param string class_uri Rechercher iniquement les instances de la classe

	 * @param integer page Page du r�sultat de recherche � afficher

	 * @return Array()
	 * @access public
	 */
	public function search( $user_query,  $class_uri,  $page ) {
	} // end of member function search


	/**
	 * 
	 *
	 * @param onto_common_property $property la propri�t� concern�e
	 * @param restriction $restrictions le tableau des restrictions associ�es � la propri�t� 
	 * @param array datas le tableau des datatypes
	 * @param string instance_name nom de l'instance
	 * @param string flag Flag

	 * @return string
	 * @static
	 * @access public
	 */
	public static function get_form($item_uri,$property, $restrictions,$datas, $instance_name,$flag) {
		global $msg,$charset,$ontology_tpl;
		$form=$ontology_tpl['form_row'];
		$form=str_replace("!!onto_row_label!!",htmlentities($property->get_label() ,ENT_QUOTES,$charset) , $form);
		$form=str_replace("!!onto_new_order!!","0" , $form);
		$options = "";
		foreach(onto_ontopmb_datatype_marclist_selector::$options as $value => $msg_key){
 			if(is_array($datas) && count($datas) && $value == $datas[0]->get_value()){
 				$options.="<option value='".$value."' selected='selected'>".htmlentities($msg[$msg_key],ENT_QUOTES,$charset)."</option>";
 			}else{
 				$options.="<option value='".$value."'>".htmlentities($msg[$msg_key],ENT_QUOTES,$charset)."</option>";
 			}
		}
		$form=str_replace("!!onto_rows!!",$ontology_tpl['onto_ontopmb_datatype_marclist_ui'], $form);
		$form=str_replace("!!options!!",$options, $form);
		$form=str_replace("!!onto_row_id!!",$instance_name.'_'.$property->pmb_name , $form);
		return $form;
	} // end of member function get_form
	
	/**
	 * 
	 *
	 * @param onto_common_datatype datas Tableau des valeurs � afficher associ�es � la propri�t�

	 * @param property property la propri�t� � utiliser

	 * @param string instance_name nom de l'instance

	 * @return string
	 * @access public
	 */
	public function get_display($datas, $property, $instance_name) {
		
		$display='<div id="'.$instance_name.'_'.$property->pmb_name.'">';
		$display.='<p>';
		$display.=$property->get_label().' : ';
		foreach($datas as $data){
			$display.=$data->get_formated_value();
		}
		$display.='</p>';
		$display.='</div>';
		return $display;
		
	}

} // end of onto_common_datatype_resource_selector_ui
