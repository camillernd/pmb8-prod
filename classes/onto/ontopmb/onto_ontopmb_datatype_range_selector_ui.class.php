<?php
// +-------------------------------------------------+
// � 2002-2011 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: onto_ontopmb_datatype_range_selector_ui.class.php,v 1.7 2020/10/14 10:45:31 arenou Exp $

if (stristr($_SERVER['REQUEST_URI'], ".class.php")) die("no access");

require_once $class_path.'/onto/common/onto_common_datatype_ui.class.php';
require_once $include_path.'/templates/onto/ontopmb/onto_ontopmb_datatype_range_selector_ui.tpl.php';


/**
 * class onto_common_datatype_resource_selector_ui
 * 
 */
class onto_ontopmb_datatype_range_selector_ui extends onto_common_datatype_ui {

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

		$classes = $property->get_available_range();
		
		$classes['http://www.w3.org/2000/01/rdf-schema#Literal'] = $msg['onto_ontopmb_datatype_range_selector_literal'];
		$pmb_entities_ranges = array(
				'http://www.pmbservices.fr/ontology#record' => $msg['288'],
				'http://www.pmbservices.fr/ontology#author' => $msg['234'],
				'http://www.pmbservices.fr/ontology#category' => $msg['isbd_categories'],
				'http://www.pmbservices.fr/ontology#publisher' => $msg['isbd_editeur'],
				'http://www.pmbservices.fr/ontology#collection' => $msg['isbd_collection'],
				'http://www.pmbservices.fr/ontology#sub_collection' => $msg['isbd_subcollection'],
				'http://www.pmbservices.fr/ontology#serie' => $msg['isbd_serie'],
				'http://www.pmbservices.fr/ontology#work' => $msg['isbd_titre_uniforme'],
				'http://www.pmbservices.fr/ontology#indexint' => $msg['isbd_indexint'],
				'http://www.w3.org/2004/02/skos/core#Concept' => $msg['concept_menu'],
				'http://www.pmbservices.fr/ontology#marclist' => $msg['parperso_marclist'],
				
		);
		$classes = array_merge($classes, $pmb_entities_ranges);
 		foreach($classes as $value => $label){
 			$selected = false;
 			if(is_array($datas) && count($datas)){
	 			foreach($datas as $data){
	 				if($value == $data->get_value()){
	 					$selected = true;
	 				}
	 			}
 			}
  			if($selected){
  				$options.="<option value='".$value."' selected='selected'>".htmlentities($label,ENT_QUOTES,$charset)."</option>";
  			}else{
  				$options.="<option value='".$value."'>".htmlentities($label,ENT_QUOTES,$charset)."</option>";
  			}
 		}
		$form=str_replace("!!onto_rows!!",$ontology_tpl['onto_ontopmb_datatype_range_selector_ui'], $form);
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
	
	public static function get_validation_js($item_uri,$property, $restrictions,$datas, $instance_name,$flag){
		global $msg;
		return '{
			"message": "'.addslashes($property->get_label()).'",
			"valid" : true,
			"error": "",
			"values": new Array(),
			"check": function(){
				this.valid = true;	
				var div_values = document.getElementById("'.$instance_name.'_'.$property->pmb_name.'_values");	
				while(div_values.childNodes.length>0){
					div_values.removeChild(div_values.firstChild);
				}
				var select = document.getElementById("'.$instance_name.'_'.$property->pmb_name.'_select");
				var literal_selected = false;	
				j=0;		
				for(var i=0 ; i< select.options.length ; i++){
					if(	select.options[i].selected){
						j++;
						if(select.options[i].value == "http://www.w3.org/2000/01/rdf-schema#Literal"){
							literal_selected = true;
						}
						var input = document.createElement("input");
						input.setAttribute("type","hidden");
						input.setAttribute("name","'.$instance_name.'_'.$property->pmb_name.'["+j+"][value]");
						input.value = select.options[i].value;
						div_values.appendChild(input);
					}
				}
				if(j>1 && literal_selected){
					this.valid = false;
					this.error = "only_literal";			
				}
				return this.valid;
			},
			"get_error_message": function(){
 				switch(this.error){
 					case "only_literal" :
						this.message = "'.addslashes($msg['onto_error_only_literal']).'";
						break;
 				}
				this.message = this.message.replace("%s","'.addslashes($property->get_label()).'");
				return this.message;
			}
		}';
	}

} // end of onto_common_datatype_resource_selector_ui
