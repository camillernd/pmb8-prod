<?php
// +-------------------------------------------------+
// � 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: import_lecteurs_localises_ouche.inc.php,v 1.15.6.1 2024/12/20 15:54:27 dbellamy Exp $

if (stristr($_SERVER['REQUEST_URI'], ".inc.php")) die("no access");

//ce script issu d'import belgique a �t� modifi� par Ouche CCVO par Patrice Desfray le 2/4/2007 (p.desfray@orange.fr)
//son fonctionnement est expliqu� sur le Wiki PMB dans Import "Belgique" : import des �l�ve ....
//notre contexte d'utilisation: 7 biblioth�ques municipales en r�seau avec gestion publique et gestion accueil scolaire diff�renci�e
//modification apport�es / import Belgique
//suppression import profs
//liste choix pour la localisation de l'�cole cod� en dur mais peut-�tre remplac� par une requ�te sur docs_location
//un fichier csv sur le mod�le import belgique 11 colonnes avec code barre
//codage du code: ann�e �cole incr�ment ex: 2006EV00001 : rentr�e 2006 �cole de velars incr�ment sur 5 digits
//2 var globales cr�es: $code_categorie et $code_statistique pour le codage en dur des cat�gories
//$code_categorie=12 : collectivit� - �l�ves (si il y a des profs dans la liste, il faudra les passer apr�s l'import en 8 : collectivit� - �cole droits de pr�t diff�rents
//$code_statistique=3 commune de la biblioth�que
//en option 2 : efface les lecteurs sans pr�ts, sur la localisation choisie qui sont dans la cat�gorie : collectivit� - �l�ves (12)  les profs en (8) ne seront donc pas effac�s
// merci � A.M. Cubat et � aux concepteur de import_Bretagne
//Patrice Desfray

//La structure du fichier texte doit �tre la suivante : 11 champs (pour tout le monde)
//Num�ro identifiant/Nom/Pr�nom/Rue/Compl�ment de rue/Code postal/Commune/T�l�phone/Date de naissance/Classe/Sexe

//Supprimez la premi�re ligne du fichier excel si elle contient la liste des champs

global $class_path;
global $location, $user;
global $action, $type_import, $cnl_bibli;

require_once $class_path."/emprunteur.class.php";
require_once $class_path."/import/import_empr.class.php";

echo $location;
echo $user;

function show_import_choix_fichier() {
	global $msg;
	global $current_module ;

print "
<form class='form-$current_module' name='form1' ENCTYPE=\"multipart/form-data\" method='post' action=\"./admin.php?categ=empr&sub=implec&action=1\">
<h3>Choix du fichier</h3>
<div class='form-contenu'>
	<div class='row'>
		<label class='etiquette' for='form_import_lec'>".$msg["import_lec_fichier"]."</label>
        <input name='import_lec' accept='text/plain' type='file' class='saisie-80em' size='50'>
		   Fichier csv sur 11 colonnes

	</div>
	<br />
	<div class='row'>
        <label class='etiquette' for='form_import_lec'>". $msg["empr_location"]."</label>
        <select name='cnl_bibli'>";

/* ajout GM : liste des localisations */
$requete_localisation="SELECT idlocation, location_libelle FROM docs_location ORDER BY location_libelle";
$select_requete_localisation = pmb_mysql_query($requete_localisation);
while (($liste_localisation = pmb_mysql_fetch_array($select_requete_localisation))) {
	print "<option value='".$liste_localisation["idlocation"]."' >".$liste_localisation["location_libelle"]."</option>";
}

print "		</select>
	<br />
	 </div>
	   <!--<form>-->
	<br />
	<!--</form>-->
		<div class='row'>
	        <input type=radio name='type_import' value='nouveau_lect' checked>
	        <label class='etiquette' for='form_import_lec'>Nouveaux lecteurs</label>
	        (ajoute ou modifie les lecteurs pr�sents dans le fichier)
	        <br />
	        <input type=radio name='type_import' value='maj_complete'>
	        <label class='etiquette' for='form_import_lec'>Mise � jour compl�te</label>
	        (supprime les lecteurs non pr�sents de cette localisation qui n'ont pas de pr�t en cours)
	    </div>
	    <div class='row'></div>

		</div>
	<div class='row'>
		<input name='imp_elv' type='submit' class='bouton' value='Import des �l�ves'/>
	</div>
	</form>";
}

function import_eleves($separateur, $type_import,$commune){

	global $code_categorie;
	global $code_statistique;
	global $location, $user, $lang;

	$code_categorie = 12;
	$code_statistique = 3;

	$cpt_insert = 0;
	$cpt_maj = 0;

    $eleve_abrege = array("Num�ro identifiant","Nom","Pr�nom");
    $date_auj = date("Y-m-d", time());
    $date_an_proch = date("Y-m-d", time()+3600*24*30.42*12);

    //Upload du fichier
    if (!($_FILES['import_lec']['tmp_name'])) {
        print "Cliquez sur Pr&eacute;c&eacute;dent et choisissez un fichier";
    }elseif (!(move_uploaded_file($_FILES['import_lec']['tmp_name'], "./temp/" .basename($_FILES['import_lec']['tmp_name'])))) {
        print "Le fichier n'a pas pu �tre t�l�charg�. Voici plus d'informations :<br />";
        print_r($_FILES)."<p>";
    }
    $fichier = @fopen( "./temp/".basename($_FILES['import_lec']['tmp_name']), "r" );

    if ($fichier) {

        if ($type_import == 'maj_complete') {
            //Vide la table empr_groupe des �l�ves qui n'ont pas de pr�ts en cours et qui sont localis�s � la commune s�lectionn�e et de categorie collectivit� eleves
			$req_select_verif_pret = "SELECT id_empr FROM empr left join pret on id_empr=pret_idempr WHERE pret_idempr is null and empr_location= '$commune' and empr_categ = '$code_categorie' ";
            $select_verif_pret = pmb_mysql_query($req_select_verif_pret);
            while (($verif_pret = pmb_mysql_fetch_array($select_verif_pret))) {
            	//pour tous les emprunteurs qui n'ont pas de pret en cours
                $req_delete = "DELETE FROM empr_groupe WHERE empr_id = '".$verif_pret["id_empr"]."'";
                pmb_mysql_query($req_delete);
            }
            //Supprime les �l�ves qui n'ont pas de pr�ts en cours et qui sont localis�s � la commune s�lectionn�e et de categorie collectivit� eleves
            $req_select_verif_pret = "SELECT id_empr FROM empr left join pret on id_empr=pret_idempr WHERE pret_idempr is null and empr_location= '$commune' and empr_categ = '$code_categorie' ";
            $select_verif_pret = pmb_mysql_query($req_select_verif_pret);
            while (($verif_pret = pmb_mysql_fetch_array($select_verif_pret))) {
            	//pour tous les emprunteurs qui n'ont pas de pret en cours
                emprunteur::del_empr($verif_pret["id_empr"]);
            }
        }

        while (!feof($fichier)) {
            $buffer = fgets($fichier, 4096);
            $buffer = import_empr::get_encoded_buffer($buffer);
            $buffer = pmb_mysql_escape_string($buffer);
            $tab = explode($separateur, $buffer);

            //Gestion du sexe
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

            // Traitement de l'�l�ve
            $id_empr = 0;
            $select = pmb_mysql_query("SELECT id_empr FROM empr WHERE empr_cb = '".$tab[0]."'");
            $nb_enreg = pmb_mysql_num_rows($select);

            //Test si un num�ro id est fourni
            if (!$tab[0] || $tab[0] == "") {
                print("<b> El�ve non pris en compte car \"Num�ro identifiant\" non renseign� : </b><br />");
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
                	//Cet �l�ve n'est pas enregistr�
                    $req_insert = "INSERT INTO empr(empr_cb, empr_nom, empr_prenom, empr_adr1, empr_adr2, empr_cp, empr_ville, ";
                    $req_insert .= "empr_tel1, empr_year, empr_categ, empr_codestat, empr_creation, empr_sexe,  ";
                    $req_insert .= "empr_login, empr_password, empr_date_adhesion, empr_date_expiration, empr_location) ";
                    $req_insert .= "VALUES ('$tab[0]','$tab[1]','$tab[2]','$tab[3]', '$tab[4]', '$tab[5]', ";
	                //V�rifier dans la table empr_categ si id_categ_empr 1 = �l�ves
		            //V�rifier dans la table empr_codestat si idcode 2 = �cole    Sinon, changer les valeurs
                    $req_insert .= "'$tab[6]', '$tab[7]', '$tab[8]', $code_categorie , '3', '$date_auj', '$sexe', ";
                    $req_insert .= "'$login', '$tab[8]', '$date_auj', '$date_an_proch' , '$commune' )";
                    $insert = pmb_mysql_query($req_insert);

                    if (!$insert) {

                        print("<b>Echec de la cr�ation de l'�l�ve suivant (Erreur : ".pmb_mysql_error().") : </b><br />");
						print($code_categorie);
						print("3");
						print( "$location");
						print( "$user");
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
                        $check_password_rules = emprunteur::check_password_rules((int) $id_empr, $tab[8], [], $lang);
                        if( !$check_password_rules['result'] ) {
                            $old_hash = true;
                        }
                        emprunteur::update_digest($login,$tab[8]);
                        emprunteur::hash_password($login,$tab[8], $old_hash);

                        $cpt_insert ++;
                    }
                    import_empr::gestion_groupe($tab[9], $tab[0]);
                    break;

                case 1:
                	//Cet �l�ve est d�j� enregistr�
                    $req_update = "UPDATE empr SET empr_nom = '$tab[1]', empr_prenom = '$tab[2]', empr_adr1 = '$tab[3]', ";
                    $req_update .= "empr_adr2 = '$tab[4]', empr_cp = '$tab[5]', empr_ville = '$tab[6]', ";
	               //V�rifier dans la table empr_categ si id_categ_empr 1 = �l�ves    V�rifier dans la table empr_codestat si idcode 2 = �cole    Sinon, changer les valeurs
                    $req_update .= "empr_tel1 = '$tab[7]', empr_year = '$tab[8]', empr_categ = '$code_categorie ', empr_codestat = '3', empr_modif = '$date_auj', empr_sexe = '$sexe', ";
                    $req_update .= "empr_login = '$login', empr_password= '$tab[8]', ";
                    $req_update .= "empr_date_adhesion = '$date_auj', empr_date_expiration = '$date_an_proch', empr_location = '$commune'";
                    $req_update .= "WHERE empr_cb = '$tab[0]'";
                    $update = pmb_mysql_query($req_update);

                    if (!$update) {

                        print("<b>Echec de la modification de l'�l�ve suivant (Erreur : ".pmb_mysql_error().") : </b><br />");
						print('$code_categorie');
						print("3");
						print( "$location");
						print( "$user");
                        for ($i=0;$i<3;$i++) {
                            print($eleve_abrege[$i]." : ".$tab[$i].", ");
                        }
                        print("<br />");

                    } else {

                        //Chiffrement du mot de passe
                        //On verifie que le mot de passe lecteur correspond aux regles de saisie definies
                        //Si non, encodage dans l'ancien format
                        $old_hash = false;
                        $check_password_rules = emprunteur::check_password_rules((int) $id_empr, $tab[8], [], $lang);
                        if( !$check_password_rules['result'] ) {
                            $old_hash = true;
                        }
                        emprunteur::update_digest($login,$tab[8]);
                        emprunteur::hash_password($login,$tab[8], $old_hash);

                        $cpt_maj ++;
                    }
                    import_empr::gestion_groupe($tab[9], $tab[0]);
                    break;

                case 2:
                    break;

                default:
                    print($code_categorie);
                    print(3);
                    print( $location);
                    echo $user;
                    print("<b>Echec pour l'�l�ve suivant (Erreur : ".pmb_mysql_error().") : </b><br />");
                    for ($i=0;$i<3;$i++) {
                        print($eleve_abrege[$i]." : ".$tab[$i].", ");
                    }
                    print("<br />");
                    break;
            }
        }

        //Affichage des insert et update
        print("<br />_____________________<br />");
        if ($cpt_insert) print($cpt_insert." El�ves cr��s. <br />");
        if ($cpt_maj) print($cpt_maj." El�ves modifi�s. <br />");
        fclose($fichier);
    }

}

switch($action) {
    case 1:
        import_eleves(";", $type_import, $cnl_bibli);
        break;
    case 2:
        break;
    default:
        show_import_choix_fichier();
        break;
}
