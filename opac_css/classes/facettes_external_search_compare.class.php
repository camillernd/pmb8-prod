<?php

// +-------------------------------------------------+
// � 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: facettes_external_search_compare.class.php,v 1.8.4.1 2025/01/30 09:08:06 tsamson Exp $

if (stristr($_SERVER['REQUEST_URI'], ".class.php")) die("no access");

require_once($class_path."/facette_search_compare.class.php");
require_once($class_path."/facettes_external.class.php");
require_once($class_path."/notice_affichage_unimarc.class.php");

class facettes_external_search_compare extends facette_search_compare {

	public function __construct() {
		parent::__construct();
	}

	/**
	 * Tableau d'identifiants de notices
	 */
	protected function get_objects_compare($facette_compare) {
		$objects_ids = array();
		$query = facettes_external::get_filter_query_by_facette($facette_compare[2], $facette_compare[3], array($facette_compare[1]));
		$query .= " JOIN ".static::$temporary_table_name." ON sub.recid=".static::$temporary_table_name.".rid";
		$result=pmb_mysql_query($query);
		while($row=pmb_mysql_fetch_object($result)){
			$objects_ids[]=$row->id_notice;
		}
		return $objects_ids;
	}

	protected function get_query_groupby($facette_groupby, $tmpArray) {
		$sub_queries = facettes_external::get_sub_queries($facette_groupby[1], $facette_groupby[2]);
		$selected_sources = facettes_external::get_selected_sources();
		$queries = array();
		foreach ($selected_sources as $source) {
			$queries[] = "SELECT value,recid FROM entrepot_source_".$source."
						WHERE recid IN (".implode(",", $tmpArray).")
					AND ((".implode(') OR (', $sub_queries)."))";
		}
		$query = "select value , recid as id_notice from ("
				.implode(' UNION ', $queries).") as sub";
		return $query;
	}

	/**
	 * On lance la comparaison � partir d'une liste d'identifiants
	 * Rempli la variables result
	 *
	 * @param object_ids
	 * @return true si succ�s message d'erreur sinon
	 */
	public function compare_from_objects($objects_ids){
		self::session_facette_compare($this);

		if(is_countable($this->facette_compare) && sizeof($this->facette_compare)){
			//on insert les notices externes de la recherche en table memoire
			self::gen_temporary_table_name();
			$query = "CREATE TEMPORARY TABLE ".static::$temporary_table_name." engine=memory SELECT rid FROM external_count WHERE rid IN (".$objects_ids.")";
			pmb_mysql_query($query);
			$query = "ALTER TABLE ".static::$temporary_table_name." engine=memory ADD INDEX notice_id_index BTREE (rid)";
			pmb_mysql_query($query);

			//pour toutes les facettes choisies en comparaison
			$this->build_result();

			//Si trop de r�sultat, la g�n�ration du tableau html sera trop longue = on coupe.
			if (is_countable($this->result) && is_countable($this->facette_compare)){
				if(sizeof($this->result)*sizeof($this->facette_compare) > $this->max_display){
					return 'facette_compare_too_more_result';
				}
			}
			return true;
		}else{
			//pas de r�sultat
			return 'facettes_compare_no_result';
		}
	}

	/**
	 * si une des facette n'est pas d�j� choisie pour comparer et n'est pas utilis� en recherche, on la rend active pour pouvoir etre utilis� en comparaison
	 * @param string $id l'id de la facette concern�e
	 * @param bool $available
	 */
	public function set_available_compare($id,$available=true){
		$this->facette_compare[$id]['available']=$available;
		$_SESSION['check_facettes_external_compare'][$id]['available']=$available;
	}

	/**
	 * Si un groupe n'est pas d�j� choisi et dont un �lement au moins est disponible pour la recherche, on le rend actif pour pouvoir etre utilis� en groupement
	 * @param integer $id l'id du groupe
	 * @param bool $available
	 */
	public function set_available_groupby($id,$available=true){
		$this->facette_groupby[$id]['available']=$available;
		$_SESSION['check_facettes_external_groupby'][$id]['available']=$available;
	}

	/**
	 * Classe permettant d'appeler l'affichage des notices
	 * Retire de la liste envoy�e en r�f�rence les notices d�j� affich�es
	 *
	 * @param string $notices_ids la liste des notices, s�par�es par ,
	 * @param integer $notice_nb le nombre de notices � afficher par passe
	 * @param integer $notice_tpl l'identifiant du template d'affichage, si null, affiche le header de la classe d'affichage
	 */
	public static function call_notice_display(&$notices_ids,$notice_nb,$notice_tpl){
		global $msg;
		global $opac_cart_allow, $opac_cart_only_for_subscriber, $charset;

		if ((($opac_cart_allow)&&(!$opac_cart_only_for_subscriber))||(($opac_cart_allow)&&($_SESSION["user_code"]))) $cart=1; else $cart=0;
		if ($nocart) $cart=0;

		$entrepots_localisations = array();
		$entrepots_localisations_sql = "SELECT * FROM entrepots_localisations ORDER BY loc_visible DESC";
		$res = pmb_mysql_query($entrepots_localisations_sql);
		while ($row = pmb_mysql_fetch_array($res)) {
			$entrepots_localisations[$row["loc_code"]] = array("libelle" => $row["loc_libelle"], "visible" => $row["loc_visible"]);
		}

		$notices_ids=explode(",",$notices_ids);

		$notices='';
		for($i_notice_nb=0;$i_notice_nb<$notice_nb;$i_notice_nb++) {
			if($notices_ids[$i_notice_nb]){
				$notices.='<li>';

				$current = new notice_affichage_unimarc($notices_ids[$i_notice_nb],"",$cart,0, $entrepots_localisations);
				$current->do_header();
				if($current->notice_header == ""){
					$current->notice_header = sprintf($msg['cart_notice_expired'],$notices_ids[$i_notice_nb]);
					$current->notice_expired = true;
				}

				//le panier
				if ($current->cart_allowed){
					if(isset($_SESSION["cart"]) && in_array("es".$notices_ids[$i_notice_nb], $_SESSION["cart"])) {
						$notices.="<a href='#' class=\"img_basket_exist\" title=\"".$msg['notice_title_basket_exist']."\"><img src=\"".get_url_icon('basket_exist.png', 1)."\" style='border:0px' alt=\"".htmlentities($msg['notice_title_basket_exist'],ENT_QUOTES, $charset)."\" /></a>";
					} else {
						$notices.="<a href=\"cart_info.php?id=es".$notices_ids[$i_notice_nb]."&header=".rawurlencode(strip_tags($current->notice_header))."\" target=\"cart_info\" title=\"".htmlentities($msg['notice_title_basket'],ENT_QUOTES, $charset)."\"><img src=\"".get_url_icon("basket_small_20x20.png", 1)."\" style='border:0px' alt=\"".htmlentities($msg['notice_title_basket'],ENT_QUOTES, $charset)."\"></a>";
					}
				}else {
					$notices.="";
				}
				$notices.=$current->notice_header;

				unset($notices_ids[$i_notice_nb]);
				$notices.='</a>';
				$notices.='</li>';
			}
		}
		if(is_countable($notices_ids) && sizeof($notices_ids)){
			$notices_ids=implode(',', $notices_ids);
		}
		return $notices;
	}

	public static function get_compare_checked_session() {
	    if (isset($_SESSION['check_facettes_external_compare'])) {
	        return $_SESSION['check_facettes_external_compare'];
	    }
	    return null;
	}

	public static function set_compare_checked_session($facettes_compare) {
		$_SESSION['check_facettes_external_compare'] = $facettes_compare;
	}

	public static function unset_compare_checked_session() {
		unset($_SESSION['check_facettes_external_compare']);
	}

	public static function get_groupby_checked_session() {
	    if (isset($_SESSION['check_facettes_external_groupby'])) {
	        return $_SESSION['check_facettes_external_groupby'];
	    }
		return null;
	}

	public static function set_groupby_checked_session($facettes_groupby) {
		$_SESSION['check_facettes_external_groupby'] = $facettes_groupby;
	}

	public static function unset_groupby_checked_session() {
		unset($_SESSION['check_facettes_external_groupby']);
	}

	public static function get_formatted_value($id_critere, $id_ss_critere, $value) {
		return facettes_external::get_formatted_value($id_critere, $id_ss_critere, $value);
	}
}
