<?php
// +-------------------------------------------------+
// � 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: abts_func.inc.php,v 1.14.4.1 2025/02/04 15:02:40 jparis Exp $

if (stristr($_SERVER['REQUEST_URI'], ".inc.php")) die("no access");

/* ce s�lecteur est bas� sur le calendrier dont la description et 
   l'auteur initial sont mentionn�s ci-dessous.
   Il a �t� modifi� afin d'�tre utilisable dans notre application */


/***************************************************************************
             ____  _   _ ____  _              _     _  _   _   _
            |  _ \| | | |  _ \| |_ ___   ___ | |___| || | | | | |
            | |_) | |_| | |_) | __/ _ \ / _ \| / __| || |_| | | |
            |  __/|  _  |  __/| || (_) | (_) | \__ \__   _| |_| |
            |_|   |_| |_|_|    \__\___/ \___/|_|___/  |_|  \___/
            
                       calendrier.php  -  A calendar
                             -------------------
    begin                : June 2002
    copyright            : (C) 2002 PHPtools4U.com - Mathieu LESNIAK
    email                : support@phptools4u.com

***************************************************************************/

/***************************************************************************
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/

/* 
- $params['calendar_id'] :  Par d�faut � 1, incr�menter cette valeur pour utiliser plusieurs calendriers sur la m�me page.  
- $params['calendar_columns'] :  Par d�faut � 7, modifier ce nombre pour diminuer / augmenter le nombres de colonnes. 
- $params['show_day'] :  Par d�faut � 1, permet d'afficher les jours (L M M J V S D) 
- $params['show_month'] :  Par d�faut � 1, permet d'afficher le nom du mois et l'ann�e en haut 
- $params['nav_link'] :  Par d�faut � 1, affiche les liens pour les jours et mois pr�c�dents / suivants 
- $params['link_after_date'] :  Par d�faut � 0, si activ�, affiche les liens de la navigation (cf ci-dessus) pour les dates sup�rieures au jour en cours 
- $params['link_on_day'] :  Lien � attribuer sur les jours du calendrier. A chaque lien est rajout� la date en argument. Pr�voir de mettre '?argument=' en fin de lien 
- $params['font_face'] :  Police a utiliser (par d�faut : 'Verdana, Arial, Helvetica') 
- $params['font_size'] :  Taille de la police moyenne en pixels (10 par d�faut) 
- $params['bg_color'] :  Couleur du fond des cases des jours (blanc - #FFFFFF par d�faut) 
- $params['today_bg_color'] :  Couleur de fond de la case du jour en cours 
- $params['font_today_color'] :  Couleur de la police pour le jour en cours 
- $params['font_color'] :  Couleur de la police 
- $params['font_nav_bg_color'] :  Couleur de fond pour la barre des jours (L M M J V S D) 
- $params['font_nav_color'] :  Couleur de la police pour la barre des jours (L M M J V S D) 
- $params['font_header_color'] :  Couleur de la police pour le nom du mois 
- $params['border_color'] :  Couleur pour les s�paration des cases et des bordures 
- $params['use_img'] :  Utilise des fichiers gif � c�t� du nom du mois et pour la barre de navigation en bas. Si d�fini � '0', affiche les liens textes. 

*/

$base_url = "./admin.php?categ=calendrier&sub=$sub";
$base_url_mois = "./admin.php?categ=calendrier&sub=edition";

$params=array();
$params['calendar_id'] = 1 ; 				
$params['calendar_columns'] = 7 ; 			
$params['show_day'] = 1 ; 				
$params['show_month'] = 1 ; 				
$params['nav_link'] = 0 ; 				
$params['link_on_day'] = "" ; 		
$params['link_after_date'] = 1 ; 			
$params['link_before_date'] = 0 ; 
$params['font_face'] = "Verdana, Arial, Helvetica" ; 	
$params['font_size'] = 10 ; 				
$params['bg_color'] = "#FFFFFF" ; 			
$params['today_bg_color'] = "#FF0000" ; 		
$params['font_today_color'] = "#000000" ; 		
$params['font_color'] = "#000000" ; 			
$params['font_nav_bg_color'] = "#AAAAAA" ; 		
$params['font_nav_color'] = "#000000" ; 		
$params['font_header_color'] = "#00FF00" ; 		
$params['border_color'] = "#000000" ; 			
$params['use_img'] = 1 ; 


function calendar_gestion($date = '', $navbar=0, $url_maj_base='', $base_url_mois="", $form_input_par_jour=0,$modele_id=0,$num_abt=0) {
	global $link_on_day, $params, $base_url, $caller, $msg, $charset, $date_caller;
	global $pmb_first_week_day_format ;
	global $style_calendrier ;
	global $admin_calendrier_form_mois_start, $admin_calendrier_form_mois_end, $admin_calendrier_form_mois_commentaire ;
	global $deflt2docs_location;
	
	$param = array();
	
	$output = '';
	// Default Params
	$param_d=array();
	$param_d['calendar_id']		= 1; // Calendar ID
	$param_d['calendar_columns']= 5; // Nb of columns
	$param_d['show_day'] 		= 1; // Show the day bar
	$param_d['show_month']		= 1; // Show the month bar
	$param_d['nav_link']		= 1; // Add a nav bar below
	$param_d['link_after_date']	= 0; // Enable link on days after the current day
	$param_d['link_before_date']= 0; // Enable link on days before the current day
	//$param_d['link_on_day']		= $PHP_SELF.'?date='; // Link to put on each day
	$param_d['font_face']		= 'Verdana, Arial, Helvetica'; // Default font to use
	$param_d['font_size']		= 10; // Font size in px
	$param_d['bg_color']		= '#FFFFFF'; 
	$param_d['today_bg_color']	= '#A0C0C0';
	$param_d['font_today_color']= '#990000';
	$param_d['font_color']		= '#000000';
	$param_d['font_nav_bg_color']= '#A9B4B3';
	
	$param_d['font_nav_color']	= '#FFFFFF';
	$param_d['font_header_color']	= '#FFFFFF';
	$param_d['border_color']	= '#3f6551';
	$param_d['use_img']		= 1; // Use gif for nav bar on the bottom
	
	$monthes_name = array('',$msg[1006],$msg[1007],$msg[1008],$msg[1009],$msg[1010],$msg[1011],$msg[1012],$msg[1013],$msg[1014],$msg[1015],$msg[1016],$msg[1017]);
	$days_name = array('',$msg[1018],$msg[1019],$msg[1020],$msg[1021],$msg[1022],$msg[1023],$msg[1024]);
	
	foreach ($param_d as $key => $val) {
		if (isset($params[$key])) $param[$key] = $params[$key];
		else $param[$key] = $param_d[$key];
	}
	$param['calendar_columns'] = ($param['show_day']) ? 7 : $param['calendar_columns'];
	
	//priv_reg_glob_calendar('date');
	if ($date == '') {
		$date_MySQL = " CURDATE() ";
	} else {
		$month 		= substr($date, 4 ,2);
		$day 		= substr($date, 6, 2);
		$year		= substr($date, 0 ,4);
		$date_MySQL = "'$year-$month-$day'";
	}
	$rqt_date = "select date_format(".$date_MySQL.", '%d') as current_day, date_format(".$date_MySQL.", '%m') as current_month_2, date_format(".$date_MySQL.", '%c') as current_month, date_format(".$date_MySQL.", '%Y') as current_year " ;
	$resultatdate=pmb_mysql_query($rqt_date);
	$resdate=pmb_mysql_fetch_object($resultatdate);
	$current_day 		= $resdate->current_day;
	$current_month 		= $resdate->current_month;
	$current_month_2	= $resdate->current_month_2;
	$current_year 		= $resdate->current_year;
	$date_MySQL_firstday = "'$year-$current_month_2-01'";
	$rqt_date = "select date_format(".$date_MySQL_firstday.", '%w') as first_day_pos,
				date_format(DATE_SUB(DATE_ADD(".$date_MySQL_firstday.", INTERVAL 1 MONTH),INTERVAL 1 DAY), '%d') as nb_days_month " ;
	$resultatdate=pmb_mysql_query($rqt_date);
	$resdate=pmb_mysql_fetch_object($resultatdate);
	$first_day_pos 		= $resdate->first_day_pos;
	$first_day_pos 		= ($first_day_pos == 0) ? 7 : $first_day_pos;
	$nb_days_month 		= $resdate->nb_days_month ;
	$current_month_name = $monthes_name[$current_month];
	
	/* Ajout ER : d�tection si date en cours du calendrier correspond ou pas � la date de l'appelant 
		Sans ce test, le lien sur tous les jours identiques d'un autre mois n'�taient pas affich�s, exemple :
			appelant avec date au 04/10/2003 >> lien du 04/11/2003 absent */
	$date_caller = $date_caller ?? '';
	$date_MySQL_caller = "'".substr($date_caller, 0 ,4)."-".substr($date_caller, 4 ,2)."-".substr($date_caller, 6 ,2)."'";
	$rqt_date = "select date_format(".$date_MySQL_caller.", '%d') as current_day, date_format(".$date_MySQL_caller.", '%c') as current_month, date_format(".$date_MySQL_caller.", '%Y') as current_year ";
	$resultatdate=pmb_mysql_query($rqt_date);
	$resdate=pmb_mysql_fetch_object($resultatdate);
	$caller_day 		= $resdate->current_day;
	$caller_month 		= $resdate->current_month;
	$caller_year 		= $resdate->current_year;
	if (($caller_month==$current_month) && ($caller_year==$current_year) && ($caller_day==$current_day)) $same_date=1; else $same_date=0;
	if (!$style_calendrier) {
		$style_calendrier = '<style type="text/css">
				<!--
				.calendarNav'.$param['calendar_id'].' 	{  font-family: '.$param['font_face'].'; font-size: '.($param['font_size']-1).'px; font-style: normal; background-color: '.$param['border_color'].'}
				.calendarTop'.$param['calendar_id'].' 	{  font-family: '.$param['font_face'].'; font-size: '.($param['font_size']+1).'px; font-style: normal; color: '.$param['font_header_color'].'; font-weight: bold;  background-color: '.$param['border_color'].'}
				.calendarToday'.$param['calendar_id'].' {  font-family: '.$param['font_face'].'; font-size: '.$param['font_size'].'px; font-weight: bold; color: '.$param['font_today_color'].'; background-color: '.$param_d['today_bg_color'].';}
				.calendarDays'.$param['calendar_id'].' 	{  font-family: '.$param['font_face'].'; font-size: '.$param['font_size'].'px; font-style: normal; color: '.$param['font_color'].'; background-color: '.$param['bg_color'].'; text-align: center}
				.calendarHeader'.$param['calendar_id'].'{  font-family: '.$param['font_face'].'; font-size: '.($param['font_size']-1).'px; background-color: '.$param['font_nav_bg_color'].'; color: '.$param['font_nav_color'].';}
				.calendarTable'.$param['calendar_id'].' {  background-color: '.$param['border_color'].'; border: 1px '.$param['border_color'].' solid}
				-->
				</style>';
		$output = $style_calendrier ;
	}
	if ($form_input_par_jour) $output .= $admin_calendrier_form_mois_start ;  	
	$output .= '<TABLE style="border:0px; padding: 1px; border-spacing: 0px" class="calendar-container">'."\n";
	// Displaying the current month/year
	if ($param['show_month'] == 1) {
		$output .= '<TR>'."\n";
		$output .= '	<TD colspan="'.$param['calendar_columns'].'" class="align_right">'."\n";
		$output .= "<a name='".$current_year."-".$current_month_2."' ></a>";
		if ($base_url_mois) $output .= "<a href='".$base_url_mois."&date=".$current_year.$current_month_2."01' alt='".$msg["calendrier_edition"]."' title='".$msg["calendrier_edition"]."'>";
		if ($param['use_img'] ) $output .= "<IMG src='".get_url_icon('mois.gif')."'>";			
		$output .= $current_month_name.' '.$current_year;
		if ($base_url_mois) $output .= "</a>";
		$output .= "</TD>";
		$output .= "</TR>"."\n";
	}
	if($pmb_first_week_day_format){
		$first_day_pos++;
		if($first_day_pos==8) $first_day_pos=1;
	}
	// Building the table row with the days
	if ($param['show_day'] == 1) {
		$output .= '<TR class="center">'."\n";
		if($pmb_first_week_day_format) $output .= '<TD class="calendarHeader'.$param['calendar_id'].'"><B>'.$msg[1024].'</B></TD>'."\n";
		$output .= '<TD class="calendarHeader'.$param['calendar_id'].'"><B>'.$msg[1018].'</B></TD>'."\n";
		$output .= '<TD class="calendarHeader'.$param['calendar_id'].'"><B>'.$msg[1019].'</B></TD>'."\n";
		$output .= '<TD class="calendarHeader'.$param['calendar_id'].'"><B>'.$msg[1020].'</B></TD>'."\n";
		$output .= '<TD class="calendarHeader'.$param['calendar_id'].'"><B>'.$msg[1021].'</B></TD>'."\n";
		$output .= '<TD class="calendarHeader'.$param['calendar_id'].'"><B>'.$msg[1022].'</B></TD>'."\n";
		$output .= '<TD class="calendarHeader'.$param['calendar_id'].'"><B>'.$msg[1023].'</B></TD>'."\n";
		if(!$pmb_first_week_day_format) $output .= '<TD class="calendarHeader'.$param['calendar_id'].'"><B>'.$msg[1024].'</B></TD>'."\n";
		$output .= '</TR>'."\n";	
	} else {
		$first_day_pos = 1;	
	}

	$output .= '<TR class="center">';
	$int_counter = 0;
	for ($i = 1; $i < $first_day_pos; $i++) {
		$output .= '<TD>&nbsp;</TD>'."\n";
		$int_counter++;
	}
	// Building the table
	for ($i = 1; $i <= $nb_days_month; $i++) {
		$i_2 = ($i < 10) ? '0'.$i : $i;
		//$commentaire=htmlentities($reception[$current_year.'-'.$current_month_2.'-'.$i_2]['commentaire'],ENT_QUOTES, $charset) ;
		$obj="$current_year-$current_month_2-$i_2";	
	
		### Row start
		if ((($i + $first_day_pos-1) % $param['calendar_columns']) == 1 && $i != 1) {
			$output .= "<TR class='center'>";
			$int_counter = 0;
		}
		if ($form_input_par_jour) {
			$input_commentaire = "&nbsp;".str_replace("!!name!!", "comment_".$i_2, $admin_calendrier_form_mois_commentaire) ;
			$input_commentaire = "&nbsp;".str_replace("!!commentaire!!", '', $input_commentaire) ;
		} else $input_commentaire = "" ; 
		$serie=0;
		if($num_abt){// Pour la grille abonnement
			$type_serie=$type_horsserie=0;
			$requete = "select type from abts_grille_abt where num_abt='$num_abt' and date_parution ='$obj'";
			$resultat=pmb_mysql_query($requete);		
			while($r=pmb_mysql_fetch_object($resultat)){
				$type=$r->type;				
				if ($type==1)$type_serie=1;
				if ($type==2)$type_horsserie=2;	
			}		
			$serie=$type_serie+$type_horsserie;		
		}
		else{// Pour la grille mod�le			
			$requete = "select type_serie from abts_grille_modele where num_modele='$modele_id' and date_parution ='$obj'";
			$resultat=pmb_mysql_query($requete);			
			while($r=pmb_mysql_fetch_object($resultat)){
				$type_serie=$r->type_serie;					
				$serie+=$type_serie;		
			}
		}
		// c'est un p�riodique
		if ($serie==1) {
			$class = " class='lien_date' ";
		} 
		// c'est un hors-s�rie
		elseif ($serie==2) {
			$class = " class='lien_date_hs'";
		}
		// c'est un hors-s�rie et un p�riodique 
		elseif ($serie==3) {
			$class = " class='lien_date_hs_p'";
		}
		// rien n'est attendu ce jour l�
		else {
			$class = " ";
		}		
		$td_link="onClick='ad_date(\"$obj\",event);return false;'";
		$output .="<TD $class id='$obj' $td_link>" .
				"<a href='#'>$i</a></TD>\n";   
		$int_counter++;
		// Row end
		if ( (($i+$first_day_pos-1) % $param['calendar_columns']) == 0 ) {
			$output .= '</TR>'."\n";	
		}
	}
	$cell_missing = $param['calendar_columns'] - $int_counter;
	
	for ($i = 0; $i < $cell_missing; $i++) {
		$output .= '<TD class="align_right">&nbsp;</TD>'."\n";
	}
	if($cell_missing)$output .= '</TR>'."\n";
	$output .= '</TABLE>'."\n";
	return $output;
}

