<?php

require_once(TOOLKIT . '/class.xsltpage.php');
require_once(TOOLKIT . '/class.frontendpage.php');
require_once(CORE . '/class.frontend.php');

Class EmailTemplate extends XSLTPage{

	protected $_frontendPage;
	
	public function __construct(){
		parent::__construct();
		$this->_frontendPage = new FrontendPage(Symphony::Engine());
	}
	
	public function processDatasources(){
		$this->_frontendPage->processDatasources('test', $XML = new XMLElement('data'));
		return $XML;
	}
	
	public function generate(){
		if(is_null($this->getXML()))	$this->setXML($this->processDatasources()->generate());
		if(is_null($this->getXSL()))	$this->setXSL(EXTENSIONS . '/email_templates/templates/cat.xsl', true);
		return $this->generate();
	}
	
}