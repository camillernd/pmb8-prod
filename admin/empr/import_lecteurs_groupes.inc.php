<?php
// +-------------------------------------------------+
// � 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: import_lecteurs_groupes.inc.php,v 1.20.6.1.2.2 2025/04/30 13:03:02 rtigero Exp $

if (stristr($_SERVER['REQUEST_URI'], ".inc.php")) die("no access");

global $class_path;
global $action, $import_launch, $Sep_Champs, $type_import;
global $lang;

require_once $class_path."/emprunteur.class.php";
require_once $class_path."/import/import_empr.class.php";

//La structure du fichier texte doit �tre la suivante :
//Num�ro identifiant/Nom/Pr�nom/Rue/Compl�ment de rue/Code postal/Commune/T�l�phone/Ann�e de date de naissance/Classe/Sexe/Tel2/Mail/Profession/Message

function show_import_choix_fichier() {
	global $msg, $charset;
	global $current_module ;

print "
<form class='form-$current_module' name='form1' ENCTYPE=\"multipart/form-data\" method='post' action=\"./admin.php?categ=empr&sub=implec&action=1\">
<h3>Choix du fichier</h3>
<div class='form-contenu'>
	<div class='row'>
		<label class='etiquette' for='form_import_lec'>".$msg["import_lec_fichier"]."</label>
        <input name='import_lec' accept='text/plain' type='file' class='saisie-80em' size='40'>
        <label class='etiquette' for='form_import_lec'>". $msg["import_lec_separateur"]."</label>
        <select name='Sep_Champs' >
            <option value=';'>;</option>
            <option value='.'>.</option>
        </select>
	</div>
	<div class='row'>
		<p>L'ordre des colonnes dans votre fichier doit &ecirc;tre :<br />
		Code-barres ; Nom ; Pr&eacute;nom ; Rue ; Compl&eacute;ment de rue ; Code postal ; Commune ; T&eacute;l&eacute;phone ; Ann&eacute;e de date de naissance ; Classe ; Sexe ; T&eacute;l&eacute;phone 2 ; Mail ; Profession ; Message<br />
		Les trois derni&egrave;res colonnes mail, profession, message sont facultatives.
		</p>
	</div>
	<div class='row'>

    </div>
    <br />
	<div class='row'>
        ".import_empr::get_update_type_radio()."
    </div>
    <div class='row'>";

// Si les lecteurs sont localis�s, affiche le s�lecteur de localisation, sinon valeur par d�faut de l'utilisateur
print import_empr::get_locations_selector('localisation');
print "<br />";

// S�lecteur de categ lecteur
print import_empr::get_categories_selector('categorie');
print "<br />";

// S�lecteur de code stat
print import_empr::get_codestat_selector('codestat');
print "<br />";

print "<label class='etiquette' for='encodage_fic_source' id='text_desc_encodage_fic_source' name='text_desc_encodage_fic_source'>".htmlentities($msg["admin_import_encodage_fic_source"],ENT_QUOTES,$charset)."</label>";
print import_empr::get_encoding_selector();

print "
	</div>
	<div class='row'>
		<input name='import_launch' value='Import des lecteurs' type='submit' class='bouton'/>
	</div>
</form>";
}

function import($separateur, $type_import){

    //La structure du fichier texte doit �tre la suivante :
    //Code-barres ; Nom ; Pr�nom ; Rue ; Compl�ment de rue ; Code postal ; Commune ; T�l�phone ; Ann�e de date de naissance ; Classe ; Sexe ; T�l�phone 2 ; Mail ; Profession ; Message

    global $categorie, $codestat, $localisation;
	global $pmb_lecteurs_localises, $lang;

	$cpt_maj = 0;
	$cpt_insert = 0;
	$cpt_delete = 0;

	$requete = "SELECT duree_adhesion FROM empr_categ WHERE id_categ_empr='".$categorie."'";
	$resultat = pmb_mysql_query($requete);
	if(pmb_mysql_num_rows($resultat)) {
		 $duree = pmb_mysql_result($resultat,0,0);
	} else {
	    $duree=365;
	}
    $eleve_abrege = array("Num&eacute;ro identifiant","Nom","Pr�nom");
    $date_auj = date("Y-m-d", time());
    $date_an_proch = date("Y-m-d", time()+3600*24*$duree);

    //Upload du fichier
    if (!($_FILES['import_lec']['tmp_name'])) {
        print "Cliquez sur Pr&eacute;c&eacute;dent et choisissez un fichier";
    } elseif (!(move_uploaded_file($_FILES['import_lec']['tmp_name'], "./temp/".basename($_FILES['import_lec']['tmp_name'])))) {
        print "Le fichier n'a pas pu &ecirc;tre t&eacute;l&eacute;charg&eacute;. Voici plus d'informations :<br />";
        print_r($_FILES)."<p>";
    }
    $fichier = @fopen( "./temp/".basename($_FILES['import_lec']['tmp_name']), "r" );

    if ($fichier) {
        while (!feof($fichier)) {
			//initialise la variable tableau, au cas o� on ait pas toutes les colonnes dans le fichier csv
			$buffer = fgets($fichier, 4096);
            if($buffer === false) {
                break;
            }

			$buffer = import_empr::get_encoded_buffer($buffer);
            $buffer = pmb_mysql_escape_string($buffer);
            $tab = explode($separateur, $buffer);

            //Gestion du sexe
            if(!isset($tab[10][0])) {
                if (!isset($tab[10])) {
                    $tab[10] = [];
                }
                $tab[10][0] = '';
            }
            switch ($tab[10][0]) {
                case 'M':
                    $sexe = 1;
                    break;
                case 'F':
                    $sexe = 2;
                    break;
                default:
                    $sexe = 0;
                    break;
            }
			if (isset($tab[8]) && $tab[8]!="0") $password=$tab[8]; else $password="";
			//pour �viter un saut de ligne dans les trois derni�res colonnes qui sont facultatives
			if(isset($tab[12])) $tab[12]=str_replace("\\r\\n","", $tab[12]);
			if(isset($tab[13])) $tab[13]=str_replace("\\r\\n","", $tab[13]);
			if(isset($tab[14])) $tab[14]=str_replace("\\r\\n","", $tab[14]);

            // Traitement du lecteur
			$id_empr = 0;
			$select = pmb_mysql_query("SELECT id_empr FROM empr WHERE empr_cb = '".$tab[0]."'");
            $nb_enreg = pmb_mysql_num_rows($select);

            //Test si un num�ro id est fourni, rejet si pas d'id avec message si au moins nom ou au moins pr�nom contient qqch
            //si pas d'id, pas de nom, pas de pr�nom, erreur muette : derni�re ligne
            if (empty($tab[0]) && !($tab[1]=="" && $tab[2]=="" && $tab[3]==""&& $tab[4]=="")) {
            	print("<b> Lecteur non pris en compte car \"Num&eacute;ro identifiant\" non renseign&eacute; : </b><br />");
                for ($i=0;$i<3;$i++) {
                    print($eleve_abrege[$i]." : ".$tab[$i].", ");
                }
                print("<br />");
                $nb_enreg = 2;
            }
            if($nb_enreg == 1) {
                $row = pmb_mysql_fetch_assoc($select);
                $id_empr = $row['id_empr'];
            }

            $login = import_empr::cre_login($tab[1],$tab[2]);

            switch ($nb_enreg) {

                case 0:
                	//Ce lecteur n'est pas enregistr�
                    $req_insert = "INSERT INTO empr(empr_cb, empr_nom, empr_prenom, empr_adr1, empr_adr2, empr_cp, empr_ville, ";
                    $req_insert .= "empr_tel1, empr_year, empr_categ, empr_codestat, empr_creation, empr_sexe,  ";
                    $req_insert .= "empr_login, empr_password, empr_date_adhesion, empr_date_expiration, empr_tel2, empr_mail, empr_prof, empr_msg, empr_location) ";
                    $req_insert .= "VALUES ('$tab[0]','$tab[1]','$tab[2]','$tab[3]', '$tab[4]', '$tab[5]', ";
                    $req_insert .= "'$tab[6]', '$tab[7]', '$tab[8]', $categorie, $codestat, '$date_auj', '$sexe', ";
                    $req_insert .= "'$login', '$password', '$date_auj', '$date_an_proch','$tab[11]','$tab[12]','$tab[13]','$tab[14]','$localisation')";
                    $insert = pmb_mysql_query($req_insert);

                    if (!$insert) {

                        print("<b>Echec de la cr&eacute;ation du lecteur suivant (Erreur : ".pmb_mysql_error().") : </b><br />");
                        for ($i=0;$i<3;$i++) {
                            print($eleve_abrege[$i]." : ".$tab[$i].", ");
                        }
                        print("<br />");

                    } else {

                        $id_empr = pmb_mysql_insert_id();

                        //Chiffrement du mot de passe
                        //On verifie que le mot de passe lecteur correspond aux regles de saisie definies
                        //Si non, encodage dans l'ancien format
                        $old_hash = false;
                        $check_password_rules = emprunteur::check_password_rules((int) $id_empr, $password, [], $lang);
                        if( !$check_password_rules['result'] ) {
                            $old_hash = true;
                        }
                        emprunteur::update_digest($login, $password);
                        emprunteur::hash_password($login, $password, $old_hash);

                        $cpt_insert ++;
                    }
                    import_empr::gestion_groupe($tab[9], $tab[0]);
                    break;

                case 1:
                	//Ce lecteur est d�j� enregistr�
                    $req_update = "UPDATE empr SET empr_nom = '$tab[1]', empr_prenom = '$tab[2]', empr_adr1 = '$tab[3]', ";
                    $req_update .= "empr_adr2 = '$tab[4]', empr_cp = '$tab[5]', empr_ville = '$tab[6]', ";
                    $req_update .= "empr_tel1 = '$tab[7]', empr_year = '$tab[8]', empr_categ = '".$categorie."', empr_codestat = '$codestat', empr_modif = '$date_auj', empr_sexe = '$sexe', ";

                    // on ne modifie ni login ni mot de passe pour �viter d'�craser un mot de passe chang� par le lecteur
                    // $req_update .= "empr_login = $login, empr_password= $tab[8], ";
                    $req_update .= "empr_date_adhesion = '$date_auj', empr_date_expiration = '$date_an_proch', empr_tel2 = '$tab[11]', empr_location='$localisation' ";
                    $req_update .= "WHERE empr_cb = '$tab[0]'";
                    $update = pmb_mysql_query($req_update);

                    if (!$update) {

                        print("<b>Echec de la modification du lecteur suivant (Erreur : ".pmb_mysql_error().") : </b><br />");
                        for ($i=0;$i<3;$i++) {
                            print($eleve_abrege[$i]." : ".$tab[$i].", ");
                        }
                        print("<br />");

                    } else {

                    	if ($tab[12]!="") {
	                    	$req_update_mail = "UPDATE empr SET empr_mail='$tab[12]' WHERE empr_cb = '$tab[0]'";
	                    	$update_mail = pmb_mysql_query($req_update_mail);
	                    	if (!$update_mail) {
	                    		print("<b>Echec de la modification du mail du lecteur suivant (Erreur : ".pmb_mysql_error().") : </b><br />");
	                        	for ($i=0;$i<3;$i++) print($eleve_abrege[$i]." : ".$tab[$i].", ");
	                        	print("<br />");

							}
							$tab[12]="";
                    	}
                    	if (isset($tab[13]) && $tab[13]!="") {
	                    	$req_update_prof = "UPDATE empr SET empr_prof='$tab[13]' WHERE empr_cb = '$tab[0]'";
	                    	$update_prof = pmb_mysql_query($req_update_prof);
	                    	if (!$update_prof) {
	                    		print("<b>Echec de la modification de la profession lecteur suivant (Erreur : ".pmb_mysql_error().") : </b><br />");
	                        	for ($i=0;$i<3;$i++) print($eleve_abrege[$i]." : ".$tab[$i].", ");
	                        	print("<br />");
							}
							$tab[13]="";
                    	}
                    	if (isset($tab[14]) && $tab[14]!="") {
	                    	$req_update_msg = "UPDATE empr SET empr_msg='$tab[14]' WHERE empr_cb = '$tab[0]'";
	                    	$update_msg = pmb_mysql_query($req_update_msg);
	                    	if (!$update_msg) {
	                    		print("<b>Echec de la modification du message sur le lecteur suivant (Erreur : ".pmb_mysql_error().") : </b><br />");
	                        	for ($i=0;$i<3;$i++) print($eleve_abrege[$i]." : ".$tab[$i].", ");
	                        	print("<br />");
							}
							$tab[14]="";
                    	}

                        $cpt_maj ++;
                    }
                    import_empr::gestion_groupe($tab[9], $tab[0]);
                    break;
                case 2:
                    break;
                default:
                    print("<b>Echec pour le lecteur suivant (Erreur : ".pmb_mysql_error().") : </b><br />");
                    for ($i=0;$i<3;$i++) {
                        print($eleve_abrege[$i]." : ".$tab[$i].", ");
                    }
                    print("<br />");
                    break;
            }
        }


        if ($type_import == 'maj_complete') {
        	$requete_empr_groupe_delete = "DELETE FROM empr_groupe LEFT JOIN empr ON empr_id=id_empr LEFT JOIN pret ON pret_idempr=id_empr WHERE pret_idempr IS NULL and empr_modif != '$date_auj' and empr_categ=$categorie and empr_codestat= $codestat";
            $requete_empr_where = "";
			if ($pmb_lecteurs_localises=="1") {
				$requete_empr_where .= " and empr_location=$localisation";
			}
			pmb_mysql_query($requete_empr_groupe_delete.$requete_empr_where);

        	$requete_list_empr_delete = "SELECT id_empr FROM empr LEFT JOIN pret ON pret_idempr=id_empr
        		WHERE pret_idempr IS NULL and empr_modif != '$date_auj' and empr_categ=$categorie and empr_codestat= $codestat $requete_empr_where ";
        	$list_empr_delete=pmb_mysql_query($requete_list_empr_delete);
        	while (($empr_delete = pmb_mysql_fetch_array($list_empr_delete))) {
            	$deleted  = emprunteur::del_empr($empr_delete["id_empr"]);
            	if(true === $deleted) {
            	    $cpt_delete++;
            	}
            }
        }

        //Affichage des insert et update
        print("<br />");
        if ($cpt_delete) print($cpt_delete." lecteurs supprim&eacute;s. <br />");
        if ($cpt_insert) print($cpt_insert." lecteurs cr&eacute;&eacute;s. <br />");
        if ($cpt_maj) print($cpt_maj." lecteurs modifi&eacute;s. <br />");
        fclose($fichier);
    }
}

switch($action) {
    case 1:
        if ($import_launch){
            import($Sep_Champs, $type_import);
        }
        else {
            show_import_choix_fichier();
        }
        break;
    case 2:
        break;
    default:
        show_import_choix_fichier();
        break;
}
