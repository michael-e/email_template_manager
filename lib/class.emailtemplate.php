<?php

require_once(TOOLKIT . '/class.xsltpage.php');
require_once(TOOLKIT . '/class.frontendpage.php');
require_once(CORE . '/class.frontend.php');

Class EmailTemplate extends XSLTPage{
	
	protected $_frontendPage;
	public $ExtensionManager;
	
	protected $config = Array();
	
	public function __construct(){
		parent::__construct();
		//needed for debug devkit.
		$this->ExtensionManager = Symphony::ExtensionManager();
		$this->_param = array(
			'today' => DateTimeObj::get('Y-m-d'),
			'current-time' => DateTimeObj::get('H:i'),
			'this-year' => DateTimeObj::get('Y'),
			'this-month' => DateTimeObj::get('m'),
			'this-day' => DateTimeObj::get('d'),
			'timezone' => DateTimeObj::get('P'),
			'website-name' => Symphony::Configuration()->get('sitename', 'general'),
			'root' => URL,
			'workspace' => URL . '/workspace'
		);
	}
	
	public function getAbout(){
		return $this->about;
	}
	
	public function getConfig(){
		return $this->config;
	}
	
	public function getName(){
		return $this->about['name'];
	}
	
	public function getHandle(){
		return strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $this->getName())));
	}
	
	protected function processDatasources(){
		if(is_null($this->_frontendPage)) $this->_frontendPage = new FrontendPage(Symphony::Engine());
		$xml = new XMLElement('data');
		$xml->setIncludeHeader(true);
		$this->_frontendPage->processDatasources(implode(', ',$this->config['datasources']), $xml);
		return $xml;
	}
	
	public function render($filter_template = false){
		if(!empty($this->config['datasources']) && !empty($this->config['templates'])){
			$result = Array();
			if(is_null($this->getXML())){
				try{
					$this->setXML($this->processDatasources()->generate(true, 0));
				}
				catch(Exception $e){
					throw new EmailTemplateException('Error including XML for rendering');
				}
			}
			foreach($this->config['templates'] as $type=>$template){
				$this->setXSL(dirname(__FILE__) . '/../templates/' . $this->getHandle() . '/' . $template, true);
				$res = $this->generate();
				if($res){
					$result[$type] = $res;
				}
				else{
					throw new EmailTemplateException('Error compiling xml to xslt');
				}
			}
			return $result;
		}
	}
	
	public function preview($template){
		$output = $this->render($template);
		$output = $output[$template];
		$devkit = null;
		Symphony::ExtensionManager()->notifyMembers(
			'FrontendDevKitResolve', '/frontend/',
			array(
				'full_generate'	=> true,
				'devkit'		=> &$devkit
			)
		);
		if (!is_null($devkit)) {
			$devkit->prepare($this, Array('filelocation'=>dirname(__FILE__) . '/../templates/' . $this->getHandle() . '/template.' . $template . '.xsl'), $this->_xml, $this->_param, $output);
			return $devkit->build();
		}
		return $output;
	}
}

Class EmailTemplateException extends Exception{
}