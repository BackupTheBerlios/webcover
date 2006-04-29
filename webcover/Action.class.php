<?php
  //
  // $Id: Action.class.php,v 1.2 2006/04/29 00:07:25 jjhop Exp $
  // Author: Rafa� Kotusiewicz <jjhop@codeguru.info>
  // 
  // This file is part of the WebCover Framework
  // http://webcover.berlios.de.
  //
  // This application is free software; you can redistribute it and/or
  // modify it under the terms of the Ruby license defined in the
  // LICENSE file.
  // 
  // Copyright (c) 2004,2005,2006 Rafal Kotusiewicz. All rights reserved.
  //
  
  class Action {
      var $_actionForwards;
      var $_globalForwards;
      var $_post;
      var $_get;
      var $_cookies;
      var $_request;
      var $_response;
      var $_prepared_tamplates;
      
      function Action($_post,               // dane przekazane metod� POST  (zwykle tablica $_POST) 
                      $_get,                // dane przekazane metod� GET   (zwykle tablica $_GET) 
                      $_cookies,            // dane przekazane jako cookies (zwykle tablica $_COOKIES)
                      $_request,            // dane ��dania                 (zwykle tablica $_REQUEST)
                      $_session,            // dane sesji                   (zwykle tablica $_SESSION)
                      $_response,           // tablica do przechowania danych odpowiedzi
                      $_prepared_template   // przygotowany szablon dla akcji (zwykle nie u�ywany w akcji
      ) {
          $this->_post     = $_post;
          $this->_get      = $_get;
          $this->_cookies  = $_cookies;
          $this->_request  = $_request;
          $this->_session  = &$_session;
          $this->_response = &$_response;
          $this->_prepared_templates = &$_prepared_templates;
          
          $this->_actionForwards = null;
          $this->_globalForwards = null;
      }
	  
      /*
      // zwraca atrybut �adania wed�ug klucza
      // kolejno�� wyszukiwania wed�ug ustawie�
      // w konfiguracji PHP (php.ini)
      */
      function getAttribute($attr) {
          if(is_set($GLOBALS[$attr])) {
              return $GLOBALS[$attr];
          }
      }
	  
      /*
      // metoda do nadpisania w klasach potomnych
      // jako parametr przekazujemy tablic� danych
      */
      function prepare($_pepared_data) {
      }

      /*
      // funkcja zawieraj�ca logik� akcji i wywo�ywana zaraz po prepare()
      // KONIECZNIE NADPISA� W KLASIE POCHODNEJ !!! 
      */
      function execute() {
          die("Action::execute() this method must be covered!");
      }
      
      /*
      // metoda wywo�ywana przez kontroler
      // ustawiaj�ca dane akcji
      //
      // NIE NADPISYWA� W KLASACH POTOMNYCH!!!
      */
      function setForwards($forwards) {
          if(!is_array($forwards)) {
              die("\$forwards must be an array!");
          } else {
              $this->_actionForwards = &$forwards;
          }
      }
	  
      /*
      // metoda ustawiajaca globalne forwardy
      //
      // NIE NADPISYWA�! DON'T OVERWRITE!
      */
      function setGlobalForwards($globalForwards) {
          if(!is_array($globalForwards)) {
              die("\$globalForwards must be an array!");
          } else {
              $this->_globalForwards = &$globalForwards;
          }
      }
		  
      /*
      // zwraca tablic� z danymi forwardu 
      // o przekazanej w argumencie nazwie
      */
      function findForward($_forward_name) {
          if($this->_actionForwards == null AND $this->_globalForwards == null) {
              die('_actionForwards & _globalForwards is not set!');
          }
          if((!array_key_exists($_forward_name, $this->_actionForwards)) || (!array_key_exists($_forward_name, $this->_globalForwards))) {
              die('Forward not exist!');
          }
          if(array_key_exists($_forward_name, $this->_actionForwards)) {
              return $this->_actionForwards[$_forward_name];
          } else {
              return $this->_globalForwards[$_forward_name];
          }
      }
      
      /*
      // wrzucanie atrybut�w do response'a
      */
      function setAttribute($attrName, $attrValue) {
          $this->_response[$attrName] = $attrValue;
      }
  }
?>
