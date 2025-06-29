<?php
// +-------------------------------------------------+
// | 2002-2011 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: cms_article.class.php,v 1.43.2.1.2.1 2025/03/17 08:15:42 qvarin Exp $

if (stristr($_SERVER['REQUEST_URI'], ".class.php")) die("no access");

global $class_path;
global $charset;
global $gestion_acces_active, $gestion_acces_empr_cms_article;

require_once $class_path.'/cms/cms_editorial.class.php';
require_once $class_path.'/audit.class.php';
require_once $class_path.'/acces.class.php';

class cms_article extends cms_editorial {

	protected $detail;
	protected $num_section;

	public function __construct($id=0, $num_parent=0) {
		//on g�re les propri�t�s communes dans la classe parente
		parent::__construct($id,"article",$num_parent);

		$this->opt_elements = array(
			'contenu' => true
		);
	}

	protected function fetch_data(){
		if(!$this->id)
			return false;

		// les infos g�n�rales...
		$rqt = "SELECT
			article_num_type,
			article_title,
			article_resume,
			article_contenu,
			article_publication_state,
			article_start_date,
			article_end_date,
			num_section,
			article_creation_date,
			article_update_timestamp
		FROM cms_articles WHERE id_article ='".$this->id."'";
		$res = pmb_mysql_query($rqt);
		if (pmb_mysql_num_rows($res)) {

			$row = pmb_mysql_fetch_object($res);
			$this->num_type = $row->article_num_type;
			$this->title = $row->article_title;
			$this->resume = $row->article_resume;
			$this->contenu = $row->article_contenu;
			$this->publication_state = $row->article_publication_state;
			$this->start_date = $row->article_start_date;
			$this->end_date = $row->article_end_date;
			$this->num_parent = $row->num_section;
			$this->create_date = $row->article_creation_date;
			$this->last_update_date = $row->article_update_timestamp;

			unset($row);
			pmb_mysql_free_result($res);
		}

		if(strpos($this->start_date,"0000-00-00")!== false){
			$this->start_date = "";
		}
		if(strpos($this->end_date,"0000-00-00")!== false){
			$this->end_date = "";
		}
	}

	public function save(){

		$audit_id = $this->id;
		if($this->id){
			$save = "update ";
			$order = "";
			$clause = "where id_article = '".$this->id."'";
		}else{
			$save = "insert into ";

			//on place le nouvel article � la fin par d�faut
			$query = "SELECT id_article FROM cms_articles WHERE num_section=".addslashes($this->num_parent);
			$result = pmb_mysql_query($query);
			$order = ",article_order = '".(pmb_mysql_num_rows($result)+1)."' ";

			$clause = "";
		}
		$save.= "cms_articles set
		article_title = '".addslashes($this->title)."',
		article_resume = '".addslashes($this->resume)."',
		article_contenu = '".addslashes($this->contenu)."',
		article_publication_state = '".addslashes($this->publication_state)."',
		article_start_date = '".addslashes($this->start_date)."',
		article_end_date = '".addslashes($this->end_date)."',
		num_section = '".addslashes($this->num_parent)."',
		article_num_type = '".$this->num_type."' ".
		(!$this->id ? ",article_creation_date=sysdate() " :"")."
		$order"."
		$clause";
		pmb_mysql_query($save);
		if(!$this->id) $this->id = pmb_mysql_insert_id();
		//au tour des descripteurs...
		//on commence par tout retirer...
		$del = "delete from cms_articles_descriptors where num_article = '".$this->id."'";
		pmb_mysql_query($del);
		$this->get_descriptors();
		for($i=0 ; $i<count($this->descriptors) ; $i++){
			$rqt = "insert into cms_articles_descriptors set num_article = '".$this->id."', num_noeud = '".$this->descriptors[$i]."',article_descriptor_order='".$i."'";
			pmb_mysql_query($rqt);
		}

		//et maintenant le logo...
		$this->save_logo();

		//enfin les �l�ments du type de contenu
		$types = new cms_editorial_types("article");
		$types->save_type_form($this->num_type,$this->id);

		$this->save_concepts();

		$this->maj_indexation();

		$this->save_documents();

		//bouton pour le cache
		$upd = "UPDATE cms_articles SET article_update_timestamp = now() WHERE id_article = '".$this->id."'";
		pmb_mysql_query($upd);

		//Audit
		if (!$audit_id) {
			audit::insert_creation(AUDIT_EDITORIAL_ARTICLE, $this->id);
		} else {
			audit::insert_modif(AUDIT_EDITORIAL_ARTICLE, $this->id);
		}

		//traitement des droits acces empr_cms_article
		global $gestion_acces_active;
		if ($gestion_acces_active==1) {
			$ac = new acces();
			global $gestion_acces_empr_cms_article;
			if ($gestion_acces_empr_cms_article==1) {
				$dom_8= $ac->setDomain(8);
				if ($audit_id) {
					$dom_8->storeUserRights(1, $this->id);
				} else {
					$dom_8->storeUserRights(0, $this->id);
				}
			}
		}
	}

	public function duplicate($num_parent = 0) {

		if (!$num_parent) $num_parent = $this->num_parent;

		// On place le nouvel article � la fin par d�faut
		$query = "SELECT id_article FROM cms_articles WHERE num_section = $num_parent";
		$result = pmb_mysql_query($query);
		$order = ",article_order = 1";
		if ($result) {
		    $order = ",article_order = '" . (pmb_mysql_num_rows($result) + 1) . "'";
		}

		$insert = "insert into cms_articles set
		article_title = '" . addslashes($this->title) . "',
		article_resume = '" . addslashes($this->resume) . "',
		article_contenu = '" . addslashes($this->contenu) . "',
		article_logo = '" . addslashes($this->logo->data) . "',
		article_publication_state ='" . addslashes($this->publication_state) . "',
		article_start_date = '" . addslashes($this->start_date) . "',
		article_end_date = '" . addslashes($this->end_date) . "',
		num_section = '$num_parent',
		article_num_type = '$this->num_type',
		article_creation_date=sysdate() $order";

		pmb_mysql_query($insert);
		$id = pmb_mysql_insert_id();

		// Au tour des descripteurs...
		$this->get_descriptors();
		for ($i = 0; $i < count($this->descriptors); $i++) {
			$rqt = "insert into cms_articles_descriptors set num_article = '$id', num_noeud = '".$this->descriptors[$i]['id']."',article_descriptor_order='$i'";
			pmb_mysql_query($rqt);
		}

		// On cr�e la nouvelle instance
		$new_article = new cms_article($id);

		// On duplique les concepts
	    $new_article->duplicate_concepts($this->id);

		// Enfin les �l�ments du type de contenu
		$types = new cms_editorial_types("article");
		$types->duplicate_type_form($this->num_type,$id,$this->id);
		$new_article->maj_indexation();

		$new_article->documents_linked = $this->get_documents();
		$new_article->save_documents();

		// Audit
		audit::insert_creation(AUDIT_EDITORIAL_ARTICLE, $id);

		// Traitement des droits acces empr_cms_article
		global $gestion_acces_active;
		if ($gestion_acces_active == 1) {
			$ac = new acces();
			global $gestion_acces_empr_cms_article;
			if ($gestion_acces_empr_cms_article == 1) {
				$dom_8 = $ac->setDomain(8);
				$dom_8->storeUserRights(0, $id);
			}
		}

		return $id;
	}

	public function get_parent_selector(){
		return $this->_recurse_parent_select();
	}

	protected function _recurse_parent_select($parent=0,$lvl=0){
		global $charset;
		$opts = "";
		$parent = intval($parent);
		$rqt = "select id_section, section_title from cms_sections where section_num_parent = '".$parent."'";
		$res = pmb_mysql_query($rqt);
		if(pmb_mysql_num_rows($res)){
			while($row = pmb_mysql_fetch_object($res)){
				$opts.="
				<option value='".$row->id_section."'".($this->num_parent == $row->id_section ? " selected='selected'" : "").">".str_repeat("&nbsp;&nbsp;",$lvl).htmlentities($row->section_title,ENT_QUOTES,$charset)."</option>";
				$opts.=$this->_recurse_parent_select($row->id_section,$lvl+1);
			}
		}
		return $opts;
	}

	public function update_parent_section($num_section,$order=0){
		$this->num_section = intval($num_section);
		$order = intval($order);
		$update = "update cms_articles set num_section ='".$this->num_section."', article_order = '".$order."' where id_article = '".$this->id."'";
		pmb_mysql_query($update);
	}

	protected function is_deletable(){
		return true;
	}

	public static function get_format_data_structure($type="article",$full=true){
		return cms_editorial::get_format_data_structure($type,$full);
	}

	public function get_detail()
	{
		global $include_path;

		if (isset($this->detail)) {
			return $this->detail;
		}

		$template = "{$include_path}/templates/cms/list/article_detail_subst.tpl.html";
		if (!is_file($template)) {
			$template = "{$include_path}/templates/cms/article_detail.tpl.html";
		}
		$this->detail = "";
		if (is_file($template)) {
			$h2o = \H2o_collection::get_instance($template);
			$this->detail = $h2o->render([
				"article" => $this
			]);
		}
		return $this->detail;
	}
}