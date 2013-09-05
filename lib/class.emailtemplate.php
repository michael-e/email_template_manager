<?php

require_once(TOOLKIT . '/class.xsltpage.php');
require_once(TOOLKIT . '/class.frontendpage.php');
require_once(CORE . '/class.frontend.php');

Class EmailTemplate extends XSLTPage{

	public $subject = "";
	public $reply_to_name;
	public $reply_to_email_address;
	public $recipients;

	public $datasources = Array();
	public $layouts = Array();

	protected $_parsedProperties = Array();
	protected $_frontendPage;

	public function __construct(){
		parent::__construct();
		//needed for debug devkit.
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

	public function getName(){
		return $this->about['name'];
	}

	public function getHandle(){
		return strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '', str_replace(' ', '-', $this->getName())));
	}

	public function processDatasources(){
		if(is_null($this->_frontendPage)) $this->_frontendPage = new FrontendPage(Symphony::Engine());

		$this->_frontendPage->_param = $this->_param;

		$xml = new XMLElement('data');
		$xml->setIncludeHeader(true);
		$this->_frontendPage->processDatasources(implode(', ',$this->datasources), $xml);
		$env = $this->_frontendPage->Env();
		foreach((array)$env['pool'] as $name => $val){
			$tmp[$name] = implode(", ", (array)$val);
		}
		$this->addParams($tmp);
		return $xml;
	}

	public function evalXPath($xpath_string, $multiple = false){
		$dom = new DOMDocument();
		$dom->strictErrorChecking = false;
		$dom->loadXML($this->getXML());
		$xpath = new DOMXPath($dom);
		if($multiple == true){
			$xpath_strings = explode(",", $xpath_string);
			foreach(array_keys($this->_param) as $param){
				$search_strings[] = '{$' . $param . '}';
			}
			$ret = Array();
			foreach($xpath_strings as $xpath_string){
				$xpath_string = trim($xpath_string);
				$str = str_replace($search_strings, $this->_param, $xpath_string);
				$replacements = array();
				preg_match_all('/\{[^\}\$]+\}/', $str, $matches);
				$str = Array($str);
				if(is_array($matches[0]) && !empty($matches[0])){
					foreach($matches[0] as $match){
						$results = @$xpath->evaluate(trim($match, '{}'));
						if(is_object($results)){
							if($results->length > 0){
								if(count($str) == 1){
									$str = array_fill(0, $results->length, $str[0]);
								}
								if(count($str) == $results->length){
									foreach($results as $offset=>$result){
										$str[$offset] = str_replace($match, trim($result->textContent), $str[$offset]);
									}
								}
								else{
									throw new EmailTemplateException("XPath matching failed. Number of returned values in queries do not match");
								}
							}
							elseif($results->length <= 0){
								foreach($str as $offset=>$val){
									$str[$offset] = '';
								}
								Symphony::Log()->pushToLog(__('Email Template Manager') . ': ' . ' Xpath query '.$match.' did not return any results, skipping. ', 100, true);
							}
						}
						else{
							if(empty($results)){
								$results = '';
							}
							foreach($str as $offset=>$val){
								$str[$offset] = str_replace($match, trim($results), $str[$offset]);
							}
						}
					}
				}
				$ret = array_merge($ret, $str);
			}
			return $ret;
		}
		else{
			$search_strings = Array();
			foreach(array_keys($this->_param) as $param){
				$search_strings[] = '{$' . $param . '}';
			}
			$str = str_replace($search_strings, $this->_param, $xpath_string);
			$replacements = array();
			preg_match_all('/\{[^\}\$]+\}/', $str, $matches);
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
				return str_replace(array_keys($replacements), array_values($replacements), $str);
			}
		}
	}

	public function render($layouts = Array('plain', 'html')){

		if(!is_array($layouts)){
			$layouts = Array($layouts);
		}
		if(isset($this->datasources) && isset($this->layouts)){
			$result = Array();

			if(is_array($_GET) && !empty($_GET)){
				foreach($_GET as $key => $val){
					if(!in_array($key, array('symphony-page', 'debug', 'profile'))) $this->_param['url-' . $key] = $val;
				}
			}

			if(is_null($this->getXML())){
				try{
					$this->setXML($this->processDatasources()->generate(true, 0));
				}
				catch(Exception $e){
					$error = $this->getError();
					throw new EmailTemplateException('Error including XML for rendering: ' . $e->getMessage());
				}
			}

			$this->parseProperties();
			$properties = $this->getParsedProperties();

			foreach($this->layouts as $type=>$layout){
				if(in_array(strtolower($type), array_map("strtolower", $layouts))){
					$xsl = '<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:import href="./workspace/email-templates/' . $this->getHandle() . '/' . $layout.'"/>
</xsl:stylesheet>';
					$this->setXSL($xsl, false);
					$res = $this->generate();
					if($res){
						$result[strtolower($type)] = $res;
					}
					else{
						$error = $this->getError();
						throw new EmailTemplateException('Error compiling xml with xslt: ' . $error[1]['message']);
					}

				}
			}
			return array_merge($result, $properties);
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
			$devkit->prepare($this, Array('title' => $this->getName(), 'filelocation'=>dirname(EmailTemplateManager::find($this->getHandle())) . '/' . EmailTemplateManager::getFileNameFromLayout($template)), $this->_xml, $this->_param, $output);
			return $devkit->build();
		}
		return $output;
	}

	public function __set($var, $val){
		if(property_exists($this, $var)){
			if(is_public($this->$var)){
				$this->$var = $val;
				unset($this->_parsedProperties[$var]);
			}
		}
	}

	public function parseProperties(){
		if(empty($this->_parsedProperties['recipients'])){
			$recipients = $this->evalXPath($this->recipients, true);
			foreach($recipients as $recipient){
				if(strlen($recipient) > 0){
					if(strpos($recipient, '@') !== false){
						// NAME <email@domain>
						if((($start = strpos($recipient, "<")) !== false) && (($stop = strpos($recipient, ">")) !== false)){
							$name = trim(substr($recipient, 0, $start), '"< ');
							if(strlen($name) == 0){
								$name = count((array)$rcpts);
							}
							$rcpts[trim($name)] = trim(substr($recipient, $start+1, $stop - ($start+1)));
						}
						// email@domain
						else{
							$rcpts[] = trim($recipient);
						}
					}
					// username
					else{
						$author = AuthorManager::fetchByUserName(trim($recipient));
						if(is_a($author, 'Author')){
							$rcpts[trim($author->get('first_name') . ' '. $author->get('last_name'))] = $author->get("email");
						}
						else{
							Symphony::Log()->pushToLog(__('Email Template Manager') . ': ' . ' Recipient is recognised as a username, but the user can not be found: ' . $recipient , 100, true);
						}
					}
				}
				else{
					Symphony::Log()->pushToLog(__('Email Template Manager') . ': ' . ' Recipient is empty, skipping.' , 100, true);
				}
			}
			if(!empty($rcpts)){
				$this->_parsedProperties['recipients'] = $rcpts;
			}
			else{
				Symphony::Log()->pushToLog(__('Email Template Manager') . ': ' . ' No valid recipients are selected, can not send emails.' , 100, true);
				return false;
			}
		}

		if(empty($this->_parsedProperties['subject'])){
			$this->_parsedProperties['subject'] = $this->evalXPath($this->subject, false);
			//$this->addParams(Array('etm-subject'=>$this->_parsedProperties['subject']));
		}

		if(empty($this->_parsedProperties['reply-to-name'])){
			$this->_parsedProperties['reply-to-name'] = $this->evalXPath($this->reply_to_name, false);
			//$this->addParams(Array('etm-reply-to-name'=>$this->_parsedProperties['reply-to-name']));
		}

		if(empty($this->_parsedProperties['reply-to-email-address'])){
			$this->_parsedProperties['reply-to-email-address'] = $this->evalXPath($this->reply_to_email_address, false);
			//$this->addParams(Array('etm-reply-to-email-address'=>$this->_parsedProperties['reply-to-email-address']));
		}
	}

	public function getParsedProperties(){
		return $this->_parsedProperties;
	}

	public function getProperties(){
		return Array(
			'reply-to-name' => $this->reply_to_name,
			'reply-to-email-address' => $this->reply_to_email_address,
			'subject' => $this->subject,
			'recipients' => $this->recipients,
			'datasources' => $this->datasources,
			'layouts' => $this->layouts
		);
	}

	public function setXML($xml){
		$this->_parsedProperties = Array();
		return parent::setXML($xml);
	}
}

Class EmailTemplateException extends Exception{
}
