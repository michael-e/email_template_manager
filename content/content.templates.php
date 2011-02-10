<?php

require_once(TOOLKIT . '/class.administrationpage.php');
require_once(EXTENSIONS . '/email_templates/lib/class.emailtemplate.php');
require_once(EXTENSIONS . '/email_templates/lib/class.emailtemplatemanager.php');

Class contentExtensionemail_templatestemplates extends AdministrationPage {
	
	function __construct(){
		parent::__construct(Symphony::Engine());
		$this->_uri = URL . '/symphony/extension/email_templates';
	}
	
	public function __actionIndex(){
		if($_POST['with-selected'] == 'delete'){
			$this->_delete($_POST['items']);
		}
	}
	
	public function __actionNew(){
		$this->_context[0] = 'new';
		$this->__actionEdit();
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
						$template->getName(),
						"{$this->_uri}/templates/edit/{$template->getHandle()}/"
					)
				);
				$col_name->appendChild(Widget::Input("items[" . $template->getHandle() . "]", null, 'checkbox'));
				
				$tmp = Array();
				$prv = Array();
				$config = $template->getConfig();
				if(is_array($config['templates'])){
					foreach($config['templates'] as $name=>$tmpl){
						if(file_exists(dirname(__FILE__) . '/../templates/' . $template->getHandle() . '/' . $tmpl)){
							$tmp[] = Widget::Anchor(ucfirst(strtolower($name)), "{$this->_uri}/templates/edit/{$template->getHandle()}/{$name}")->generate();
							$prv[] = Widget::Anchor(ucfirst(strtolower($name)), "{$this->_uri}/templates/preview/{$template->getHandle()}/{$name}/")->generate();
						}
					}
					$col_template = Widget::TableData(implode(', ',$tmp));
					$col_preview = Widget::TableData(implode(', ',$prv));
				}
				if(empty($tmp)){
					$col_template = Widget::TableData(__('None Found'), 'inactive');
				}
				if(empty($prv)){
					$col_preview = Widget::TableData(__('None Found'), 'inactive');
				}
				
				$tableBody[] = Widget::TableRow(array($col_name, $col_template, $col_preview), null);
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
	
	function __viewNew(){
		$this->__viewEdit();
	}
	
	function __viewEdit(){
		if($this->_context[3] == 'saved' || $this->_context[2] == 'saved') {
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
		list(,$handle, $template) = $this->_context;
		if(!is_null($template) && $template != 'saved'){
			$this->_editXSL();
		}
		else{
			$this->_editClass();
		}
	}
	
	public function __actionEdit() {
		list($mode,$handle, $template) = $this->_context;
		
		if(isset($_POST['action']['delete'])){
			if($this->_delete($handle)){
				redirect($this->_uri. '/templates/');
			}
			else{
				return;
			}
		}
		
		
		
		//Edit Template xsl Files
		if(!is_null($template)){
			$filename = 'template.' . $template . '.xsl';
			$folder_name = $handle;
			$file_abs = dirname(__FILE__) . '/../templates/' . basename($folder_name) . '/' . basename($filename);
			$fields = $_POST['fields'];
			$this->_errors = array();

			if(!isset($fields['body']) || trim($fields['body']) == '') {
				$this->_errors['body'] = __('Body is a required field.');

			} elseif(!General::validateXML($fields['body'], $errors, false, new XSLTProcess())) {
				$this->_errors['body'] = __('This document is not well formed. The following error was returned: <code>%s</code>', array($errors[0]['message']));
			}

			if(empty($this->_errors)) {
				if(!$write = General::writeFile($file_abs, $fields['body'], Symphony::Configuration()->get('write_mode', 'file'))) {
					$this->pageAlert(__('Template could not be written to disk. Please check permissions on <code>/extensions/email_templates/templates/</code>.'), Alert::ERROR);

				} else {
					redirect($this->_uri . '/templates/edit/' . $handle . '/' . $template  . '/saved/');
				}
			}
		}
		
		//Edit Template Configuration Files
		else{
		
			$fields = $_POST['fields'];
			$tpl_file = dirname(__FILE__) . '/templates/class.tpl';
			
			// new entry
			if(!is_null($handle)){
				$templ = null;
				try{
					$templ = EmailTemplateManager::load($handle);
					if($templ->config['editable'] != true){
						throw new FrontendPageNotFoundException();
						// do something fancy here, like redirect to the info page
					}
				}
				catch(EmailTemplateManagerException $e){
					throw new FrontendPageNotFoundException();
				}
			}
			
			$tpl = @file_get_contents($tpl_file);
			//TODO: sanitation
			$about = Array(
				'name' => $fields['name'],
				'author name' => Administration::instance()->Author->getFullName(),
				'author email' => Administration::instance()->Author->get('email'),
				'author website' => URL,
				'release date' => DateTimeObj::getGMT('c'),
				'version' => '1.0'
			);
			$datasources = null;
			foreach((Array)$fields['data_sources'] as $ds){
				$datasources .= "\r\n \t\t\t'$ds',";
			}
			$config = Array(
				'datasources' => $datasources,
				'templates'	  => "'html' => 'template.html.xsl',\r\n\t\t\t'text' => 'template.text.xsl'"
			);
			
			$tpl = str_replace('<!-- CLASS NAME -->', ucfirst(EmailTemplateManager::getHandleFromName($fields['name'])), $tpl);
			$tpl = str_replace('<!-- NAME -->', $about['name'], $tpl);
			$tpl = str_replace('<!-- AUTHOR NAME -->', $about['author name'], $tpl);
			$tpl = str_replace('<!-- AUTHOR EMAIL -->', $about['author email'], $tpl);
			$tpl = str_replace('<!-- AUTHOR WEBSITE -->', $about['author website'], $tpl);
			$tpl = str_replace('<!-- RELEASE DATE -->', $about['release date'], $tpl);
			$tpl = str_replace('<!-- VERSION -->', $about['version'], $tpl);
			$tpl = str_replace('<!-- DATASOURCES -->', $config['datasources'], $tpl);
			$tpl = str_replace('<!-- TEMPLATES -->', $config['templates'], $tpl);
			
			$filename = EmailTemplateManager::getFileNameFromHandle(EmailTemplateManager::getHandleFromName($fields['name']));
			if(!is_dir(dirname(__FILE__) . '/../templates/' . EmailTemplateManager::getHandleFromName($fields['name']))){
				if(is_dir(dirname(__FILE__) . '/../templates/' . EmailTemplateManager::getHandleFromName($handle)) && !empty($handle)){
					@rename(dirname(__FILE__) . '/../templates/' . EmailTemplateManager::getHandleFromName($handle), dirname(__FILE__) . '/../templates/' . EmailTemplateManager::getHandleFromName($fields['name']));
					@unlink(dirname(__FILE__) . '/../templates/' . EmailTemplateManager::getHandleFromName($fields['name']) . '/' . EmailTemplateManager::getFileNameFromHandle(EmailTemplateManager::getHandleFromName($handle)));
				}
				else{
					mkdir(dirname(__FILE__) . '/../templates/' . EmailTemplateManager::getHandleFromName($fields['name']));
				}
			}
			$file = dirname(__FILE__) . '/../templates/' . EmailTemplateManager::getHandleFromName($fields['name']) . '/' . $filename;
			
			if(!is_writable(dirname($file)) || !$write = General::writeFile($file, $tpl, Symphony::Configuration()->get('write_mode', 'file')))
				$this->pageAlert(__('Failed to write Template to <code>%s</code>. Please check permissions.', array(dirname(dirname(__FILE__) . '/../templates/'))), Alert::ERROR);

			if($mode == 'new'){
				$html = file_get_contents(dirname(__FILE__) . '/templates/xsl-html.tpl');
				$text = file_get_contents(dirname(__FILE__) . '/templates/xsl-text.tpl');
				
				$html_file = dirname(__FILE__) . '/../templates/' . EmailTemplateManager::getHandleFromName($fields['name']) . '/template.html.xsl';
				$text_file = dirname(__FILE__) . '/../templates/' . EmailTemplateManager::getHandleFromName($fields['name']) . '/template.text.xsl';
				
				if(!is_writable(dirname($html_file)) || !$write = General::writeFile($html_file, $html, Symphony::Configuration()->get('write_mode', 'file')))
					$this->pageAlert(__('Failed to write HTML Template to <code>%s</code>. Please check permissions.', array($html_file), Alert::ERROR));
				if(!is_writable(dirname($text_file)) || !$write = General::writeFile($text_file, $text, Symphony::Configuration()->get('write_mode', 'file')))
					$this->pageAlert(__('Failed to write HTML Template to <code>%s</code>. Please check permissions.', array($text_file), Alert::ERROR));
			}
			
			redirect($this->_uri . '/templates/edit/' . EmailTemplateManager::getHandleFromName($fields['name']) . '/saved/');
		}
	}
	
	public function __viewPreview(){
		list(,$handle, $template) = $this->_context;
		$templates = EmailTemplateManager::load($handle);
		echo $templates->preview($template);
		die();
	}
		
	protected function _editXSL(){
		list(,$handle, $template) = $this->_context;
		if(!empty($handle)){
			$this->setPageType('form');
			$this->Form->setAttribute('action', $this->_uri . '/templates/edit/' . $handle . '/' . $template . '/');
			$this->setTitle(__('Symphony &ndash; Email Templates &ndash; Edit'));
			try{
				$tmpl = EmailTemplateManager::load($handle);
				
				$config = $tmpl->getConfig();
				if(!is_null($config['templates'][$template])){
					
					$file_abs = dirname(__FILE__) . '/../templates/' . $handle . '/' . basename($config['templates'][$template]);
					
					if(!is_file($file_abs)) redirect($this->_uri . '/templates/');
					$fields['body'] = @file_get_contents($file_abs);
					
					$this->appendSubheading(__($config['templates'][$template] ? $config['templates'][$template] : __('Untitled')), Widget::Anchor(__('Edit Configuration'), $this->_uri . '/templates/edit/' . $handle, __('Edit Template Confguration'), 'button', NULL, array('accesskey' => 't')));
					
					if(!empty($_POST)) $fields = $_POST['fields'];

					$fields['body'] = General::sanitize($fields['body']);

					$fieldset = new XMLElement('fieldset');
					$fieldset->setAttribute('class', 'primary');

					$label = Widget::Label(__('Body'));
					$label->appendChild(Widget::Textarea(
						'fields[body]', 30, 80, $fields['body'],
						array(
							'class'	=> 'code'
						)
					));

					if(isset($this->_errors['body'])) {
						$label = $this->wrapFormElementWithError($label, $this->_errors['body']);
					}
					
					$utilities = General::listStructure(UTILITIES, array('xsl'), false, 'asc', UTILITIES);
					$utilities = $utilities['filelist'];

					if(is_array($utilities) && !empty($utilities)) {
						$div = new XMLElement('div');
						$div->setAttribute('class', 'secondary');

						$p = new XMLElement('p', __('Utilities'));
						$p->setAttribute('class', 'label');
						$div->appendChild($p);

						$ul = new XMLElement('ul');
						$ul->setAttribute('id', 'utilities');

						foreach ($utilities as $index => $util) {
							$li = new XMLElement('li');

							if($index % 2 != 1) $li->setAttribute('class', 'odd');

							$li->appendChild(Widget::Anchor($util, SYMPHONY_URL . '/blueprints/utilities/edit/' . str_replace('.xsl', '', $util) . '/', NULL));
							$ul->appendChild($li);
						}

						$div->appendChild($ul);
						$this->Form->appendChild($div);
					}

					$fieldset->appendChild($label);
					$this->Form->appendChild($fieldset);
					$div = new XMLElement('div');
					$div->setAttribute('class', 'actions');
					$div->appendChild(Widget::Input(
						'action[save_t]', __('Save Changes'),
						'submit', array('accesskey' => 's')
					));

					$this->Form->appendChild($div);
				}
				else{
					throw new FrontendPageNotFoundException();
				}				
			}
			catch(EmailTemplateManagerException $e){
				throw new FrontendPageNotFoundException();
			}
		}
		else{
			throw new FrontendPageNotFoundException();
		}
	}
	
	protected function _editClass(){
		list($mode,$handle, $template) = $this->_context;
		$this->setPageType('form');
		$this->Form->setAttribute('action', $this->_uri . '/templates/'.$mode.'/' . $handle . '/');
		$this->setTitle(__('Symphony &ndash; Email Templates &ndash; Edit'));
		try{
			
			$templ = Array();
			
			if($mode != 'new'){
				$templates = EmailTemplateManager::load($handle);
				
				$templ = $templates->getConfig();
				$templ = (array)$templ['templates'];
			}
			
			foreach($templ as $name=>$tm){
				if(file_exists(dirname(__FILE__) . '/../templates/' . EmailTemplateManager::getHandleFromName($handle) . '/' . $tm)){
					$out .= Widget::Anchor(__("Edit ".ucfirst($name)." Template"), $this->_uri . '/templates/edit/' . $handle . '/' . $name, __('Edit Email Template'), 'button', NULL, array('accesskey' => 't'))->generate();
				}
			}
			
			$this->appendSubheading(__($mode != 'new' ? $templates->getName() : __('Untitled')),$out);
			
			$fields = Array();
			
			if($mode != 'new'){
				$fields = $templates->about;
				$fields = array_merge($fields, $templates->config);
			}
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Template Settings')));

			$label = Widget::Label(__('Title'));
			$label->appendChild(Widget::Input(
				'fields[name]', General::sanitize($fields['name'])
			));

			if(isset($this->_errors['name'])) {
				$label = $this->wrapFormElementWithError($label, $this->_errors['name']);
			}

			$fieldset->appendChild($label);
			//$this->Form->appendChild($fieldset);
			
			$label = Widget::Label(__('Data Sources'));

			$manager = new DatasourceManager($this->_Parent);
			$datasources = $manager->listAll();

			$options = array();

			if(is_array($datasources) && !empty($datasources)) {
				if(!is_array($fields['data_sources'])) $fields['data_sources'] = array();
				foreach ($datasources as $name => $about) $options[] = array(
					$name, in_array($name, (array)$fields['datasources']), $about['name']
				);
			}

			$label->appendChild(Widget::Select('fields[data_sources][]', $options, array('multiple' => 'multiple')));
			$fieldset->appendChild($label);
			$this->Form->appendChild($fieldset);
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input(
				'action[save]', __('Save Changes'),
				'submit', array('accesskey' => 's')
			));
			
			if($this->_context[0] == 'edit'){
				$button = new XMLElement('button', __('Delete'));
				$button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'button confirm delete', 'title' => __('Delete this page'), 'accesskey' => 'd'));
				$div->appendChild($button);
			}

			$this->Form->appendChild($div);
		}
		catch(EmailTemplateManagerException $e){
			// TODO: log error?
			throw new FrontendPageNotFoundException();
		}
	}
	
	function _delete($handles){
		if(!is_array($handles)){
			$handles = Array($handles=>$handles);
		}
		foreach($handles as $handle=>$val){
			$dir = dirname(__FILE__) . '/../templates/' . basename($handle);
			if(is_dir($dir)){
				try{
					if(!(($files = @scandir($dir)) && count($files) <= 2)){
						foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $filename => $cur){
							if(is_dir($filename)){
								rmdir($filename);
							}
							elseif(is_file($filename)){
								unlink($filename);
							}
						}
					}
					rmdir($dir);
					redirect($this->_uri . '/templates');
				}
				catch(Exception $e){
					$this->pageAlert(__('Directory could not be removed. Please check permissions on <code>/extensions/email_templates/templates/'.basename($handle).'</code>.'), Alert::ERROR);
					return false;
				}
			}
			else{
				$this->pageAlert(__('Directory could not be removed. Directory <code>/extensions/email_templates/templates/'.basename($handle).'</code> does not exist.'), Alert::ERROR);
				return false;
			}
		}
		return true;
	}
	
	function __getclassName(){
		return 'contentExtensionemail_templateindex'; 
	}
}