<?php

	require_once(dirname(__FILE__) . '/lib/class.emailtemplatemanager.php');
	
	Class extension_email_templates extends Extension{
	
		public function about(){
			return array(
				'name' => 'Email Templates',
				'version' => '1.0',
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
					$this->_sendEmail($template, $context);
				}
			}
		}
		
		protected function _sendEmail($template, $context){
			ksort($_POST['etm'], SORT_STRING);
			$params = Array();
			foreach((array)$_POST['etm'] as $handle => $values){
			
				// Numeric handle values are not set in html (etm[][setting]) and can be regarded as
				// "global" settings. These settings will apply to all email templates sent.
				// Named handles, on the other hand (etm[template_name][setting]) are template-specific settings.
				// This can be useful to send an email to the user (you have been registered) and to the admin (a new user has registered) with the same form,
				// but different templates.
				
				if(is_numeric($handle) || $template->getHandle() == $handle){
					$params = array_merge($params, $values);
				}
			}
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
						if(!empty($params['sender-name'])){
							$email->sender_name = $params['sender-name'];
							$template->addParams(Array('etm-sender-name'=>$params['sender-name']));
						}
						if(!empty($params['sender-email'])){
							$email->sender_email = $params['sender-email'];
							$template->addParams(Array('etm-sender-email'=>$params['sender-email']));
						}
						if(!empty($params['reply-to-name'])){
							$email->reply_to_name = $params['reply-to-name'];
							$template->addParams(Array('etm-reply-to-name'=>$params['reply-to-name']));
						}
						if(!empty($params['reply-to-email'])){
							$email->reply_to_email_address = $params['reply-to-email'];
							$template->addParams(Array('etm-reply-to-name'=>$params['reply-to-name']));
						}

						foreach((array)$context['fields'] as $name => $val){
							$flds['etm-' . $name] = implode(", ", (array)$val);
						}
						$template->addParams($flds);
						
						$content = $template->render();
						
						$email->text_plain = $content['Plain'];
						$email->text_html = $content['HTML'];
						$email->subject = $content['subject'];
						
						$email->send();
					}
					catch(EmailValidationException $e){
						$errors['errors'][] = Array('etm-' . $template->getHandle(), false, $e->getMessage());
					}
					catch(EmailGatewayException $e){
						$context['errors'][] = Array('etm-' . $template->getHandle(), false, $e->getMessage());
					}
				}	
			}
			else{
				$context['errors'][] = Array('etm-' . $template->getHandle(), false, 'No recipients selected, can not send emails.');
			}
		}
	}