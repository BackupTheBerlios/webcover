<?php
// This file is part of the WebCover2 Framework
// http://webcover.berlios.de/webcover2
//
// This application is free software; you can redistribute it and/or
// modify it under the terms of the license defined in the LICENSE file.
// 
// Copyright (c) 2004,2005,2006,2007 Rafał Kotusiewicz. All rights reserved.

/**
 * Abstrakt akcji. Klasa do nadpisania przez uzytkownikow. Zawiera podstawowe metody
 * do obslugi parametrow żądania oraz przesyłania atrybutów do szablonu.
 *
 * @author Rafał Kotusiewicz <jjhop@users.berlios.de>
 * @version $Id: Action.php,v 1.1 2007/04/12 01:34:01 jjhop Exp $
 */
abstract class Action {

	private $actionforwards;
	private $globalForwards;

	private $response;
	private $response;

	private $post;
	private $get;
	private $cookies;
	private $session;

	/** @author Rafał */
	private $prepared_template;

	/**
	 * Konstruktor klasy...
	 * 
	 * @param array $post tablica zawierajaca parametry przeslane metoda POST ($_POST)
	 * @param array get tablica zawierajaca parametry przeslane w URLu (metoda GET) ($_GET)
	 * @param array cookies tablica zawierajaca parametry przeslane za pomoca ciasteczek ($_COOKIES)
	 * @param array session
	 * @param array request
	 * @param array response
	 * @param array $prepared_templates  przygotowany szablon dla akcji
	 */
	public function __construct($post, $get, $cookies, $request, $session, $response, $prepared_templates) {
		$this->post = $post;
		$this->get = $get;
		$this->cookies = $cookies;
		$this->request = $request;
		$this->session = & $session;
		$this->response = & $response;
		$this->prepared_templates = & $prepared_templates;

		$this->actionForwards = null;
		$this->globalForwards = null;
	}

	/**
	 * 
	 */
	abstract protected function execute();

	/**
	 * 
	 */
	public final function setForwards($actionforwards) {
		if (!is_array($actionforwards)) {
			die("\$actionforwards must be an array!");
		} else {
			$this->actionforwards = & $actionforwards;
		}
	}

	/**
	 * 
	 */
	public final function setGlobalForwards($globalForwards) {
		if (!is_array($globalForwards)) {
			die("\$globalForwards must be an array!");
		} else {
			$this->globalForwards = & $globalForwards;
		}
	}

	/**
	 * zwraca tablicę z danymi forwardu o przekazanej w argumencie nazwie
	*/
	function findForward($forward_name) {
		if ($this->actionForwards == null AND $this->globalForwards == null) {
			die('actionForwards & globalForwards is not set!');
		}
		if ((!array_key_exists($forward_name, $this->actionForwards)) || (!array_key_exists($forward_name, $this->globalForwards))) {
			die('Forward not exist!');
		}
		if (array_key_exists($forward_name, $this->actionForwards)) {
			return $this->actionForwards[$forward_name];
		} else {
			return $this->globalForwards[$forward_name];
		}
	}

	/**
	 * wrzucanie atrybutów do response'a
	*/
	function setAttribute($attrName, $attrValue) {
		$this->response[$attrName] = $attrValue;
	}

	/**
	 * 
	 */
	function __toString() {
		return "+++";
	}

}
?>
