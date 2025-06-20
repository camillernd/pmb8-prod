<?php
/**
 * \defgroup sip2_protocol Protocol SIP2
 *
 * \brief Impl�mentation du protocol SIP2
 */

/**
 * \brief Gestion des �l�ments du protocole
 *
 * Affecte les messages, les variables autoris�es et les r�gles associ�es au protocol SIP2 � partir d'un fichier XML.
 * C'est le support de base pour impl�menter le protocole.
 * \ingroup sip2_protocol
 */
class sip2_protocol {
	public $error=false;
	public $error_message="";
	public $charset;
    public $version="";
    public $messages=array();
    public $fields=array();
    public $idenditifers=array();
    public $cur_id="";
    public $cur_elt;
    public $in_messages=false;
    public $identifiers=[];

    public function startElement($parser,$name,$attribs) {
    	$this->cur_elt=$name;
    	switch ($name) {
    		case "SIP":
    			$this->version=$attribs["VERSION"];
    			break;
    		case "MESSAGE":
    			$this->cur_id=$attribs["ID"];
    			$this->messages[$attribs["ID"]]["NAME"]=$attribs["NAME"];
    			$this->messages[$attribs["ID"]]["FROM"]=$attribs["FROM"];
    			switch ($attribs["FROM"]) {
    				case "SC":
    					$this->messages[$attribs["ID"]]["REPLY_ID"]=$attribs["REPLY_ID"] ?? null;
    					break;
    				case "ACS":
    					$this->messages[$attribs["ID"]]["REQUEST_ID"]=$attribs["REQUEST_ID"] ?? null;
    					break;
    			}
    			break;
    		case "MESSAGES":
    			$this->in_messages=true;
    			break;
    		case "FIELD":
    			$this->cur_id=$attribs["ID"];
    			$this->fields[$this->cur_id]["TYPE"]=$attribs["TYPE"];
    			$this->fields[$this->cur_id]["LEN"]=$attribs["LENGTH"];
    			if (isset($attribs["IDENTIFIER"])) {
					$this->fields[$this->cur_id]["IDENTIFIER"]=$attribs["IDENTIFIER"];
					$this->identifiers[$attribs["IDENTIFIER"]]=$this->cur_id;
    			}
    			break;
    	}
    }

    public function endElement($parser,$name) {
    	$this->cur_elt="";
    	switch ($name) {
    		case "MESSAGE":
    		    if(isset($this->messages[$this->cur_id]["FIELDS"]) && is_countable($this->messages[$this->cur_id]["FIELDS"])) {
        			for ($i=0; $i<count($this->messages[$this->cur_id]["FIELDS"]); $i++) {
        				$field=$this->messages[$this->cur_id]["FIELDS"][$i];
        				if ($field[strlen($field)-1]=="*") {
        					$optional=true;
        					$field=substr($field,0,strlen($field)-1);
        					$this->messages[$this->cur_id]["FIELDS"][$i]=$field;
        				} else $optional=false;
        				$this->messages[$this->cur_id]["OPTIONALS"][$field]=$optional;
        			}
    		    }
    			$this->cur_id="";
    			break;
    		case "MESSAGES":
    			$this->in_messages=false;
    			break;
    		case "FIELD":
    			$this->cur_id="";
    			break;
    	}
    }

    public function charElement($parser,$char) {
    	switch ($this->cur_elt) {
    		case "FIXEDFIELDS":
    			$fixedfields = explode(",", $char);
				if (!isset($this->messages[$this->cur_id]['FIXEDFIELDS'])) {
					$this->messages[$this->cur_id]['FIXEDFIELDS'] = [];
				}
    			$this->messages[$this->cur_id]["FIXEDFIELDS"]=array_merge((array)$this->messages[$this->cur_id]["FIXEDFIELDS"],$fixedfields);
    			break;
    		case "FIELDS":
    			if ($this->in_messages) {
    				$fields=explode(",", $char);
					if (!isset($this->messages[$this->cur_id]['FIELDS'])) {
						$this->messages[$this->cur_id]['FIELDS'] = [];
					}
    				$this->messages[$this->cur_id]["FIELDS"]=array_merge((array)$this->messages[$this->cur_id]["FIELDS"],$fields);
    			}
    			break;
    		case "ITEMS":
    			$items=explode(",",$char);
				if (!isset($this->fields[$this->cur_id]['ITEMS'])) {
					$this->fields[$this->cur_id]['ITEMS'] = [];
				}
    			$this->fields[$this->cur_id]["ITEMS"]=array_merge((array)$this->fields[$this->cur_id]["ITEMS"],$items);
    			break;
    	}
    }

    public function __construct($file,$charset="iso-8859-1") {
    	$this->charset=$charset;

    	//Lecture du fichier
    	$xml=file_get_contents($file);

    	//Initialisation du parser
		$xml_parser=xml_parser_create($this->charset);
		xml_set_object($xml_parser,$this);
		xml_parser_set_option( $xml_parser, XML_OPTION_CASE_FOLDING, 1 );
		xml_parser_set_option( $xml_parser, XML_OPTION_SKIP_WHITE, 1 );
		xml_set_element_handler($xml_parser, "startElement", "endElement");
		xml_set_character_data_handler($xml_parser,"charElement");

		if (!xml_parse($xml_parser, $xml)) {
       		$this->error_message=sprintf("XML error: %s at line %d",xml_error_string(xml_get_error_code($xml_parser)),xml_get_current_line_number($xml_parser));
       		$this->error=true;
		}

		xml_parser_free($xml_parser);
    }
}
?>