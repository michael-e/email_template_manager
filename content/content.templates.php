<?php

require_once(TOOLKIT . '/class.administrationpage.php');
require_once(EXTENSIONS . '/email_templates/lib/class.emailtemplate.php');
require_once(EXTENSIONS . '/email_templates/lib/class.emailtemplatemanager.php');

Class contentExtensionemail_templatestemplates extends AdministrationPage {
	
	function __construct(){
		parent::__construct(Symphony::Engine());
		$this->_uri = URL . '/symphony/extension/emailtemplates';
	}
	
	function __viewEdit(){
		$this->setPageType('table');
		$this->setTitle(__('Symphony &ndash; Email Templates &ndash; Edit'));
		try{
			$templates = EmailTemplateManager::load('testa');
		}
		catch(EmailTemplateManagerException $e){
			$this->pageAlert('An error occurred. ' . $e->getMessage());
			$this->__viewIndex();
		}
		//var_dump($templates);
	}
	
	function __viewIndex(){
		$this->setPageType('table');
		$this->setTitle(__('Symphony &ndash; Email Templates'));
		$this->appendSubheading(__('Email Templates'), Widget::Anchor(
			'Create New', "{$this->_uri}/templates/new/",
			'Create a new email template', 'create button'
		));
		
		$tableHead = array(
			array('Name', 'col'),
			array('Template', 'col'),
			array('Preview', 'col')
		);
		
		$templates = EmailTemplateManager::listAll();
		
		if(empty($templates)){
			$tableBody = array(
				Widget::TableRow(array(Widget::TableData(__('None Found.'), 'inactive', null, count($tableHead))))
			);
		}
		else{
			$i = 0;
			foreach($templates as $template){
				$col_name = Widget::TableData(
					Widget::Anchor(
						$template,
						"{$this->_uri}/templates/edit/{$template}/"
					)
				);
				$col_name->appendChild(Widget::Input("items[" . ++$i . "]", null, 'checkbox'));
				
				//$col_template = ;
				
				$col_preview = Widget::TableData(
					Widget::Anchor("Html","{$this->_uri}/templates/preview/{$template}/")->generate() . ', ' . Widget::Anchor("Text","{$this->_uri}/templates/preview/{$template}/")->generate()
				);
				
				$tableBody[] = Widget::TableRow(array($col_name, Widget::TableData(new XMLElement('a','test')), $col_preview), null);
			}
		}
		
		$table = Widget::Table(
			Widget::TableHead($tableHead), null,
			Widget::TableBody($tableBody), 
			'orderable, selectable'
		);
		
		$this->Form->appendChild($table);
		
		$actions = new XMLElement('div');
		$actions->setAttribute('class', 'actions');

		$options = array(
			array(null, false, 'With Selected...'),
			array('delete', false, 'Delete')
		);

		$actions->appendChild(Widget::Select('with-selected', $options));
		$actions->appendChild(Widget::Input('action[apply]', 'Apply', 'submit'));

		$this->Form->appendChild($actions); 
	}
	
	function __getclassName(){
		return 'contentExtensionemail_templateindex'; 
	}
}