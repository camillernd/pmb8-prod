<?php

// +-------------------------------------------------+
// � 2002-2010 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: map_layer_model_sur_location.class.php,v 1.4.8.1 2025/04/25 12:05:30 qvarin Exp $

if (stristr($_SERVER['REQUEST_URI'], ".class.php")) die("no access");

global $class_path;
require_once($class_path . "/map/map_layer_model.class.php");

/**
 * class map_layer_model_sur_location
 * Classe repr�sentant le mod�le de donn�es pour des sur-localisations
 */
class map_layer_model_sur_location extends map_layer_model {
    /** Aggregations: */
    /** Compositions: */
    /*     * * Attributes: ** */

    /**
     * Va chercher et instancier les emprises correspondantes.
     * Peut appeler la classe map_model_authority pour les emprises des autorit�s
     * utilis�es pour indexer la notice
     *
     * @return void
     * @access public
     */
    public function fetch_datas() {
        global $pmb_map_holds_location_color;

        $this->holds = array();

        if (count($this->ids) > 0 && $this->ids[0]) {
            $req = "select map_emprises.map_emprise_id, map_emprises.map_emprise_obj_num, AsText(map_emprises.map_emprise_data) as map, map_hold_areas.bbox_area as bbox_area, map_hold_areas.center as center from map_emprises join map_hold_areas on map_emprises.map_emprise_id = map_hold_areas.id_obj where map_emprises.map_emprise_type=" . TYPE_SUR_LOCATION . " and map_emprises.map_emprise_obj_num in (" . implode(",", $this->ids) . ")";
            $res = pmb_mysql_query($req);
            if (pmb_mysql_num_rows($res)) {
                while ($r = pmb_mysql_fetch_object($res)) {
                    $geometric = strtolower(substr($r->map, 0, strpos($r->map, "(")));
                    $hold_class = "map_hold_" . $geometric;
                    if (class_exists($hold_class)) {
                        $emprise = new $hold_class("sur_location", $r->map_emprise_obj_num, $r->map);
                        $emprise->set_normalized_bbox_area($r->bbox_area);
                        $emprise->set_center($r->center);
                        $this->holds[$r->map_emprise_id] = $emprise;
                    }
                }
            }
        }
        $this->color = $pmb_map_holds_location_color;
    }

// end of member function fetch_datas

    public function get_layer_model_type() {
        return "sur_location";
    }

    public function get_layer_model_name() {
        return "sur_location";
    }
}

// end of map_layer_model_records
