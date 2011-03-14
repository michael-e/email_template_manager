<?php

require_once(TOOLKIT . '/class.xsltpage.php');
require_once(TOOLKIT . '/class.frontendpage.php');
require_once(CORE . '/class.frontend.php');

Class EmailTemplate extends XSLTPage{
	
	public $subject = "";
	protected $_frontendPage;
	public $ExtensionManager;
	
	protected $config = Array();
	
	public function __construct(){
		parent::__construct();
		//needed for debug devkit.
		$this->ExtensionManager = Symphony::ExtensionManager();
		$this->addParams(array(
			'today' => DateTimeObj::get('Y-m-d'),
			'current-time' => DateTimeObj::get('H:i'),
			'this-year' => DateTimeObj::get('Y'),
			'this-month' => DateTimeObj::get('m'),
			'this-day' => DateTimeObj::get('d'),
			'timezone' => DateTimeObj::get('P'),
			'website-name' => Symphony::Configuration()->get('sitename', 'general'),
			'root' => URL,
			'workspace' => URL . '/workspace'
		));
	}
		
	public function addParams($params = array()){
		if(!is_array($params)) return false;
		return ($this->setRuntimeParam(array_merge($this->_param, $params)));
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
		$env = $this->_frontendPage->Env();
		foreach($env['pool'] as $name => $val){
			$tmp[$name] = implode(", ", (array)$val);
		}
		$this->addParams($tmp);
		return $xml;
	}
	
	public function render($layouts = Array('plain', 'html')){
		
		if(!is_array($layouts)){
			$layouts = Array($layouts);
		}
		if(isset($this->config['datasources']) && isset($this->config['layouts'])){
			$result = Array();
			
			if(is_null($this->getXML())){
				try{
					$this->setXML($this->processDatasources()->generate(true, 0));
				}
				catch(Exception $e){
					throw new EmailTemplateException('Error including XML for rendering');
				}
			}
			
			if(!empty($this->config['subject'])){
				// Basic {$variable} matching
				$search_strings = Array();
				foreach(array_keys($this->_param) as $param){
					$search_strings[] = '{$' . $param . '}';
				}
				$subject = str_replace($search_strings, $this->_param, $this->config['subject']);
				
				// More advanced XPATH matching. Using both is currently not supported.
				$replacements = array();
				preg_match_all('/\{[^\}]+\}/', $subject, $matches);
				if(is_array($matches[0])){
					$dom = new DOMDocument();
					$dom->strictErrorChecking = false;
					$dom->loadXML($this->getXML());
					$xpath = new DOMXPath($dom);
					foreach ($matches[0] as $match) {
						$results = @$xpath->evaluate('string(' . trim($match, '{}') . ')');
						if (!is_null($results)) {
							$replacements[$match] = trim($results);
						}
						else {
							$replacements[$match] = '';
						}
					}
				}
				$subject = str_replace(array_keys($replacements), array_values($replacements), $subject);
				
				$this->subject = $subject;
				$this->addParams(Array('subject'=>$subject));
				$result['subject'] = $subject;
			}
			
			foreach($this->config['layouts'] as $type=>$layout){
				if(in_array($type, $layouts)){
					$this->setXSL(dirname(EmailTemplateManager::find($this->getHandle())) . '/' . $layout, true);
					$res = $this->generate();
					if($res){
						$result[$type] = $res;
					}
					else{
						$error = $this->getError();
						throw new EmailTemplateException('Error compiling xml with xslt: ' . $error[1]['message']);
					}
					
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
			$devkit->prepare($this, Array('filelocation'=>dirname(EmailTemplateManager::find($this->getHandle())) . '/' . EmailTemplateManager::getFileNameFromLayout($template)), $this->_xml, $this->_param, $output);
			return $devkit->build();
		}
		return $output;
	}
}

Class EmailTemplateException extends Exception{
}