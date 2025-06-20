<?php
// +-------------------------------------------------+
// � 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: coordonnees.inc.php,v 1.10.16.1 2025/05/12 15:05:27 dbellamy Exp $
if (stristr($_SERVER['REQUEST_URI'], ".inc.php")) {
    die("no access");
}

global $class_path;

// Variables definies dans le template ./selectors/templates/sel_coordonnees.tpl.php
global $sel_header, $sel_footer, $jscript;

// Variables definies dans l'appel
global $caller, $page, $param1, $param2, $param3, $id_bibli, $no_display, $bt_ajouter;

// la variable $caller, pass�e par l'URL, contient le nom du form appelant
$base_url = "./select.php?what=coord&caller=$caller&param1=$param1&param2=$param2&param3=$param3&id_bibli=$id_bibli&no_display=$no_display&bt_ajouter=$bt_ajouter";

// contenu popup s�lection fournisseur
require_once './selectors/templates/sel_coordonnees.tpl.php';
require_once $class_path . '/entites.class.php';

// affichage du header
print $sel_header;

print $jscript;

$page = intval($page);
if (! $page) {
    $page = 1;
}
show_results($page);

// affichage des membres de la page
function show_results($page = 0)
{
    global $nb_per_page;
    global $base_url;
    global $caller;
    global $charset;
    global $msg;
    global $id_bibli;
    global $no_display;

    // on r�cup�re le nombre de lignes qui vont bien
    $nbr_lignes = entites::count_coordonnees($id_bibli);
   $debut = ($page - 1) * $nb_per_page;

    if ($nbr_lignes) {
        // on lance la vraie requ�te
        $res = entites::get_coordonnees($id_bibli, '-1', $debut, $nb_per_page);

        while ($row = pmb_mysql_fetch_object($res)) {

            $adresse = '';
            $adresse1 = '';
            if ($row->libelle != '') {
                $adresse = htmlentities(addslashes($row->libelle), ENT_QUOTES, $charset) . "\\n";
                $adresse1 = htmlentities($row->libelle, ENT_QUOTES, $charset);
            }
            if ($row->contact != '') {
                $adresse .= htmlentities(addslashes($row->contact), ENT_QUOTES, $charset) . "\\n";
            }
            $adresse1 .= ' (';
            if ($row->adr1 != '') {
                $adresse .= htmlentities(addslashes($row->adr1), ENT_QUOTES, $charset) . "\\n";
                $adresse1 .= htmlentities($row->adr1, ENT_QUOTES, $charset) . ' ';
            }
            if ($row->adr2 != '') {
                $adresse .= htmlentities(addslashes($row->adr2), ENT_QUOTES, $charset) . "\\n";
                $adresse1 .= htmlentities($row->adr2, ENT_QUOTES, $charset) . ' ';
            }
            if ($row->cp != '') {
                $adresse .= htmlentities(addslashes($row->cp), ENT_QUOTES, $charset) . ' ';
                $adresse1 .= htmlentities($row->cp, ENT_QUOTES, $charset) . ' ';
            }
            if ($row->ville != '') {
                $adresse .= htmlentities(addslashes($row->ville), ENT_QUOTES, $charset);
                $adresse1 .= htmlentities($row->ville, ENT_QUOTES, $charset);
            }
            $adresse1 .= ')';

            print pmb_bidi("
			<a href='#' onclick=\"set_parent('$caller', '$row->id_contact', '$adresse' )\">$adresse1</a>");
            print "<br />";
        }
        pmb_mysql_free_result($res);

        // constitution des liens

        $nbepages = ceil($nbr_lignes / $nb_per_page);
        $suivante = $page + 1;
        $precedente = $page - 1;

        // affichage du lien pr�c�dent si n�cessaire
        print '<hr /><div class="center">';
        if ($precedente > 0) {
            print "<a href='$base_url&page=$precedente&nbr_lignes=$nbr_lignes&no_display=$no_display'><img src='" . get_url_icon('left.gif') . "' style='border:0px; margin:3px 3px' title='$msg[48]' alt='[$msg[48]]' class='align_middle' /></a>";
        }
        for ($i = 1; $i <= $nbepages; $i ++) {
            if ($i == $page) {
                print "<b>$i/$nbepages</b>";
            }
        }

        if ($suivante <= $nbepages) {
            print "<a href='$base_url&page=$suivante&nbr_lignes=$nbr_lignes&no_display=$no_display'><img src='" . get_url_icon('right.gif') . "' style='border:0px; margin:3px 3px' title='$msg[49]' alt='[$msg[49]]' class='align_middle' /></a>";
        }
    }
    print '</div>';
}

print $sel_footer;
