<?php

require_once(TOOLKIT . '/class.xsltpage.php');
require_once(TOOLKIT . '/class.frontendpage.php');
require_once(CORE . '/class.frontend.php');

Class EmailTemplate extends XSLTPage{
	
	protected $_frontendPage;
	
	protected $_name = null;
	
	protected $_config = Array();
	
	public function __construct(){
		parent::__construct();
		//$this->_frontendPage = new FrontendPage(Symphony::Engine());

	}
	
	public function setName($name){
		$this->_name = $name;
	}
	
	public function getName(){
		return $this->_name;
	}
	
	public function processDatasources(){
		$this->_frontendPage->processDatasources('test', $XML = new XMLElement('data'));
		return $XML;
	}	
}

Class EmailTemplateException extends Exception{
}