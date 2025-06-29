<?php
// +-------------------------------------------------+
// � 2002-2012 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: cms_module_breadcrumb_datasource_sections.class.php,v 1.6.18.1 2025/02/25 13:40:19 dgoron Exp $

if (stristr($_SERVER['REQUEST_URI'], ".class.php")) die("no access");

class cms_module_breadcrumb_datasource_sections extends cms_module_common_datasource_list{
	
	public function __construct($id=0){
		parent::__construct($id);
	}
	
	public function get_available_selectors(){
		return array(
			'cms_module_common_selector_section',
			'cms_module_common_selector_env_var',
			'cms_module_common_selector_generic_parent_section',
			'cms_module_common_selector_global_var'
		);
	}
	
	/*
	 * R�cup�ration des donn�es de la source...
	 */
	public function get_datas(){
		//on commence par r�cup�rer l'identifiant retourn� par le s�lecteur...
		$selector = $this->get_selected_selector();
		if($selector){
			$section_id = $selector->get_value();
			$section_ids = $this->filter_datas("sections",array($section_id));
			if(isset($section_ids[0]) && $section_ids[0]){
				$sections = array();
				$section_id = $section_ids[0];
				$datas = array(
					'sections' => array()
				);
				$i=0;
				do {
					$i++;
					$query = "select id_section,section_num_parent from cms_sections where id_section = '".($section_id*1)."'";
					$result = pmb_mysql_query($query);
					if(pmb_mysql_num_rows($result)){
						$row = pmb_mysql_fetch_object($result);
						$section_id = $row->section_num_parent;
						$datas['sections'][] = $row->id_section;
						
					}else{
						break;
					}
				//en th�orie on sort toujours, mais comme c'est un pays formidable, on lock � 100 it�rations...
				}while ($row->section_num_parent != 0 || $i>100);
				$datas['sections'] = array_reverse($datas['sections']);
				return $datas;
			}
		}
		return false;
	}
}