<?php

if(!defined('ETMDIR')) define('ETMDIR', EXTENSIONS . "/email_template_manager");
if(!defined('ETVIEWS')) define('ETVIEWS', ETMDIR . "/content/templates");

if(!class_exists('ExtensionPage')){
	require_once(ETMDIR . '/lib/class.extensionpage.php');
}
require_once(ETMDIR . '/lib/class.emailtemplate.php');
require_once(ETMDIR . '/lib/class.emailtemplatemanager.php');
require_once(TOOLKIT . '/class.datasourcemanager.php');
require_once(TOOLKIT . '/class.emailgatewaymanager.php');

Class contentExtensionemail_template_managertemplates extends ExtensionPage {

	protected $_type;
	protected $_function;

	protected $_XML;

	function __construct(){
		$this->_XML = new XMLElement("data");
		parent::__construct(Symphony::Engine());
		$this->viewDir = ETVIEWS;
	}

	function __actionNew(){
		$fields = $_POST['fields'];

		if(!$this->_validateConfig($fields, false, true)){
			$this->_XML->appendChild($this->_validateConfig($fields, true, true));
			$this->pageAlert(
				__('Could not save. Please correct errors below.'),
				Alert::ERROR
			);
		}
		else{
			if($fields['layouts'] == 'both'){
				unset($fields['layouts']);
			}
			if($fields['layouts'] == 'html'){
				$fields['layouts'] = Array('html'=>'template.html.xsl');
			}
			if($fields['layouts'] == 'plain'){
				$fields['layouts'] = Array('plain'=>'template.plain.xsl');
			}
			if(EmailTemplateManager::create($fields)){
				redirect(SYMPHONY_URL . '/extension/email_template_manager/templates/edit/' . EmailTemplateManager::getHandleFromName($fields['name']) . '/saved');
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
				redirect(SYMPHONY_URL . '/extension/email_template_manager/templates/');
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
						__('Could not save. Please correct errors below.'),
						Alert::ERROR
					);
				}

				if($fields['layouts'] == 'both'){
					unset($fields['layouts']);
				}
				if($fields['layouts'] == 'html'){
					$fields['layouts'] = Array('html'=>'template.html.xsl');
				}
				if($fields['layouts'] == 'plain'){
					$fields['layouts'] = Array('plain'=>'template.plain.xsl');
				}

				if(EmailTemplateManager::editConfig($this->_context[1], $fields)){
					redirect(SYMPHONY_URL . '/extension/email_template_manager/templates/edit/' . EmailTemplateManager::getHandleFromName($fields['name']) . '/saved');
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
					$errors->appendChild(new XMLElement('body', __('Body is a required field')));
				}
				elseif(!General::validateXML($fields['body'], $error, false, new XSLTProcess())) {
					$errors->appendChild(new XMLElement('body', __('This document is not well formed. The following error was returned: <code>%s</code>', array($error[0]['message']))));
				}
				elseif(EmailTemplateManager::editLayout($this->_context[1],$this->_context[2], $fields['body'])){
					redirect(SYMPHONY_URL . '/extension/email_template_manager/templates/edit/' . $this->_context[1] . '/' . $this->_context[2] . '/saved');
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

		$this->appendSubheading(__('Email Templates'), Widget::Anchor(
			__('Create New'), SYMPHONY_URL . '/extension/email_template_manager/templates/new/',
			__('Create a new email template'), 'create button'
		));

		$templates = new XMLElement("templates");
		foreach(EmailTemplateManager::listAll() as $template){
			$entry = new XMLElement("entry");
			General::array_to_xml($entry, $template->about);
			General::array_to_xml($entry, $template->getProperties());
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
					__('Template updated at %1$s.'),
					array(
						DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__),
					)
				),
				Alert::SUCCESS
			);
		}

		// Default page context
		$title = __('New Template');
		$buttons = array();
		$breadcrumbs = array(
			Widget::Anchor(__('Email Templates'), SYMPHONY_URL . '/extension/email_template_manager/templates/')
		);

		// Edit config
		if(empty($this->_context[2]) || ($this->_context[2] == 'saved')){
			$templates = new XMLElement("templates");
			$template = EmailTemplateManager::load($this->_context[1]);
			if($template){
				$properties = $template->getProperties();
				$title = $template->about['name'];			
				$entry = new XMLElement("entry");
				General::array_to_xml($entry, $template->about);
				General::array_to_xml($entry, $properties);
				$entry->appendChild(new XMLElement("handle", $template->getHandle()));
				$templates->appendChild($entry);
				
				// Create layout buttons
				$properties = $template->getProperties();
				foreach($properties['layouts'] as $layout => $file) {
					$buttons[] = Widget::Anchor(
						__('Edit %s layout', array($layout)), SYMPHONY_URL . '/extension/email_template_manager/templates/edit/' . $template->getHandle() . '/' . $layout,
						__('Edit %s layout', array($layout)), 'button'
					);
				}
			}
			elseif(!$new){
				Administration::instance()->errorPageNotFound();
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
			General::array_to_xml($this->_XML, Array("email-settings" => Symphony::Configuration()->get('email_' . EmailGatewayManager::getDefaultGateway())));
		}

		// Edit layout
		else{
			$this->_useTemplate = 'viewEditLayout';
			$utils = General::listStructure(UTILITIES, array('xsl'), false, 'asc', UTILITIES);
			$utils = (array)$utils['filelist'];

			$templates = new XMLElement("templates");
			$template = EmailTemplateManager::load($this->_context[1]);
			if($template){
				$properties = $template->getProperties();
				$title = ($this->_context[2] == 'plain' ? __('Plain') : __('HTML'));			
				$entry = new XMLElement("entry");
				General::array_to_xml($entry, $template->about);
				General::array_to_xml($entry, $properties);
				$entry->appendChild(new XMLElement("handle", $template->getHandle()));
				$templates->appendChild($entry);

				// Add template to breadcrumbs
				$breadcrumbs[] = Widget::Anchor($template->about['name'], SYMPHONY_URL . '/extension/email_template_manager/templates/edit/' . $template->getHandle());
				
				// Create layout buttons
				foreach($properties['layouts'] as $layout => $file) {
					if($layout == $this->_context[2]) {
						$buttons[] = Widget::Anchor(
							__('Preview %s layout', array($layout)), SYMPHONY_URL . '/extension/email_template_manager/templates/preview/' . $template->getHandle() . '/' . $layout,
							__('Preview %s layout', array($layout)), 'button'
						);
					}
					else {
						$buttons[] = Widget::Anchor(
							__('Edit %s layout', array($layout)), SYMPHONY_URL . '/extension/email_template_manager/templates/edit/' . $template->getHandle() . '/' . $layout,
							__('Edit %s layout', array($layout)), 'button'
						);
					}
				}
			}
			elseif(!$new){
				Administration::instance()->errorPageNotFound();
			}
			$this->_XML->appendChild($templates);

			$utilities = new XMLElement('utilities');
			General::array_to_xml($utilities, $utils);
			$this->_XML->appendChild($utilities);

			$template = EmailTemplateManager::load($this->_context[1]);
			if($template){
				if($template->layouts[$this->_context[2]]){
					$layout = new XMLElement('layout', '<![CDATA[' . file_get_contents(dirname(EmailTemplateManager::find($this->_context[1])) . '/' . $template->layouts[$this->_context[2]]) . ']]>');
					$this->_XML->appendChild($layout);
				}
				else{
					Administration::instance()->errorPageNotFound();
				}
			}
			else{
				Administration::instance()->errorPageNotFound();
			}
		}

		// Add page context
		$this->appendSubheading($title, $buttons);
		$this->insertBreadcrumbs($breadcrumbs);
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
		$output =  $templates->preview($template);
		if($template == 'plain' && !isset($_REQUEST['debug']) && !isset($_REQUEST['profile'])){
			header('Content-Type:text/plain; charset=utf-8');
		}
		echo $output;
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
		$this->addScriptToHead(URL . '/extensions/email_template_manager/assets/admin.js', 140);
		parent::build($context);
	}

	protected function _validateConfig($config, $as_xml = false, $unique_name = false){
		$errors = new XMLElement('errors');
		if(!empty($config['name'])){
			if($unique_name && EmailTemplateManager::find(EmailTemplateManager::getHandleFromName($config['name']))){
				$errors->appendChild(new XMLElement('name', __('A template with this name already exists.')));
				if(!$as_xml) return false;
			}
		}
		else{
			$errors->appendChild(new XMLElement('name', __('This field can not be empty')));
			if(!$as_xml) return false;
		}
		if(empty($config['subject'])){
			$errors->appendChild(new XMLElement('subject', __('This field can not be empty')));
			if(!$as_xml) return false;
		}

		return $errors;
	}
}