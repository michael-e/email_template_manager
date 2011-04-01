<?php

	require_once(dirname(__FILE__) . '/lib/class.emailtemplatemanager.php');
	require_once(TOOLKIT . '/class.datasourcemanager.php');
	
	Class extension_email_template_manager extends Extension{
	
		public function about(){
			return array(
				'name' => 'Email Template Manager',
				'version' => '2.0beta',
				'release-date' => '2011-02-04',
				'author' => array(
					'name' => 'Huib Keemink',
					'website' => 'http://www.creativedutchmen.com',
					'email' => 'huib.keemink@creativedutchmen.com'
				)
			);
		}
		
		public function fetchNavigation() {
			return array(
				array(
					'location'  => __('Blueprints'),
					'name'      => __('Email Templates'),
					'link'      => '/templates/'
				)
			);
		}
		
		public function getSubscribedDelegates() {
			return array(
				array(
				'page' => '/blueprints/events/edit/',
				'delegate' => 'AppendEventFilter',
				'callback' => 'AppendEventFilter'
				),
				array(
				'page' => '/blueprints/events/new/',
				'delegate' => 'AppendEventFilter',
				'callback' => 'AppendEventFilter'
				),
				array(
					'page' => '/frontend/',
					'delegate' => 'EventFinalSaveFilter',
					'callback' => 'eventFinalSaveFilter'
				),
				array(
					'page' => '/blueprints/events/edit/',
					'delegate' => 'AppendEventFilterDocumentation',
					'callback' => 'AppendEventFilterDocumentation'
				),
				array(
					'page' => '/blueprints/datasources/',
					'delegate' => 'DatasourcePostEdit',
					'callback' => 'DatasourcePostEdit'
				),
			);
		}
		
		public function install(){
			if(!is_dir(WORKSPACE . '/email-templates')){
				try{
					mkdir(WORKSPACE . '/email-templates');
				}
				catch(Exception $e){
					return false;
				}
			}
			return true;
		}
		
		public function DatasourcePostEdit($file){
			$ds_handle = DatasourceManager::__getHandleFromFileName(substr($file['file'], strrpos($file['file'], '/')+1));
			$templates = EmailTemplateManager::listAll();
			foreach($templates as $template){
				$config = $template->getConfig();
				if(($key = array_search($file['parent']->Page->_context[1], $config['datasources'])) !== false){
					$config['datasources'][$key] = $ds_handle;
					return EmailTemplateManager::editConfig($template->getHandle(), array_merge($template->getAbout(), $config));
				}
			}
		}
		
		public function AppendEventFilter($context){
			$templates = EmailTemplateManager::listAll();
			foreach($templates as $template){
				$handle = 'etm-' . $template->getHandle();
				$selected = (in_array($handle, $context['selected']));
				$context['options'][] = Array(
					$handle, $selected, General::sanitize("Send Email Template: " . $template->getName())
				);
			}
		}
		
		public function eventFinalSaveFilter($context){
			$templates = EmailTemplateManager::listAll();
			foreach($templates as $template){
				$handle = 'etm-' . $template->getHandle();
				if(in_array($handle, $context['event']->eParamFILTERS)){
					if($this->_sendEmail($template, $context)){
						$context['errors'][] = Array('etm-' . $template->getHandle(), true, __('Email sent successfully'));
					}
				}
			}
		}
		
		// borrowed from event.section.php
		protected function __sendEmailFindFormValue($needle, $haystack, $discard_field_name=true, $default=NULL, $collapse=true){

			if(preg_match('/^(fields\[[^\]]+\],?)+$/i', $needle)){
				$parts = preg_split('/\,/i', $needle, -1, PREG_SPLIT_NO_EMPTY);
				$parts = array_map('trim', $parts);

				$stack = array();
				foreach($parts as $p){
					$field = str_replace(array('fields[', ']'), '', $p);
					($discard_field_name ? $stack[] = $haystack[$field] : $stack[$field] = $haystack[$field]);
				}

				if(is_array($stack) && !empty($stack)) return ($collapse ? implode(' ', $stack) : $stack);
				else $needle = NULL;
			}

			$needle = trim($needle);
			if(empty($needle)) return $default;

			return $needle;

		}
		
		protected function _sendEmail($template, $context){
			ksort($_POST['etm'], SORT_STRING);
			$fields = Array();
			$params = Array();
			foreach((array)$_POST['etm'] as $handle => $values){
			
				// Numeric handle values are not set in html (etm[][setting]) and can be regarded as
				// "global" settings. These settings will apply to all email templates sent.
				// Named handles, on the other hand (etm[template_name][setting]) are template-specific settings.
				// This can be useful to send an email to the user (you have been registered) and to the admin (a new user has registered) with the same form,
				// but different templates.
				
				if(is_numeric($handle) || $template->getHandle() == $handle){
					$fields = array_merge($params, $values);
				}
			}
			$params['recipient'] = $this->__sendEmailFindFormValue($fields['recipient'], $_POST['fields'], true);
			if(!empty($params['recipient'])){

				$params['recipient']		= preg_split('/\,/i', $params['recipient'], -1, PREG_SPLIT_NO_EMPTY);
				$params['recipient']		= array_map('trim', $params['recipient']);
				$params['recipient']		= Symphony::Database()->fetch("SELECT `email`, `first_name` FROM `tbl_authors` WHERE `username` IN ('".@implode("', '", $params['recipient'])."') ");
				
				foreach($params['recipient'] as $recipient){
					$email = Email::create();
					try{
						list($rcp, $name) = array_values($recipient);
						$email->recipients = Array($name=>$rcp);
						$template->addParams(Array('etm-recipient-name'=>$name));
						$template->addParams(Array('etm-recipient-email'=>$rcp));
						$params['sender-name']	= $this->__sendEmailFindFormValue($fields['sender-name'], $_POST['fields'], true, NULL);
						if(!empty($params['sender-name'])){
							$email->sender_name = $params['sender-name'];
							$template->addParams(Array('etm-sender-name'=>$params['sender-name']));
						}
						$params['sender-email']	= $this->__sendEmailFindFormValue($fields['sender-email'], $_POST['fields'], true, NULL);
						if(!empty($params['sender-email'])){
							$email->sender_email = $params['sender-email'];
							$template->addParams(Array('etm-sender-email'=>$params['sender-email']));
						}
						$params['reply-to-name']	= $this->__sendEmailFindFormValue($fields['reply-to-name'], $_POST['fields'], true, NULL);
						if(!empty($params['reply-to-name'])){
							$email->reply_to_name = $params['reply-to-name'];
							$template->addParams(Array('etm-reply-to-name'=>$params['reply-to-name']));
						}
						$params['reply-to-email']	= $this->__sendEmailFindFormValue($fields['reply-to-email'], $_POST['fields'], true, NULL);
						if(!empty($params['reply-to-email'])){
							$email->reply_to_email_address = $params['reply-to-email'];
							$template->addParams(Array('etm-reply-to-email'=>$params['reply-to-email']));
						}
						
						$template->addParams(Array("etm-entry-id"=>$context['entry']->get('id')));
						Symphony::Engine()->Page()->_param["etm-entry-id"] = $context['entry']->get('id');
						$xml = $template->processDatasources();
						
						$about = $context['event']->about();
						
						General::array_to_xml($xml, Array("events"=>Array($about['name'] => Array("post-values" =>$context['fields']))));
						
						$template->setXML($xml->generate());

						$content = $template->render();

						if(isset($content['plain']))
							$email->text_plain = $content['plain'];
						if(isset($content['html']))
							$email->text_html = $content['html'];
						$email->subject = $content['subject'];
						
						$email->send();
					}
					catch(EmailValidationException $e){
						$errors['errors'][] = Array('etm-' . $template->getHandle(), false, $e->getMessage());
						return false;
					}
					catch(EmailGatewayException $e){
						$context['errors'][] = Array('etm-' . $template->getHandle(), false, $e->getMessage());
						return false;
					}
					return true;
				}	
			}
			else{
				$context['errors'][] = Array('etm-' . $template->getHandle(), false, 'No recipients selected, can not send emails.');
				return false;
			}
		}
		
		public function AppendEventFilterDocumentation($context){
			$templates = EmailTemplateManager::listAll();
			foreach($templates as $template){
				$handle = 'etm-' . $template->getHandle();
				if(in_array($handle, $context['selected'])){

					$context['documentation'][] = new XMLElement('h3', __('Send Email Using Email Templates'));
					$context['documentation'][] = new XMLElement('p', __('To use the Email Template Manager, only the recipients field has to be used, as seen below:'));
					$context['documentation'][] = contentBlueprintsEvents::processDocumentationCode('<form action="" method="post">
	<fieldset>
		<input name="etm[][recipient]" value="fred" type="hidden" />
		<input id="submit" type="submit" name="action[save-contact-form]" value="Send" />
	</fieldset>
</form>');

					$context['documentation'][] = new XMLElement('p', __('Of course, it is also possible to use more advanced features (like with the default send-email filter):'));
					$context['documentation'][] = contentBlueprintsEvents::processDocumentationCode('<form action="" method="post">
	<fieldset>
		<label>'.__('Name').' <input type="text" name="fields[author]" value="" /></label>
		<label>'.__('Email').' <input type="text" name="fields[email]" value="" /></label>
		<input name="etm[][recipient]" value="fred" type="hidden" />
		<input name="etm[][sender-email]" value="fields[email]" type="hidden" />
		<input name="etm[][sender-name]" value="fields[author]" type="hidden" />
		<input name="etm[][reply-to-email]" value="fields[email]" type="hidden" />
		<input name="etm[][reply-to-name]" value="fields[author]" type="hidden" />
		<input id="submit" type="submit" name="action[save-contact-form]" value="Send" />
	</fieldset>
</form>');

					$context['documentation'][] = new XMLElement('p', __('To make things even better, it is even possible to use two or more Email Template filters, with different settings:'));
					$context['documentation'][] = contentBlueprintsEvents::processDocumentationCode('<form action="" method="post">
	<fieldset>
		<label>'.__('Name').' <input type="text" name="fields[author]" value="" /></label>
		<label>'.__('Email').' <input type="text" name="fields[email]" value="" /></label>
		
		<input name="etm[template_1_handle][recipient]" value="fred" type="hidden" />
		<input name="etm[template_2_handle][recipient]" value="hank" type="hidden" />
		
		<input name="etm[][sender-email]" value="fields[email]" type="hidden" />
		<input name="etm[][sender-name]" value="fields[author]" type="hidden" />
		<input name="etm[][reply-to-email]" value="fields[email]" type="hidden" />
		<input name="etm[][reply-to-name]" value="fields[author]" type="hidden" />
		<input id="submit" type="submit" name="action[save-contact-form]" value="Send" />
	</fieldset>
</form>');
				$context['documentation'][] = new XMLElement('p', __('In the example only the recipient setting is changed, but the same principle applies to all settings described above.'));
			}
			break;
		}
	}
}