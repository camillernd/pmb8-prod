<?php
// +-------------------------------------------------+
// � 2002-2011 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: onto_contribution_datatype_docnum_file.class.php,v 1.6 2022/05/09 10:26:00 tsamson Exp $

if (stristr($_SERVER['REQUEST_URI'], ".class.php")) die("no access");

require_once $class_path.'/onto/common/onto_common_datatype.class.php';
require_once $class_path.'/upload_folder.class.php';
require_once $class_path.'/explnum.class.php';


/**
 * class onto_common_datatype_small_text
 * Les m�thodes get_form,get_value,check_value,get_formated_value,get_raw_value
 * sont �ventuellement � red�finir pour le type de donn�es
 */
class onto_contribution_datatype_docnum_file extends onto_common_datatype_file {

	/** Aggregations: */

	/** Compositions: */

	 /*** Attributes: ***/
	
	
	public function check_value(){
		if (is_string($this->value) && (strlen($this->value) < 256)) return true;
		return false;
	}
	
	public static function get_values_from_form($instance_name, $property, $uri_item) {
		$var_name = $instance_name."_".$property->pmb_name;
		$var_directory = $instance_name."_upload_directory";	
		
		global ${$var_name}, ${$var_directory};

		$file = array();
		if (isset($_FILES[$instance_name."_".$property->pmb_name]) && $_FILES[$instance_name."_".$property->pmb_name]["name"][0]["value"] != "" && $_FILES[$instance_name."_".$property->pmb_name]["tmp_name"][0]["value"] != "") {
			$file = $_FILES[$instance_name."_".$property->pmb_name];
		}
		if(count($file)) {
			if (isset(${$var_directory}) && ${$var_directory}[0]['value']) {
				$path = '/';
				$upload_directory = ${$var_directory}[0]['value'];
				
				$slash_pos = strpos($upload_directory, '/');
				// Si il y a un slash dans la valeur, alors c'est un r�pertoire navigable
				if ($slash_pos !== false) {
					$path = substr($upload_directory, $slash_pos);
					$upload_directory = substr($upload_directory, 0, $slash_pos);
				}
				$upload_folder = new upload_folder($upload_directory);
				$file_path = self::get_valid_file_path($upload_folder->repertoire_path.$path.explnum::clean_explnum_file_name($file['name'][0]['value']));
				move_uploaded_file($file['tmp_name'][0]['value'], $file_path);
				$values[] = array(
						'value' => basename($file_path),
						'type' => $_POST[$var_name][0]['type']
				);
				${$var_name} = $values;
			}
		}		
		if ((empty(${$var_name})) || (${$var_name}[0]['value'] == '' && $_POST[$var_name][0]['default_value'])) {
			${$var_name} = [
			    [
			        'value' => $_POST[$var_name][0]['default_value'],
                    'type' => $_POST[$var_name][0]['type'],
		        ],
			];
		}
		return parent::get_values_from_form($instance_name, $property, $uri_item);
	}
	
	public static function get_valid_file_path($file_path) {
		$file_path = str_replace('//', '/', $file_path);
		if (!file_exists($file_path)) {
			return $file_path;
		}
		$i = 1;
		$file_info = pathinfo($file_path);
		do {
			$file_path = $file_info['dirname'].'/'.$file_info['filename'].'_'.$i.'.'.$file_info['extension'];
			$i++;
		} while (file_exists($file_path));
		return $file_path;
	}
} // end of onto_common_datatype_small_text
