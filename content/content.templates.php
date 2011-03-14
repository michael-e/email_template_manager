<?php

if(!defined('ETDIR')) define('ETDIR', EXTENSIONS . "/email_templates");
if(!defined('ETVIEWS')) define('ETVIEWS', ETDIR . "/content/templates");

require_once(ETDIR . '/lib/class.extensionpage.php');
require_once(EXTENSIONS . '/email_templates/lib/class.emailtemplate.php');
require_once(EXTENSIONS . '/email_templates/lib/class.emailtemplatemanager.php');
require_once(TOOLKIT . '/class.datasourcemanager.php');

Class contentExtensionemail_templatestemplates extends ExtensionPage {
	
	protected $_type;
	protected $_function;
	
	protected $_XSLTProc;
	protected $_XML;
	
	function __construct(){
		$this->_XSLTProc = new XsltProcess();
		$this->_XML = new XMLElement("data");
		parent::__construct(Symphony::Engine());
	}
	
	function __actionNew(){
		$fields = $_POST['fields'];
		
		if(!$this->_validateConfig($fields, false, true)){
			$this->_XML->appendChild($this->_validateConfig($fields, true, true));
			$this->pageAlert(
				__('Could not save, please correct errors below'),
				Alert::ERROR
			);
		}
		else{
			if(EmailTemplateManager::create($fields)){
				redirect(SYMPHONY_URL . '/extension/email_templates/templates/edit/' . EmailTemplateManager::getHandleFromName($fields['name']) . '/saved');
			}
			else{
				$this->pageAlert(
					__('Could not save: ' . EmailTemplateManager::$errorMsg),
				Alert::ERROR
				);
			}
		}
	}
	
	function __actionEdit(){
		$fields = $_POST['fields'];
		
		if(isset($_POST['action']['delete'])){
			if(EmailTemplateManager::delete($this->_context[1])){
				redirect(SYMPHONY_URL . '/extension/email_templates/templates/');
			}
			else{
				$this->pageAlert(
					__('Could not delete: ' . EmailTemplateManager::$errorMsg),
					Alert::ERROR
				);
				return;
			}
		}
		
		else{
		
			// Config editing
			if(empty($this->_context[2]) || ($this->_context[2] == 'saved')){
				
				if(!$this->_validateConfig($fields)){
					$this->_XML->appendChild($this->_validateConfig($fields, true, true));
					$this->pageAlert(
						__('Could not save, please correct errors below'),
						Alert::ERROR
					);
				}
				
				if(EmailTemplateManager::editConfig($this->_context[1], $fields)){
					redirect(SYMPHONY_URL . '/extension/email_templates/templates/edit/' . EmailTemplateManager::getHandleFromName($fields['name']) . '/saved');
				}
				else{
					$this->pageAlert(
						__('Could not save: ') . __(EmailTemplateManager::$errorMsg),
						Alert::ERROR
					);
				}
			}
			
			// Layout editing
			else{
				$errors = new XMLElement('errors');
				if(!isset($fields['body']) || trim($fields['body']) == '') {
					$errors->appendChild(new XMLElement('body', 'Body is a required field'));
				}
				elseif(!General::validateXML($fields['body'], $error, false, new XSLTProcess())) {
					$errors->appendChild(new XMLElement('body', __('This document is not well formed. The following error was returned: <code>%s</code>', array($error[0]['message']))));
				}
				elseif(EmailTemplateManager::editLayout($this->_context[1],$this->_context[2], $fields['body'])){
					redirect(SYMPHONY_URL . '/extension/email_templates/templates/edit/' . $this->_context[1] . '/' . $this->_context[2] . '/saved');
				}
				else{
					$this->pageAlert(
						__('Could not save: ') .  __(EmailTemplateManager::$errorMsg),
						Alert::ERROR
					);
				}
				$this->_XML->appendChild($errors);
			}
		}
	}
	
	function __actionIndex(){
		if($_POST['with-selected'] == 'delete'){
			foreach((array)$_POST['items'] as $item=>$status){
				if(!EmailTemplateManager::delete($item)){
					$this->pageAlert(
						__('Could not delete: ') .  __(EmailTemplateManager::$errorMsg),
						Alert::ERROR
					);
				}
			}
		}
	}

	function __viewIndex(){
		$this->setPageType('index');
		$this->setTitle(__("Symphony - Email Templates"));
		
		$templates = new XMLElement("templates");
		foreach(EmailTemplateManager::listAll() as $template){
			$entry = new XMLElement("entry");
			General::array_to_xml($entry, $template->about);
			General::array_to_xml($entry, $template->config);
			$entry->appendChild(new XMLElement("handle", $template->getHandle()));
			$templates->appendChild($entry);
		}
		$this->_XML->appendChild($templates);
	}
	
	function __viewEdit($new = false){
		$this->setPageType('form');
		$this->setTitle(sprintf(__("Symphony - Email Templates - %s", Array(), false), ucfirst($this->_context[1])));
		
		if($this->_context[2] == 'saved' || $this->_context[3] == 'saved'){
			$this->pageAlert(
				__(
					'Template updated at %1$s.', 
					array(
						DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__), 
					)
				),
				Alert::SUCCESS
			);
		}
		
		// Edit config
		if(empty($this->_context[2]) || ($this->_context[2] == 'saved')){
			$templates = new XMLElement("templates");
			$template = EmailTemplateManager::load($this->_context[1]);
			if($template){
				$entry = new XMLElement("entry");
				General::array_to_xml($entry, $template->about);
				General::array_to_xml($entry, $template->config);
				$entry->appendChild(new XMLElement("handle", $template->getHandle()));
				$templates->appendChild($entry);
			}
			elseif(!$new){
				throw new FrontendPageNotFoundException();
			}
			$this->_XML->appendChild($templates);
			
			$datasources = new XMLElement("datasources");
			$dsmanager = new DatasourceManager($this);
			foreach($dsmanager->listAll() as $datasource){
				$entry = new XMLElement("entry");
				General::array_to_xml($entry, $datasource);
				$datasources->appendChild($entry);
			}
			$this->_XML->appendChild($datasources);
		}
		
		// Edit layout
		else{
			$this->_useTemplate = 'viewEditLayout';
			$utils = General::listStructure(UTILITIES, array('xsl'), false, 'asc', UTILITIES);
			$utils = (array)$utils['filelist'];
			
			$templates = new XMLElement("templates");
			$template = EmailTemplateManager::load($this->_context[1]);
			if($template){
				$entry = new XMLElement("entry");
				General::array_to_xml($entry, $template->about);
				General::array_to_xml($entry, $template->config);
				$entry->appendChild(new XMLElement("handle", $template->getHandle()));
				$templates->appendChild($entry);
			}
			elseif(!$new){
				throw new FrontendPageNotFoundException();
			}
			$this->_XML->appendChild($templates);
			
			$utilities = new XMLElement('utilities');
			General::array_to_xml($utilities, $utils);
			$this->_XML->appendChild($utilities);
			
			$template = EmailTemplateManager::load($this->_context[1]);
			if($template){
				if($template->config['layouts'][$this->_context[2]]){
					$layout = new XMLElement('layout', '<![CDATA[' . file_get_contents(dirname(EmailTemplateManager::find($this->_context[1])) . '/' . $template->config['layouts'][$this->_context[2]]) . ']]>');
					$this->_XML->appendChild($layout);
				}
				else{
					throw new FrontendPageNotFoundException();
				}
			}
			else{
				throw new FrontendPageNotFoundException();
			}
		}		
	}
	
	function __viewNew(){
		$this->_context[1] = 'New';
		$this->_useTemplate = 'viewEdit';
		$this->__viewEdit(true);
	}
	
	public function __viewPreview(){
		$this->_useTemplate = false;
		list(,$handle, $template) = $this->_context;
		$templates = EmailTemplateManager::load($handle);
		echo $templates->preview($template);
		exit;
	}
	
	function view(){
		$context = new XMLElement('context');
		General::array_to_xml($context, $this->_context);
		$this->_XML->appendChild($context);
		parent::view();
	}
	
	function action(){
		if($this->_context[2] == 'saved'){
			$this->_context[2] = null;
		}
		$fields = new XMLElement('fields');
		General::array_to_xml($fields, (array)$_POST['fields']);
		$this->_XML->appendChild($fields);
		parent::action();
	}
	
	function build(Array $context = array()){
		$this->addScriptToHead(URL . '/extensions/email_templates/assets/admin.js', 70);
		parent::build($context);
	}
	
	protected function _validateConfig($config, $as_xml = false, $unique_name = false){
		$errors = new XMLElement('errors');
		if(!empty($config['name'])){
			if($unique_name && EmailTemplateManager::find(EmailTemplateManager::getHandleFromName($config['name']))){
				$errors->appendChild(new XMLElement('name', 'A template with this name already exists.'));
				if(!$as_xml) return false;
			}
		}
		else{
			$errors->appendChild(new XMLElement('name', 'This field can not be empty'));
			if(!$as_xml) return false;
		}
		if(empty($config['subject'])){
			$errors->appendChild(new XMLElement('subject', 'This field can not be empty'));
			if(!$as_xml) return false;
		}
		
		return $errors;
	}
}