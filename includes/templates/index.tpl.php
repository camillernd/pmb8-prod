<?php
// +-------------------------------------------------+
// � 2002-2004 PMB Services / www.sigb.net pmb@sigb.net et contributeurs (voir www.sigb.net)
// +-------------------------------------------------+
// $Id: index.tpl.php,v 1.40.4.3 2025/05/20 14:00:08 qvarin Exp $

use Pmb\Security\Library\Auth;

if (stristr($_SERVER['REQUEST_URI'], ".tpl.php")) die("no access");

global $ret_url, $nav_bar, $login_form, $msg, $current_module, $_tableau_databases, $_libelle_databases, $login_form_demo, $login_form_error, $index_layout, $index_footer, $pmb_version;
global $homepage, $pmb_bdd_version, $pmb_login_message, $index_header, $extra_version, $login_menu;

require_once $class_path."/html_helper.class.php";

$queryMFA = "select valeur_param from parametres where type_param='security' and sstype_param = 'mfa_active' ";
$resultMFA = pmb_mysql_query($queryMFA);
$security_mfa_active = false;
if (pmb_mysql_num_rows($resultMFA)) {
    $security_mfa_active = pmb_mysql_result($resultMFA, 0);
}

$disabled_fields = '';
$auth_instance = Auth::getInstance();
if ($auth_instance->isInBlackList()) {
    $disabled_fields = "disabled='disabled'";
}

if(!isset($ret_url)) $ret_url = '';
if(!isset($nav_bar)) $nav_bar = '';
// templates index
//    ----------------------------------
// $login_form : template form login

$login_form = "<script type='text/javascript' src='./javascript/http_request.js'></script>";
if ($security_mfa_active) {
  $login_form = "<script type='text/javascript' src='./javascript/login.js'></script>";
}

$login_form .= "
<div id='login-box'>
    <h1>$msg[1000]</h1>
    <div class='login_page'>
      <div class='login_form'>";


if ($security_mfa_active) {
    $login_form .= "<form class='form-$current_module' id='login' method='post' action='./main.php' onsubmit='send_ajax_login(event); return false;'>";
} else {
    $login_form .= "<form class='form-$current_module' id='login' method='post' action='./main.php'>";
}

$login_form .= "<input type=hidden name=ret_url value=\"".addslashes(htmlentities($ret_url, ENT_QUOTES))."\">";
if (count($_tableau_databases)>1) {
  $login_form .= "<script type='text/javascript' src='./javascript/http_request.js'></script>
  <script type='text/javascript' src='./javascript/change_db.js'></script>
  <div class='row'>
    <label class='etiquette' for='database'>".$msg['choix_database']."</label>
  </div>
  <div class='row'><select name='database' class='liste_choix_db_login' onchange='change_db(this.options[this.selectedIndex].value);' $disabled_fields >";
  for ($idatabase=0;$idatabase<count($_tableau_databases);$idatabase++){
    $login_form .= "<option value='".$_tableau_databases[$idatabase]."' class='liste_choix_db_login'";
    if ($_tableau_databases[$idatabase] == LOCATION) {
      $login_form .= " selected";
    }
    $login_form .= ">".$_libelle_databases[$idatabase]."</option>" ;
  }
  $login_form .= "</select></div>" ;
} else {
  $login_form .= "<input type='hidden' name='database' value='".$_tableau_databases[0]."'>" ;
}

$login_form .= "
    <div class='row'>
      <label class='etiquette' for='user'>$msg[1]</label>
    </div>
    <div class='row'>
      <input class='saisie-20em' type='text' name='user' id='user' value='' size='15' $disabled_fields />
    </div>
    <div class='row'>
      <label class='etiquette' for='password'>$msg[2]</label>
    </div>
    <div class='row'>
      <input class='saisie-20em' type='password' name='password' id='password' value='' size='15' $disabled_fields/>
    </div>";

  if ($security_mfa_active) {
      $login_form .= "
      <div id='mfa-login' style='display: none'>
        <div class='row'>
          <span id='mfa-notify'></span>
        </div>
        <div class='row'>
          <label class='etiquette' for='code-otp'>" . $msg['mfa_login_input_label'] . "</label>
        </div>
        <div class='row'>
          <input class='saisie-20em' type='text' name='otp' id='otp' value='' size='15'/>
        </div>
        <div class='row'>
          <a href='#' id='btn_send_mail' type='button' onclick='send_code_otp(\"send_mail\")'>" . $msg['mfa_login_send_mail'] . "</a>
        </div>
        <div class='row'>&nbsp</div>
      </div>";
    }

    $login_form .= "<div class='row'></div>
    <!--    Bouton d'envoi    -->
      <div class='row'>
        <input type='submit' class='bouton' value='$msg[715]' $disabled_fields />
      </div>
      <div id='error' class='row'>
          !!erreur!!
      </div>";

    if ($security_mfa_active) {
     $login_form .= "<div id='error-mfa' class='row'>
          <h4 class='erreur' style='display: none'>" . $msg['mfa_login_error'] . "</h4>
      </div>";
    }

    $login_form .= "</form>
    </div>
    <div class='pmb_login_message' id='pmb_login_message'>".$pmb_login_message."</div>
    <div class='login_message' id='login_message'>!!login_message!!</div>
  </div>
</div>";

$login_form_demo = "
<div id='login-box'>
            <h1>".$msg['demo']." $msg[1001]</h1>
        <form class='form-$current_module' id='login' method='post' action='./main.php'>
        <div class='form-contenu'>
            <div class='row'>
                <label class='etiquette' for='user'>$msg[767]</label>
            </div>
            <div class='row'>
                <select class='saisie-20em' name='user' id='user' selected='selected' style='width: 90%;'>
                    <option value='fr'>fran�ais</option>
                    <option value='es'>espa�ol</option>
                    <option value='en'>english</option>
                    <option value='it'>italiano</option>
                </select>
                <!--<input type='text' class='text' name='user' id='user' value='' size='15'/>-->
            </div>
            <div class='row'>
                <!--<label for='password'>$msg[2]</label>-->
                <input type='hidden' name='password' id='password' value='demo' size='15' />
            </div>
        </div>

        <!--    Bouton d'envoi    -->
        <div class='row'>
            <input type='submit' class='bouton' value='$msg[715]' />
        </div>
        </form>
        <div class='row'>
            !!erreur!!
            </div>
</div>
<br />
<p>Suite &agrave; quelques questions de n&eacute;ophytes, je pr&eacute;sente ici
  quelques rappels vis-&agrave;-vis de la base de cette d&eacute;monstration en ligne
  et sur PMB de mani&egrave;re plus g&eacute;n&eacute;rale.</p>
<p>Avertissements:</p>
<blockquote>
  <p> La base de d&eacute;montration n'est pas charg&eacute;e
    avec un th&eacute;saurus mais simplement avec un petit r&eacute;pertoire d'<strong>autorit&eacute;s
    mati&egrave;res</strong>, de mots cl�s, hi&eacute;rarchis&eacute;es mais pas li&eacute;es.
    Vous n'aurez donc ici aucun aper&ccedil;u de la navigation dans les termes
    associ&eacute;s. De m&ecirc;me, ce r&eacute;pertoire ne comporte pas de termes
    non descripteurs et vous ne pourrez donc pas voir ces renvois.<br />
    Cette base est charg&eacute;e avec une indexation <strong>type Dewey</strong>
    sans que celle-ci ne soit ni vraiment une Dewey ni vraiment correcte. Ceci
    est d'autant plus vrai que cette base accessible publiquement subit fr&eacute;quemment
    des polutions &eacute;videntes. La Dewey n'est qu'une des multiples indexations
    ou plan de classement utilisables dans PMB.</p>
  <p>Vous allez pouvoir utiliser PMB dans une <strong>version quasiment int&eacute;grale</strong>.
    Nous attirons toutefois votre attention sur le fait que, sans aide, sans explication,
    la richesse des &eacute;crans peut vous paraitre complexe. N'h&eacute;sitez
    pas &agrave; nous appeler au +33 2 43 440 660 pour une d&eacute;monstration
    en ligne sur notre serveur o&ugrave; vous pourrez b&eacute;n&eacute;ficier
    des fonctionnalit&eacute;s les plus riches comme le catalogage en <strong>int&eacute;gration
    de la BNF</strong>, le d&eacute;doublonnage acc&eacute;l&eacute;r&eacute;,
    l'utilisation d'un <strong>th&eacute;saurus approfondi</strong>, la navigation
    par section de l'OPAC, la gestion compl&egrave;te des <strong>r&eacute;servations</strong>,
    les documents num&eacute;riques...</p>
</blockquote>
<p>Comment commencer ?</p>
<blockquote>
  <p> Mettez-vous &agrave; la place d'un lecteur, d'un &eacute;l&egrave;ve, d'un ami
    qui ne connait rien au m&eacute;tier de biblioth&eacute;caire ou de documentatliste
    et lancez une interrogation en <a href='./opac_css/' target='_blank'>interface publique : l'OPAC</a><br />
    Cela vous permettra de suite d'appr&eacute;hender la <strong>convivialit&eacute;
    de PMB</strong>. Tapez simplement &quot;*&quot; dans un champ de recherche
    pour interroger le fonds sans aucun crit&egrave;re, consultez l'aide en ligne
    et d&eacute;couvrez la puissance et la finesse des recherches.<br />
    Attention, PMB trie les r&eacute;sultats par ordre de <strong>pertinence</strong>
    calcul&eacute;e sur les mots que vous avez cherch&eacute;s (en faisant un
    OU), l'ordre alphab&eacute;tique n'est utilis&eacute; qu'en seconde cl&eacute;
    de tri.<br />
    Vous pouvez poursuivre votre navigation en cliquant sur les d&eacute;tails
    d'une notice : <strong>toutes les autorit&eacute;s sont navigables</strong>
    !</p>
</blockquote>
<p>Pour continuer en vrai &quot;pro&quot; : la gestion.</p>
<blockquote>
  <p> Ca se complique forc&eacute;ment un peu, si vous connaissez le m&eacute;tier,
    vous allez trouver rapidement vos rep&egrave;res, attention au vocabulaire,
    chaque logiciel a des termes et des fa&ccedil;ons de faire qui lui sont propres.<br />
    Gardez bien &agrave; l'esprit que &ccedil;a n'est pas parce que vous n'avez
    pas su faire que &ccedil;a n'est pas pr&eacute;sent : le proc&eacute;d&eacute;
    n'est pas forc&eacute;ment identique &agrave; votre logiciel habituel, peut-&ecirc;tre
    tout simplement que cette base de d&eacute;monstration ne le permet pas.<br />
    Les listes des utilisateurs <a href='http://www.sigb.net/listes.php' target ='_blank'>http://www.sigb.net/listes.php</a>
    peuvent vous apporter toute l'aide n&eacute;cessaire.<br />
  </p>
</blockquote>
<p><em>L'�cran de d�marrage ici correspond � la version de d�monstration : la
  langue que vous s�lectionnez correspond � un utilisateur utilisant l'application
  dans cette langue. </em></p>
<p><i>Ne vous �tonnez pas si votre th�me change en cours d'utilisation de PMB
  dans cette d�monstration, il est possible qu'un autre internaute utilise le
  m�me compte utilisateur que vous et change son th�me ! </i> </p>
<p><i>L'onglet Administration est d�sactiv� de cette version de d�monstration pour une question �vidente de s�curit�.
</i>
</p>
";

$login_form_error = "<h4 class='erreur'>$msg[10]</h4>";

// $index_header : template header index
$index_header = "<!DOCTYPE html>
<html lang='".get_iso_lang_code()."'>
<head>
  <meta charset=\"".$charset."\" />
    <title>
      $msg[1001]
    </title>
    <meta name='author' content='PMB Group' />
    <meta name='description' content='Logiciel libre de gestion de m�diath�que' />
    <meta name='keywords' content='logiciel, gestion, biblioth�que, m�diath�que, libre, free, software, mysql, php, linux, windows, mac, PMB $pmb_version' />
    <meta http-equiv='Pragma' content='no-cache' />
    <meta http-equiv='Cache-Control' content='no-cache' />";
//$stylesheet='couleurs_onglets' ;
$index_header.= HtmlHelper::getInstance()->getStyle($stylesheet);
$index_header.="
    <link rel=\"SHORTCUT ICON\" href=\"images/favicon.ico\">
    </head>
  <body class='index'>
";

$extra_version ="
<div id='extra'>".$msg['sauv_misc_restaure_db']." : <span id='extra_bdd'>".htmlentities(LOCATION, ENT_QUOTES, $charset)."</span> / ".sprintf($msg["print_n_notices"],'<span id=\'extra_nb_docs\'>'.$pmb_nb_documents.'</span>')."
</div>
";

// FIXMAX - new css id #nomenu, #noconteneur, #nocontenu
// used in the login screen

$login_menu="
    <div id='nomenu'>
    </div>";

// Barre de menu
//    Par d�faut : l'�chappatoire de l'appli...   ;-)
$nav_bar = $nav_bar."
  <div id='navbar'>
        <h3>&nbsp;</h3>
        <ul>
            <li id='navbar-index' class='current'>
        <a title='$msg[1913]' class='current' href='./' accesskey='$msg[2008]'>$msg[1913]</a>
      </li>
            <li id='navbar-opac'>
        <a title='$msg[1027]' href='$pmb_opac_url' target='_blank' accesskey='$msg[2007]' id='opac_url'>$msg[1026]</a>
      </li>
    </ul>
  </div>";

// affichage en fonction de
$index_layout = "
$index_header

$nav_bar
$extra_version
<div id='noconteneur'>
$login_menu
    <div id='nocontenu'>
";

$index_footer = "
</div>
<div id='footer'>
    <hr />
        <div class='left'>&nbsp;&nbsp;PMB $pmb_version (<a href='./changelogs.php' title='changelogs' style='margin-left: 0px;' target='_blank'>changelogs</a>) - database <span id='bdd_version'>$pmb_bdd_version</span> - &copy; 2002~".date("Y")."</div>
        <div class='right'><a title='www.sigb.net' href='$homepage' style='margin-left: 0px;' target=_blank>www.sigb.net</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a title='forge PMB' href='http://forge.sigb.net' style='margin-left: 0px;' target=_blank>forge.sigb.net</a>&nbsp;&nbsp;</div>
    </div>
</div>
</body>
</html>
";

