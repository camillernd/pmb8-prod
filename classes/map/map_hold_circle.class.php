<?php

// +-------------------------------------------------+
// � 2002-2010 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: map_hold_circle.class.php,v 1.4.4.1 2025/04/24 14:45:33 qvarin Exp $

if (stristr($_SERVER['REQUEST_URI'], ".class.php"))
    die("no access");
require_once($class_path . "/map/map_hold.class.php");
require_once($class_path . "/map/map_hold_polygon.class.php");
require_once($class_path . "/map/map_coord.class.php");

/**
 * class map_hold_circle
 *
 */
class map_hold_circle extends map_hold_polygon {
    /** Aggregations: */
    /** Compositions: */
    /*     * * Attributes: ** */

    /**
     * Centre du cercle
     * @access protected
     */
    protected $center;

    /**
     * Rayon du cercle
     * @access protected
     */
    protected $radius;

    /**
     * Nombre de points pour tracer le polygone approchant
     * @access protected
     */
    protected $nb_points;

    /**
     *
     * @param map_coord coord Coordonn�es du centre
     * @return void
     * @access public
     */
    public function set_center($center) {}

    /**
     *
     * @param int nb_points Nombre de points pour le calcul du polygone approchant
     * @return void
     * @access public
     */
    public function set_nb_points($nb_points) {}

    /**
     * Retourne de nombre de points utilis�s pour le polygone approchant
     *
     * @return int
     * @access public
     */
    public function get_nb_points() {
        return 0;
    }

    /**
     * Retourne la classe repr�sentant les coordonn�es du centre du cercle
     *
     * @return map_coord
     * @access public
     */
    public function get_center() {
        $this->center;
    }

    /**
     *
     *
     * @param float radius Rayon du cercle

     * @return void
     * @access public
     */
    public function set_radius($radius) {}

    /**
     * Retourne le rayon du cercle
     *
     * @return float
     * @access public
     */
    public function get_radius() {
        return $this->radius;
    }

    /**
     * Constructeur
     *
     * @param map_coord center Centre du cercle
     * @param float radius Rayon du cercle
     * @param int nb_points Nombre de points pour g�n�rer le polygone approchant
     * @return void
     * @access public
     */
    public function __construct($center, $radius, $nb_points) {}

    /**
     *
     * @return string
     * @access public
     */
    public function get_hold_type() {
        return "";
    }

    /**
     * M�thode qui calcule les points du polygone approchant
     *
     * @return void
     * @access protected
     */
    protected function fill_coords() {}

    static public function createRegularPolygon ($origin, $radius, $sides) {
        $angle = pi() * ((1/$sides) - (1/2));
        $rotatedAngle = 0;
        $points = [];
        $lon = $radius / ((40075 * cos(($origin['y']*pi()/180))/360));
        $lat = $radius/111.32;
        for($i=0; $i<$sides; $i++) {
            $rotatedAngle = $angle + ($i * 2 * pi() / $sides);

            $point=[ 'x'=>0,'y'=>0];
            $point['x'] = $origin['x'] + ($lon * cos($rotatedAngle));
            $point['y'] = $origin['y'] + ($lat * sin($rotatedAngle));
            $points[] = $point;
        }
        return $points;
    }

    static public function getWKT($points){
        $wkt='';
        foreach($points as $point){
            if($wkt) $wkt.= ",";
            $wkt.=$point['x']." ".$point['y'];
        }
        $wkt.= ','.$points[0]['x']." ".$points[0]['y'];
        $wkt = 'POLYGON(('.$wkt.'))';
        return $wkt;
    }
}

// end of map_hold_circle