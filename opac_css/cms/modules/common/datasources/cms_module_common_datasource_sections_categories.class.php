<?php
// +-------------------------------------------------+
// � 2002-2012 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: cms_module_common_datasource_sections_categories.class.php,v 1.8.8.1 2025/02/10 15:03:30 dgoron Exp $

if (stristr($_SERVER['REQUEST_URI'], ".class.php")) die("no access");

class cms_module_common_datasource_sections_categories extends cms_module_common_datasource_list{
	
	public function __construct($id=0){
		parent::__construct($id);
		$this->sortable = true;
		$this->limitable = true;
		$this->paging = true;
	}
	/*
	 * On d�fini les s�lecteurs utilisable pour cette source de donn�e
	 */
	public function get_available_selectors(){
		return array(
			"cms_module_common_selector_record_permalink",
			"cms_module_common_selector_env_var",
		);
	}

	/*
	 * On d�fini les crit�res de tri utilisable pour cette source de donn�e
	 */
	protected function get_sort_criterias() {
		return array (
			"publication_date",
			"id_section",
			"section_title",
			"section_order",
			"pert"
		);
	}
	
	protected function get_query_base() {
		$selector = $this->get_selected_selector();
		if ($selector) {
			if(!isset($this->parameters['operator_between_authorities'])) $this->parameters['operator_between_authorities'] = 'or';
			switch ($this->parameters["operator_between_authorities"]) {
				case 'and':
					$query = "select distinct notices_categories.num_noeud
						from notices_categories
					    where notices_categories.notcateg_notice = '".intval($selector->get_value())."'";
					$result = pmb_mysql_query($query);
					$descriptors = array();
					if($result && (pmb_mysql_num_rows($result) > 0)){
						while($row = pmb_mysql_fetch_object($result)){
							$descriptors[] = $row->num_noeud;
						}
					}
					if(count($descriptors)) {
						$query = "select distinct id_section,if(section_start_date != '0000-00-00 00:00:00',section_start_date,section_creation_date) as publication_date, notices_categories.num_noeud
							from cms_sections join cms_sections_descriptors on id_section=num_section
							where cms_sections_descriptors.num_section != '".intval($selector->get_value())."' and cms_sections_descriptors.num_noeud IN (".implode(',', $descriptors).")
							group by id_section
							having count(id_section) = ".count($descriptors);
						return $query;
					}
					break;
				case 'or':
				default:
					$query = "select distinct id_section,if(section_start_date != '0000-00-00 00:00:00',section_start_date,section_creation_date) as publication_date, notices_categories.num_noeud from cms_sections join cms_sections_descriptors on id_section=num_section join notices_categories on cms_sections_descriptors.num_noeud=notices_categories.num_noeud and notcateg_notice = '".intval($selector->get_value())."'";
					return $query;
					break;
			}
		}
		return false;
	}
	
	/*
	 * R�cup�ration des donn�es de la source...
	 */
	public function get_datas(){
	    $return = array();
	    $return["sections"] = $this->get_sorted_datas('id_section', 'num_noeud');
	    if($return) {
	        $return["sections"] = $this->filter_datas("sections", $return["sections"]);
	        
	        // Pagination
	        if ($this->paging && isset($this->parameters['paging_activate']) && $this->parameters['paging_activate'] == "on") {
	            $return["paging"] = $this->inject_paginator($return['sections']);
	            $return['sections'] = $this->cut_paging_list($return['sections'], $return["paging"]);
	        } else if ($this->parameters["nb_max_elements"] > 0) {
	            $return["sections"] = array_slice($return["sections"], 0, $this->parameters["nb_max_elements"]);
	        }
	        return $return;
	    }
	    return $return;
	}
}