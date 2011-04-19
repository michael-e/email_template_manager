<?php

	require_once(dirname(__FILE__) . '/lib/class.emailtemplatemanager.php');
	require_once(TOOLKIT . '/class.datasourcemanager.php');
	
	Class extension_email_template_manager extends Extension{

		public function about(){
			return array(
				'name' => 'Email Template Manager',
				'version' => '2.1',
				'release-date' => '2011-04-05',
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
				$config = $template->getProperties();
				if(($key = array_search($file['parent']->Page->_context[1], $config['datasources'])) !== false){
					$config['datasources'][$key] = $ds_handle;
					return EmailTemplateManager::editConfig($template->getHandle(), array_merge($template->getAbout(), $config));
				}
			}
		}

		public function AppendEventFilter($context){
			$templates = EmailTemplateManager::listAll();
			foreach($templates as $template){
				$tmp[$template->getHandle()] = $template;
			}
			$templates = $tmp;
			ksort($templates, SORT_STRING);
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

		protected function _sendEmail($template, $context){
			try{
				$email = Email::create();
				$template->addParams(Array("etm-entry-id"=>$context['entry']->get('id')));
				Symphony::Engine()->Page()->_param["etm-entry-id"] = $context['entry']->get('id');
				$xml = $template->processDatasources();

				$about = $context['event']->about();
				General::array_to_xml($xml, Array("events"=>Array($about['name'] => Array("post-values" =>$context['fields']))));
				
				$template->setXML($xml->generate());

				$content = $template->render();

				if(isset($content['recipients'])){
					$email->recipients = $content['recipients'];
				}
				else{
					throw new EmailTemplateException("Can not send an email to nobody, please set a recipient.");
				}

				if(isset($content['subject'])){
					$email->subject = $content['subject'];
				}
				else{
					throw new EmailTemplateException("Can not send emails without a subject");
				}

				if(isset($content['reply-to-name'])){
					$email->reply_to_name = $content['reply-to-name'];
				}

				if(isset($content['reply-to-email-address'])){
					$email->reply_to_email_address = $content['reply-to-email-address'];
				}

				if(isset($content['plain']))
					$email->text_plain = $content['plain'];
				if(isset($content['html']))
					$email->text_html = $content['html'];
				$email->send();
			}
			catch(EmailTemplateException $e){
				$context['errors'][] = Array('etm-' . $template->getHandle(), false, $e->getMessage());
				return false;
			}
			catch(EmailValidationException $e){
				$context['errors'][] = Array('etm-' . $template->getHandle(), false, $e->getMessage());
				return false;
			}
			catch(EmailGatewayException $e){
				$context['errors'][] = Array('etm-' . $template->getHandle(), false, $e->getMessage());
				return false;
			}
			return true;
		}
	}