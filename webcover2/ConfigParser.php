<?php
// This file is part of the WebCover2 Framework
// http://webcover.berlios.de/webcover2
//
// This application is free software; you can redistribute it and/or
// modify it under the terms of the license defined in the LICENSE file.
// 
// Copyright (c) 2004,2005,2006,2007 Rafał Kotusiewicz. All rights reserved.

/**
 * Klasa parsujaca plik konfiguracyjny.
 * 
 * @author Rafał Kotusiewicz <jjhop@users.berlios.de>
 * @version $Id: ConfigParser.php,v 1.1 2007/04/12 02:19:10 jjhop Exp $
 */
final class ConfigParser {

	/**
	 * @var string
	 */
	private $current_tag;
	
	/**
	 * @var bool
	 */
	private $smarty_config_tag;
	
	/**
	 * @var bool
	 */
	private $actions_conf_tag;
	
	/**
	 * @var bool
	 */
	private $action_paths_tag;
	
	/**
	 * @var bool
	 */
	private $template_paths_tag;
	
	/**
	 * @var bool
	 */
	private $private_forwards_tag;
	
	/**
	 * @var bool
	 */
	private $last_action_path;

	/**
	 * @var bool
	 */
	private $actions_mapping_tag;
	
	/**
	 * @var bool
	 */
	private $action_tag;

	/**
	 * @var array
	 */
	private $configuration;

	/**
	 * @var string
	 */
	private $configFile;

	/**
	 * @
	 */
	public function __construct($configFile) {
		if (!function_exists('xml_parser_create_ns')) { // brak obsługi XML'a :(
			die('I can\'t find xml extension.');
		}
		$this->configFile = $configFile;
	}

	/**
	 * zwraca konfiguracje jesli jest aktualna i rozna od null, 
	 * w przeciwnym wypadku najpierw parsuje plik konfiguracyjny.
	 */
	public function getConfiguration() {

	}

	private function parseConfiguration() {
		$parser = xml_parser_create_ns();
		xml_set_element_handler($parser, 'start_tag', 'close_tag');
		xml_set_character_data_handler($parser, 'characters_data');
	}

	private function start_tag($parser, $tag_name, $attribs) {
		$this->current_tag = $tag_name;
		switch ($this->current_tag) {
			case 'smarty-config' :
				$this->smarty_config_tag = true;
				break;
			case 'actions-conf' :
				$this->actions_conf_tag = true;
				break;
			case 'action-paths' :
				$this->action_paths_tag = true;
				break;
			case 'template-paths' :
				$this->template_paths_tag = true;
				break;
			case 'global-forwards' :
				$this->global_forwards_tag = true;
				break;
			case 'actions-mapping' :
				$this->actions_mapping_tag = true;
				break;
			case 'action' :
				$this->action_tag = true;
			default :
				break;
		}

		if ($tag_name == 'db-conf') {
			$this->configuration['db_conf'] = array ();
		}

		/*
		// odczytywanie inforamcji o cashe'owaniu 
		*/
		if ($tag_name == 'cache-dir') {
			$this->configuration['smarty_config']['cache_dir']['caching'] = $attribs['caching'];
		}

		/*
		// odczyt konfiguracji globalnych forwardów
		*/

		if ($tag_name == 'forward' AND $this->global_forwards_tag == true) {
			$name_ = $attribs['name'];
			$type_ = $attribs['type'];
			$path_ = $attribs['path'];

			if ($type_ == 'action')
				$path_ = str_replace('${action-suffix}', $this->configuration['actions_conf']['action_suffix'], $path_); // to z XML'a | zamieniamy na to - tez z XML'a | na tym pracujemy
			$forward_ = array (
				'type' => $type_,
				'path' => $path_
			);
			$this->configuration['actions_conf']['global_forwards'][$name_] = $forward_;
		}

		/*
		// odczyt konfiguracji akcji
		*/
		if ($tag_name == 'action' AND $this->actions_mapping_tag == true) {
			// mamy jak± akcj
			$path_ = $attribs['path'] . $this->configuration['actions_conf']['action_suffix'];
			$class_ = $attribs['class'];
			$this->configuration['actions_conf']['actions_mapping'][$path_]['class'] = $class_ . '.class.php';
			$this->configuration['actions_conf']['actions_mapping'][$path_]['forwards'] = array ();
			$this->last_action_path = $path_;
		}

		/*
		// "doczytywanie" forwardów do akcji
		*/
		if ($tag_name == 'forward' AND $this->actions_mapping_tag == true) {
			// mamy forwarda z akcji
			$name_ = $attribs['name'];
			$type_ = $attribs['type'];
			$path_ = $attribs['path'];

			if ($type_ == 'action') {
				$path_ = str_replace('${action-suffix}', $this->configuration['actions_conf']['action_suffix'], $path_); // to z XML'a | zamieniamy na to - tez z XML'a | na tym pracujemy                                             
			} else {
				$path_ .= $this->configuration['actions_conf']['template_suffix'];
			}
			$forward_data = array (
				'type' => $type_,
				'path' => $path_
			);
			$this->configuration['actions_conf']['actions_mapping'][$this->last_action_path]['forwards'][$name_] = $forward_data;
		}

	}

	private function close_tag($parser, $tag_name) {
		$this->current_tag = null;
		switch ($tag_name) {
			case 'smarty-config' :
				$this->smarty_config_tag = false;
				break;
			case 'actions-conf' :
				$this->actions_conf_tag = false;
				break;
			case 'action-paths' :
				$this->action_paths_tag = false;
				break;
			case 'template-paths' :
				$this->template_paths_tag = false;
				break;
			case 'global-forwards' :
				$this->global_forwards_tag = false;
				break;
			case 'actions-mapping' :
				$this->actions_mapping_tag = false;
				break;
			case 'action' :
				$this->action_tag = false;
			default :
				break;
		} // END switch
	}

	function characters_data($parser, $data) {
		// parsowanie danych do poł±czenia z baz± danych
		if ($this->current_tag == 'type-connect') {
			$this->configuration['db_conf']['type_connect'] = $data;
		}
		elseif ($this->current_tag == 'host') {
			$this->configuration['db_conf']['host'] = $data;
		}
		elseif ($this->current_tag == 'port') {
			$p = (int) $data;
			if ($p != $data)
				die(htmlspecialchars('<db-conf><port>' . $data . '</port></db-conf> must be integer!'));
			else
				$this->configuration['db_conf']['port'] = $p;
		}
		elseif ($this->current_tag == 'user') {
			$this->configuration['db_conf']['user'] = $data;
		}
		elseif ($this->current_tag == 'pass') {
			$this->configuration['db_conf']['pass'] = $data;
		}
		elseif ($this->current_tag == 'dbname') {
			$this->configuration['db_conf']['dbname'] = $data;
		}

		// parsowanie konfiguracji smarty
		if ($this->current_tag == 'smarty-dir') {
			$this->configuration['smarty_config']['smarty_dir'] = $data;
		}
		elseif ($this->current_tag == 'compile-dir') {
			$this->configuration['smarty_config']['compile_dir'] = $data;
		}
		elseif ($this->current_tag == 'hconfig-dir') {
			$this->configuration['smarty_config']['config_dir'] = $data;
		}
		elseif ($this->current_tag == 'cache-dir') {
			$this->configuration['smarty_config']['cache_dir']['dir'] = $data;
		}

		// parsowanie danych pomocniczych 
		if ($this->current_tag == 'action-suffix') {
			$this->configuration['actions_conf']['action_suffix'] = $data;
		}
		elseif ($this->current_tag == 'template-suffix') {
			$this->configuration['actions_conf']['template_suffix'] = $data;
		}

		// parsowanie ścieżek do plików klas z akcjami oraz szablonami
		if ($this->current_tag == 'path' AND $this->action_paths_tag == true) {
			$this->configuration['actions_conf']['action_paths'][] = $data;
		}
		elseif ($this->current_tag == 'path' AND $this->template_paths_tag == true) {
			$this->configuration['actions_conf']['template_paths'][] = $data;
		}
	}
}
?>
