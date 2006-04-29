<?php
	$_start = microtime();
	//
	// $Id: ActionController.php,v 1.2 2006/04/29 00:19:50 jjhop Exp $
	// Author: Rafa³ Kotusiewicz <jjhop@users.berlios.de>
	// 
	// This file is part of the WebCover Framework
	// http://webcover.berlios.de
	//
	// This application is free software; you can redistribute it and/or
	// modify it under the terms of the Ruby license defined in the
	// LICENSE file.
	// 
	// Copyright (c) 2004,2005,2006 Rafa³ Kotusiewicz. All rights reserved.
	//
  
	ini_set('session.name', '_WEBCOVER_SESSID');
	session_start();
    
	/*
	// globalna zmienna przechowuj±ca aktualn± konfiguracjê
	// przydatna tylko w przypadku konieczno¶ci skompilowania
	// pliku konfiguracyjnego XML
	*/
	$configuration;

	/*
	// zmienne wspomagaj±ce parsowanie XML'a
	*/
	$current_tag;   	// zawsze przechowuje warto¶æ bie¿¹cego taga
	$smarty_config_tag;     // pokazuje czy jestesmy zagnie¿dzeni w smarty-config
	$actions_conf_tag;	//                -- // --              action-paths 
	$action_paths_tag;	//                -- // --              action-paths
	$template_paths_tag;	//                -- // --              template-paths
	$global_forwards_tag;   //                -- // --              global-forwards
			
	$actions_mapping_tag;   //                -- // --              mapowaniach akcji
	$action_tag;            //                -- // --              konkretnej akcji
	
	$last_action_path;      // sciezka do ostatniej akcji
   
	$path_sep;              // separator katalogów
    
	class ActionControler {
		/*
		// ¶cie¿ka bezwzglêdna do pliku konfiguracyjnego
		*/
		var $config_file; // XMLconfig
		
		/*
		// ¶cie¿ka bezwzglêdna do skompilowanego
		// pliku konfiguracyjnego
		*/
		var $compiled_config_file; // web-config.compiled.php
		
		/*
		//
		*/
		var $config_file_ok;
		
		/*
		// data ostatniego parsowania konfiguracji
		*/
		var $last_parse_timestamp;

		
		function ActionControler() {
			/*
			// przyogotwujemy ¶ciezke do pliku z konfiguracja
			*/
			$this->prepare_config_file_path();
			
			/*
			// sprawdzamy czy kompilacja jest aktualna
			// i w razie potrzeby wykonujemy j± ponownie
			*/
			$this->check_last_parse_timestamp();
			
			if(filemtime($this->config_file) > $this->last_parse_timestamp) {
				// parsujmey konfiguracjê
				$this->parse_config();
			}
			/*
			// odczytujmey konfiguracjê ze skompilowanego pliku
			// w tym miejsce na pewno jest taki plik w systemie
			// ale sprawdzamy (moze nie mozna go bylo utworzyc
			// po wykonaniu kompilacji)
			*/
			if(file_exists($this->compiled_config_file)) {
				//include_once($this->compiled_config_file);
			} else {
				// FIXME: poprawiæ obslugê b³edu
				//die("Nie mogê odczytaæ skompilowanej konfiguracji");
			}
		}

		/*
		// metoda sprawdzaj±ca datê ostatniej kompilacji konfiguacji
		*/
		function check_last_parse_timestamp() {
			if(file_exists($this->compiled_config_file)) {
				$this->last_parse_timestamp = filemtime($this->compiled_config_file);
			} else {
				$this->last_parse_timestamp = mktime (0,0,0,1,1,1970);
			}
		}
		
		/*
		// metoda przygotowuj±ca ¶cie¿kê bezwzglêdn±
		// pliku konfiguracyjnego
		// NOTE: metoda zaimplementowawa zgodnie z za³ó¿eniami oprócze $path_sep
		*/
		function prepare_config_file_path() { // web-config.xml
            
			global $path_sep;
			
			$file_dir = dirname( __FILE__ );
			
			/*
			// domy¶lnym separatorem katalogów
			// jest '/' ale jesli w php.ini ustawione 
			// jest inaczej to pobieramy warto¶æ stamt±d
			*/
			// FIXME: do poprawki! ustalenie powinno byæ na podstawie systemu operacyjnego, na którym dzia³a serwer
			$ini_path_separator = ini_get("path_separator");
			if(!empty($ini_path_separator))
				$path_sep = ini_get("path_separator"); 
			else
				$path_sep = "/";
                
			// gdzie jest ostatni file_separator ?
			
			$length_to_delete = strlen(strrchr($file_dir, $path_sep)); 
			$delete_offset    = strlen($file_dir) - $length_to_delete; 
			
			$config_path[] = substr($file_dir, 0, $delete_offset) . $path_sep . "web-config.xml"; // ten jest w±¶niejszy jesli jest
			$config_path[] = $file_dir . $path_sep . "web-config.xml";                            // ten bierzemy je¶li brak poprzedniego
			
			foreach($config_path as $k => $f_path) {
				if(file_exists($f_path)) {
					$this->config_file = $f_path;
					$this->compiled_config_file = substr( $f_path, 0, (strlen($f_path) - 3)) . 'compiled.php';
					break;
				}
			}
		}
        
		/*
		// metoda wykonuj±ca parsowanie (kompilacjê) pliku konfiguracyjnego
		*/
		function parse_config() {
			//global $configuration;
			if(!function_exists('xml_parser_create_ns')) {
				// brak obs³ugi XML'a :(
				die('can\'t found xml extension');
			} else {
                
				/*
				// metody wspomagaj±ce parsowanie pliku konfiguracyjnego
				*/                
				function start_tag($parser, $tag_name, $attribs) {
					global $configuration;
					global $current_tag;
					global $smarty_config_tag;
					global $actions_conf_tag;
					global $action_paths_tag;
					global $template_paths_tag;
					global $global_forwards_tag;
					
					global $actions_mapping_tag;
					global $action_tag;
					
					global $last_action_path;
					
					$current_tag = $tag_name;
					//log_($current_tag);   
					switch($current_tag) {
						case 'smarty-config':
							$smarty_config_tag = true;
							break;
						case 'actions-conf':
							$actions_conf_tag = true;
							break;
						case 'action-paths':
							$action_paths_tag = true;
							break;
						case 'template-paths':
							$template_paths_tag = true;
							break;
						case 'global-forwards':
							$global_forwards_tag = true;
							break;
						case 'actions-mapping':
							$actions_mapping_tag = true;
							break;
						case 'action':
							$action_tag = true;
						default:
							break;
					}
                    
					if($tag_name == 'db-conf') {
						$configuration['db_conf'] = array();
					} 
					
					/*
					// odczytywanie inforamcji o cashe'owaniu 
					*/
					if($tag_name == 'cache-dir') {
						$configuration['smarty_config']['cache_dir']['caching'] = $attribs['caching'];
					}
					
					/*
					// odczyt konfiguracji globalnych forwardów
					*/
                    
					if($tag_name == 'forward' AND $global_forwards_tag == true) {
						//log_($attribs['name']);
						$name_ = $attribs['name'];
						$type_ = $attribs['type'];
						$path_ = $attribs['path'];
						
						if( $type_ == 'action' )
							$path_ = str_replace( '${action-suffix}',         // to z XML'a 
							$configuration['actions_conf']['action_suffix'],  // zamieniamy na to - tez z XML'a
							$path_ );                                         // na tym pracujemy
						$forward_ = array(
							'type' => $type_,
							'path' => $path_);
						$configuration['actions_conf']['global_forwards'][$name_] = $forward_;
					}
                    
					/*
					// odczyt konfiguracji akcji
					*/
					if($tag_name == 'action' AND $actions_mapping_tag == true) {
						// mamy jak±¶ akcjê
						$path_  = $attribs['path'] . $configuration['actions_conf']['action_suffix']; 
						$class_ = $attribs['class'];
						$configuration['actions_conf']['actions_mapping'][$path_]['class']    = $class_ . '.class.php';
						$configuration['actions_conf']['actions_mapping'][$path_]['forwards'] = array();
						$last_action_path = $path_;
					}

					/*
					// "doczytywanie" forwardów do akcji
					*/
					if($tag_name == 'forward' AND $actions_mapping_tag == true) {
						// mamy forwarda z akcji
						$name_ = $attribs['name'];
						$type_ = $attribs['type'];
						$path_ = $attribs['path'];
						
						if( $type_ == 'action' ) {
							$path_ = str_replace( '${action-suffix}',         // to z XML'a 
							$configuration['actions_conf']['action_suffix'],  // zamieniamy na to - tez z XML'a
							$path_ );                                         // na tym pracujemy                                            
						} else {
							$path_ .= $configuration['actions_conf']['template_suffix'];
						}
						$forward_data = array(
							'type' => $type_, 
							'path' => $path_ );
						$configuration['actions_conf']['actions_mapping'][$last_action_path]['forwards'][$name_] = $forward_data; 
					}
				}  // END close_tag()
                
				function close_tag($parser, $tag_name) {
					//global $configuration;
					global $current_tag;
					global $smarty_config_tag;                    
					global $actions_conf_tag;
					global $action_paths_tag;
					global $template_paths_tag;
					global $global_forwards_tag;
					
					global $actions_mapping_tag;
					global $action_tag;                    
					
					$current_tag = "";
					switch($tag_name) {
						case 'smarty-config':
							$smarty_config_tag = false;
							break;
						case 'actions-conf':
							$actions_conf_tag = false;
							break;
						case 'action-paths':
							$action_paths_tag = false;
							break;
						case 'template-paths':
							$template_paths_tag = false;
							break;
						case 'global-forwards':
							$global_forwards_tag = false;
							break;
						case 'actions-mapping':
							$actions_mapping_tag = false;
							break;
						case 'action':
							$action_tag = false;
						default:
							break;
					} // END switch
				}  // END close_tag()
                
				function characters_data($parser, $data) {
					global $configuration;
					
					global $current_tag;
					//global $smarty_config_tag; 
					global $actions_conf_tag;
					global $action_paths_tag;
					global $template_paths_tag;
					global $global_forwards_tag;
					
					global $actions_mapping_tag;
					global $action_tag;
					/*
					// parsowanie danych do po³±czenia z baz± danych
					*/
					if($current_tag == 'type-connect') {
						$configuration['db_conf']['type_connect']	= $data;
					} elseif($current_tag == 'host') {
						$configuration['db_conf']['host']		= $data;
					} elseif($current_tag == 'port') {
						$p = (int)$data;
						if($p != $data)  
							die(htmlspecialchars('<db-conf><port>' . $data . '</port></db-conf> must be integer!'));
						else 
							$configuration['db_conf']['port']	= $p;
					} elseif($current_tag == 'user') {
						$configuration['db_conf']['user']		= $data;
					} elseif($current_tag == 'pass') {
						$configuration['db_conf']['pass']		= $data;
					} elseif($current_tag == 'dbname') {
						$configuration['db_conf']['dbname']		= $data;
					}
                    
					/*
					// parsowanie konfiguracji smarty
					*/
					if($current_tag == 'smarty-dir') {
						$configuration['smarty_config']['smarty_dir']  = $data;
					} elseif($current_tag == 'compile-dir') {
						$configuration['smarty_config']['compile_dir'] = $data;
					} elseif($current_tag == 'hconfig-dir') {
						$configuration['smarty_config']['config_dir']  = $data;
					} elseif($current_tag == 'cache-dir') {
						$configuration['smarty_config']['cache_dir']['dir'] = $data;
					}
                    
					/*
					// parsowanie danych pomocniczych 
					*/
					if($current_tag == 'action-suffix') {
						$configuration['actions_conf']['action_suffix'] = $data;
					} elseif($current_tag == 'template-suffix') {
						$configuration['actions_conf']['template_suffix'] = $data;
					}
					
					/*
					// parsowanie ¶cie¿ek do plików klas z akcjami oraz szablonami
					*/
					if($current_tag == 'hpath' AND $action_paths_tag == true) {
						$configuration['actions_conf']['action_paths'][] = $data;
					} elseif($current_tag == 'path' AND $template_paths_tag == true) {
						$configuration['actions_conf']['template_paths'][] = $data;
					}
				}  // END characters_data()
                
				$parser = xml_parser_create_ns();
				xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);
				xml_set_element_handler($parser, 'start_tag', 'close_tag');
				xml_set_character_data_handler($parser, 'characters_data');
				
				if(!($fh = fopen($this->config_file, 'r'))) {
					die("Nie ma pliku konfiguracyjnego w formacie XML!");
				}

				while($xml_data = fread($fh, 4096)) {
					if(!xml_parse($parser, $xml_data, feof($fh))) {
						die(sprintf("B³±d XML: %s w wierszu %d",
							xml_error_string(xml_get_error_code($parser)),
							xml_get_current_line_number($parser)));
						xml_parser_free($parser);
					} // END-IF
				} // END-WHILE
				xml_parser_free($parser);
			} // END-IF-ELSE
		}
		
		/*
		// metoda g³ówna frameworku, której zadaniem jest 
		// odnalezienie w konfiguraji "zmapowanej" akcji
		// (w przypadku braku takowej zg³asza b³±d), odna-
		// lezienie skojarzonych z ni± "forwardów" i po 
		// utworzeniu obiektu akcji, wykonaniu jej
		*/
		function execute() {
			// uzupelniamy include_paths
			for($i = 0; $i < sizeof($this->configuration['actions_conf']['action_paths']); $i++) {
				$path_ = ":" . $this->configuration['actions_conf']['action_paths'][$i];
				ini_set("include_path", ini_get("include_path") . $path_);
			}
		}
	} // END of class ActionController
  
    
	/*
	// Tutaj rozpoczynaj± sie dzia³ania maj±ce na celu
	// obsluzenie ¿adania, dopasowanie szablonu itp...
	*/
	$controler = new ActionControler(); // dopisaæ resztê
	$controler->execute();
    
    
	define('SMARTY_DIR', $configuration['smarty_config']['smarty_dir']);
	require_once(SMARTY_DIR . 'Smarty.class.php');
	include_once(dirname(__FILE__) . $path_sep .  'Action.class.php');
    
	/*
	// konfiguracja zosta³a wczytana
	// mozemy zaj±c sie przygotowywaniem kalsy Smarty
	*/
	$_smarty_class_code  = 'class WebCoverSmarty extends Smarty {' . "\n";
	$_smarty_class_code .= "    \t".'function WebCoverSmarty() {' . "\n";
	$_smarty_class_code .= "    \t\t" . '$this->Smarty();' . "\n";  
	$_smarty_class_code .= "    \t\t" . '$this->template_dir="' . $configuration['actions_conf']['template_paths'][0]     . "\";\n";
	$_smarty_class_code .= "    \t\t" . '$this->compile_dir="'  . $configuration['smarty_config']['compile_dir']          . "\";\n";
	$_smarty_class_code .= "    \t\t" . '$this->config_dir="'   . $configuration['smarty_config']['config_dir']           . "\";\n";
	$_smarty_class_code .= "    \t\t" . '$this->cache_dir="'    . $configuration['smarty_config']['cache_dir']['dir']     . "\";\n";
	$_smarty_class_code .= "    \t\t" . '$this->caching='       . $configuration['smarty_config']['cache_dir']['caching'] . ";\n";
	$_smarty_class_code .= "    \t}\n";
	$_smarty_class_code .= "}\n";
	
	eval($_smarty_class_code);
	
	$_template = new WebCoverSmarty();
	$_attributes = array();
	for($_p = 0; $_p < sizeof($configuration['actions_conf']['action_paths']); $_p++) {
		$_tmp_dir = $configuration['actions_conf']['action_paths'][$_p];
		if(is_dir($_tmp_dir)) { // jest katalogiem
			$_last_index = strlen($_tmp_dir) - 1;
			if($_tmp_dir[$_last_index] == $path_sep) { // jest ok, czyli na koñcu ma '/' lub '\'
				$_file_to_inc = $_tmp_dir . $configuration['actions_conf']['actions_mapping'][$_SERVER["SCRIPT_URL"]]['class'];
			} else {
				$_file_to_inc = $_tmp_dir . $path_sep . $configuration['actions_conf']['actions_mapping'][$_SERVER["SCRIPT_URL"]]['class']; 
			}
			if(file_exists($_file_to_inc)) {
				// jesli plik istnieje
				include_once($_file_to_inc);
				break;
			}
		} else {
			// tutaj jakas obsluga bledu, komunikat ipt..
			die("Katalog '$_tmp_dir' nie istnieje lub nie jest katalogiem...");
		}
    }
	$ActionName = substr( $configuration['actions_conf']['actions_mapping'][$_SERVER[SCRIPT_URL]]['class'], 0, 
						( strlen($configuration['actions_conf']['actions_mapping'][$_SERVER[SCRIPT_URL]]['class']) - 10));
	$_response = array();
                          
	$_obj_create_code = '$_action = new ' . "$ActionName" . '($_POST, $_GET, $_COOKIES, $_REQUEST, &$_SESSION, &$_response, $_template);';
	eval($_obj_create_code);
	$_action->setForwards($configuration['actions_conf']['actions_mapping'][$_SERVER[SCRIPT_URL]]['forwards']);	
	$_action->setGlobalForwards($configuration['actions_conf']['global_forwards']);
	$_view = $_action->execute();

	foreach($_response as $_key => $_value) {
		$_template->assign($_key, $_value);
	}
	$_template->display($_view);
?>
