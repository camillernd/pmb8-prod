<?php 
// +-------------------------------------------------+
// � 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: bannette_abon.class.php,v 1.7 2023/11/30 08:55:43 dgoron Exp $

if (stristr($_SERVER['REQUEST_URI'], ".class.php")) die("no access");

global $class_path;
require_once($class_path."/bannette.class.php");

class bannette_abon{
	protected $num_bannette;
	
	protected $num_empr;
	
	protected $groups;
	
	public function __construct($num_bannette=0,$num_empr=0) {
		$this->num_bannette = intval($num_bannette);
		$this->num_empr = intval($num_empr);
		$this->fetch_data();
	}
	
	protected function fetch_data() {
		$this->groups=array();
		$query = "select id_groupe, libelle_groupe from groupe join empr_groupe on groupe_id=id_groupe where empr_id='".$this->num_empr."'";
		$result = pmb_mysql_query($query);
		if (pmb_mysql_num_rows($result)) {
			while ($grp_temp=pmb_mysql_fetch_object($result)) {
				$this->groups[$grp_temp->id_groupe]=$grp_temp->libelle_groupe;
			}
		}
	}
	
	public function save_bannette_abon($bannette_abon, $filtered_list=array()) {
	    $tableau_bannettes = $this->tableau_gerer_bannette("PUB");
		foreach ($tableau_bannettes as $bannette) {
		    if(!count($filtered_list) || in_array($bannette->id_bannette, $filtered_list)) {
		        pmb_mysql_query("delete from bannette_abon where num_empr = '" . $this->num_empr . "' and num_bannette = '".$bannette->id_bannette."' ");
		        if (isset($bannette_abon[$bannette->id_bannette]) && $bannette_abon[$bannette->id_bannette]) {
		            pmb_mysql_query("replace into bannette_abon (num_empr, num_bannette) values ('" . $this->num_empr . "', '".$bannette->id_bannette."')");
		        }
		    }
		}
	}
	
	public function delete_bannette_abon($bannette_abon, $filtered_list=array()) {
		$tableau_bannettes = $this->tableau_gerer_bannette("PRI");
		foreach ($tableau_bannettes as $bannette) {
		    if(!count($filtered_list) || in_array($bannette->id_bannette, $filtered_list)) {
    			if (isset($bannette_abon[$bannette->id_bannette]) && $bannette_abon[$bannette->id_bannette]) {
    				pmb_mysql_query("delete from bannette_abon where num_empr = '" . $this->num_empr . "' and num_bannette = '".$bannette->id_bannette."' ");
    				pmb_mysql_query("delete from bannette_contenu where num_bannette='".$bannette->id_bannette."' ");
    				$req_eq = pmb_mysql_query("select num_equation from bannette_equation where num_bannette = '".$bannette->id_bannette."' ");
    				$eq = pmb_mysql_fetch_object($req_eq);
    				pmb_mysql_query("delete from equations where id_equation = '" . $eq->num_equation . "' ");
    				pmb_mysql_query("delete from bannette_equation where num_bannette = '".$bannette->id_bannette."' ");
    				pmb_mysql_query("delete from bannettes where id_bannette = '".$bannette->id_bannette."' ");
    			}
		    }
		}
	}
	
	// retourne un tableau des bannettes possibles de l'abonn� : les priv�es / les publiques : celles de sa cat�gorie et/ou celles auxquelles il est abonn�
	public function tableau_gerer_bannette($priv_pub='PUB') {
		$query = "select empr_categ, libelle from empr join empr_categ on empr.empr_categ = empr_categ.id_categ_empr where id_empr =".$this->num_empr;
		$result = pmb_mysql_query($query);
		$empr_categ = pmb_mysql_result($result, 0, 'empr_categ');
		$cat_l = pmb_mysql_result($result, 0, 'libelle');
		
		$tableau_bannette = array();
		//R�cup�ration des infos des bannettes
		if ($priv_pub == 'PUB') {
			$access_liste_id = array();
			
			$query = "SELECT empr_categ_num_bannette FROM bannette_empr_categs WHERE empr_categ_num_categ=".$empr_categ;
			$result = pmb_mysql_query($query);
			while ($row = pmb_mysql_fetch_object($result)) {
				$access_liste_id[] = $row->empr_categ_num_bannette;
			}
			$query = "select groupe_id from empr_groupe where empr_id=".$this->num_empr." AND groupe_id != 0";//En cr�ation de lecteur une entr�e avec groupe_id = 0 est cr��e ...
			$result = pmb_mysql_query($query);
			$groups = array();
			while ($row=pmb_mysql_fetch_object($result)) {
				$groups[] = $row->groupe_id;
			}
			if (count($groups)) {
				$query = "SELECT empr_groupe_num_bannette FROM bannette_empr_groupes WHERE empr_groupe_num_groupe IN (".implode(",",$groups).")";
				$result = pmb_mysql_query($query);
				while ($row = pmb_mysql_fetch_object($result)) {
					$access_liste_id[] = $row->empr_groupe_num_bannette;
				}
			}
			
			if (count($access_liste_id)) {
				$access_liste_id = array_unique($access_liste_id);
					
			} else {
				$access_liste_id[] = 0;
			}
			
			$restrict = "((id_bannette IN (".implode(',',$access_liste_id).")) or (bannette_opac_accueil = 1))";
			
			$requete = "select distinct id_bannette, comment_public from bannettes join bannette_abon on num_bannette=id_bannette where num_empr='".$this->num_empr."' and proprio_bannette=0 ";
			$requete .= " union select distinct id_bannette, comment_public from bannettes where ".$restrict." and proprio_bannette=0 ";
			$requete .= " order by comment_public ";
		} else {
			$requete = "select distinct id_bannette, comment_public from bannettes where proprio_bannette='".$this->num_empr."' ";
			$requete .= " order by comment_public ";
		}
		$resultat = pmb_mysql_query($requete);
		while ($r = pmb_mysql_fetch_object($resultat)) {
			$tableau_bannette[] = new bannette($r->id_bannette);
		}
		return $tableau_bannette;
	}
	
	// permet d'afficher un formulaire de gestion des abonnements aux bannettes du lecteur
	// param�tres :
	//	$bannettes : les num�ros des bannettes s�par�s par les ',' toutes si vides
	//	$aff_notices_nb : nombres de notices affich�es : toutes = 0
	//	$mode_aff_notice : mode d'affichage des notices, REDUIT (titre+auteur principal) ou ISBD ou PMB ou les deux : dans ce cas : (titre + auteur) en ent�te du truc
	//	$depliable : affichage des notices une par ligne avec le bouton de d�pliable
	//	$link_to_bannette : lien pour afficher le contenu de la bannette
	//	$htmldiv_id="etagere-container", $htmldiv_class="etagere-container", $htmldiv_zindex="" : les id, class et zindex du <DIV > englobant le r�sultat de la fonction
	//	$liens_opac : tableau contenant les url destinatrices des liens si voulu
	public function gerer_abon_bannette($priv_pub="PUB", $link_to_bannette="", $htmldiv_id="bannette-container", $htmldiv_class="bannette-container", $htmldiv_zindex="") {
		global $msg;
	
		// r�cup�ration des bannettes
		$tableau_bannettes = $this->tableau_gerer_bannette($priv_pub);
	
		if (!count($tableau_bannettes)) return "";
	
		$retour_aff = "<div id='$htmldiv_id' class='$htmldiv_class'";
		if ($htmldiv_zindex) $retour_aff .= " zindex='$htmldiv_zindex' ";
		$retour_aff .= " >";
		$retour_aff .= "<form name='bannette_abonn_" . $priv_pub . "' method='post' >";
		$retour_aff .= "<input type='hidden' name='lvl' value='bannette_gerer' />";
		$retour_aff .= "<input type='hidden' name='enregistrer' value='$priv_pub' />";
		if($priv_pub == 'PRI') {
		    list_bannettes_abon_priv_ui::set_id_empr($this->num_empr);
		    $retour_aff .= list_bannettes_abon_priv_ui::get_instance(array('proprio_bannette' => $this->num_empr))->get_display_list();
		} else {
		    list_bannettes_abon_pub_ui::set_id_empr($this->num_empr);
		    $retour_aff .= list_bannettes_abon_pub_ui::get_instance(array('num_empr' => $this->num_empr, 'proprio_bannette' => 0))->get_display_list();
		}
		$retour_aff .= "
					<INPUT type='button' class='bouton' value=\"";
		if ($priv_pub == "PUB") {
			$retour_aff .= $msg['dsi_bannette_gerer_sauver'] . "\" onclick=\"save_bannette_abon();return false;\" />";
		} else {
			$retour_aff .= $msg['dsi_bannette_gerer_supprimer'] . "\" onclick=\"delete_bannette_abon();return false;\" />";
		}
		$retour_aff.= "</form></div>";
		return $retour_aff;
	}
	
	public function get_groups() {
		return $this->groups;
	}
}// end class
