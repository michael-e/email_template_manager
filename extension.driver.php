<?php

require_once(dirname(__FILE__).'/lib/class.emailtemplatemanager.php');
require_once(TOOLKIT.'/class.datasourcemanager.php');

Class extension_email_template_manager extends Extension {

	/**
	 * The installation logic
	 *
	 * We need to make sure that there is a directory named "email-templates" in the workspace.
	 *
	 * @return bool
	 */
	public function install()
	{

		// If there isn't any directory like that
		if (!is_dir(WORKSPACE.'/email-templates')) {
			// Go ahead and create it.
			try {
				mkdir(WORKSPACE.'/email-templates');
			} catch (Exception $e) {
				// If we couldn't create it just crap out.
				return false;
			}
		}

		return true;
	}

	/**
	 * The uninstallation logic
	 *
	 * We need to remove the "email-templates" directory in the workspace as well as every email template in it.
	 *
	 * @return bool|void
	 */
	public function uninstall()
	{
		General::deleteDirectory(WORKSPACE.'/email-templates');
	}

	/**
	 * Add the navigation item for the extension.
	 *
	 * Create the "Email Templates" menu item under "Blueprints".
	 *
	 * @return array
	 */
	public function fetchNavigation()
	{

		return array(
			array(
				'location' => __('Blueprints'),
				'name' => __('Email Templates'),
				'link' => '/templates/'
			)
		);
	}

	/**
	 * Bind to Symphony's Delegates.
	 *
	 * We will be using four delegates to make this work:
	 *
	 * On the backend:
	 * - /blueprints/events/[new|edit]/::AppendEventFilter
	 * - /blueprint/events/edit/::AppendEventFilterDocumentation
	 * - /blueprint/datasources/::DataSourceEdit
	 *
	 * On the frontend:
	 * - /frontend/::EventFinalSaveFilter
	 *
	 * @return array
	 */
	public function getSubscribedDelegates()
	{

		return array(
			array(
				'page' => '/blueprints/events/edit/',
				'delegate' => 'AppendEventFilter',
				'callback' => 'appendEventFilter'
			),
			array(
				'page' => '/blueprints/events/new/',
				'delegate' => 'AppendEventFilter',
				'callback' => 'appendEventFilter'
			),
			array(
				'page' => '/blueprints/events/edit/',
				'delegate' => 'AppendEventFilterDocumentation',
				'callback' => 'appendEventFilterDocumentation'
			),
			array(
				'page' => '/blueprints/datasources/',
				'delegate' => 'DatasourcePostEdit',
				'callback' => 'datasourcePostEdit'
			),
			array(
				'page' => '/frontend/',
				'delegate' => 'EventFinalSaveFilter',
				'callback' => 'eventFinalSaveFilter'
			),
		);
	}

	/**
	 * Binding to the DatasourcePostEdit Delegate.
	 *
	 * We get the datasource handle and the email templates we have
	 *
	 * @uses DatasourcePostEdit http://symphony-cms.com/learn/api/2.3/delegates/#DatasourcePostEdit
	 *
	 * @param string $file The path to the Datasource file.
	 *
	 * @return bool
	 */
	public function datasourcePostEdit($file)
	{

		$ds_handle = DatasourceManager::__getHandleFromFileName(basename($file['file']));
		$templates = EmailTemplateManager::listAll();
		foreach ($templates as $template) {
			$config = $template->getProperties();
			// TODO: Investigate below, this should not be able to happen since the DatasourcePostEdit just provides array('file' => $file) not array('file' => $file, 'parent' => $parent).
			if (($key = array_search($file['parent']->Page->_context[1], $config['datasources'])) !== false) {
				$config['datasources'][$key] = $ds_handle;
				return EmailTemplateManager::editConfig($template->getHandle(), array_merge($template->getAbout(), $config));
			}
		}
	}

	/**
	 * Binding to the AppendEventFilter Delegate.
	 *
	 * Attach all the available templates to the available event filters.
	 *
	 * @see AppendEventFilter http://symphony-cms.com/learn/api/2.3/delegates/#AppendEventFilter
	 *
	 * @param array $context The context data.
	 *
	 * @return void
	 */
	public function appendEventFilter($context)
	{

		$templates = EmailTemplateManager::listAll();
		if (empty($templates)) {
			return;
		}

		$tmp = array();
		foreach ($templates as $template) {
			$tmp[$template->getHandle()] = $template;
		}

		$templates = $tmp;
		unset($tmp);

		ksort($templates, SORT_STRING);
		foreach ($templates as $template) {
			$handle = 'etm-'.$template->getHandle();
			$context['options'][] = array(
				$handle,
				(in_array($handle, $context['selected'])),
				General::sanitize(__('Send Email Template: %s', array($template->getName())))
			);
		}
	}

	/**
	 * Binding to the EventFinalSaveFilter Delegate.
	 *
	 * @see EventFinalSaveFilter http://symphony-cms.com/learn/api/2.3/delegates/#EventFinalSaveFilter
	 *
	 * @param array $context The context data
	 */
	public function eventFinalSaveFilter($context)
	{

		$templates = EmailTemplateManager::listAll();
		foreach ($templates as $template) {
			$handle = 'etm-'.$template->getHandle();
			if (in_array($handle, (array)$context['event']->eParamFILTERS)
				&& (($response = $this->_sendEmail($template, $context)) !== false)
			) {
				$context['errors'][] = array(
					$handle,
					($response['sent'] > 0),
					null,
					$response
				);
			}
		}
	}

	/**
	 * Send emails.
	 *
	 * Helper function for the EventFinalSaveFilter Delegate binding.
	 * todo: more descriptive documentation to follow on this. Currently I am too unfamiliar with the meat of the code to do it. -petsagouris
	 *
	 * @throws EmailTemplateException
	 *
	 * @param EmailTemplate $template
	 * @param array $context
	 *
	 * @return array|bool If everything works, an array of two ints ('total','sent') is returned.
	 */
	protected function _sendEmail($template, $context)
	{

		try {
			// Make sure that the etm-entry-id is everywhere we will be looking in.
			$template->addParams(array('etm-entry-id' => $context['entry']->get('id')));
			Symphony::Engine()->Page()->_param['etm-entry-id'] = $context['entry']->get('id');

			//
			$xml = $template->processDatasources();

			$about = $context['event']->about();

			General::array_to_xml($xml, array(
				'events' => array(
					$about['name'] => array(
						'post-values' => $context['fields']
					)
				)
			));

			$template->setXML($xml->generate());
			$template->parseProperties();
			$properties = $template->getParsedProperties();
			$recipients = $properties['recipients'];

			$sent = 0;
			if (count($recipients) > 0) {
				foreach ((array)$recipients as $name => $emailaddr) {
					try {
						// Get the email object
						$email = Email::create();
						$template->addParams(array('etm-recipient' => $emailaddr));
						$xml = $template->processDatasources();
						$about = $context['event']->about();

						General::array_to_xml($xml, array(
							'events' => array(
								$about['name'] => array(
									'post-values' => $context['fields']
								)
							)
						));

						$template->setXML($xml->generate());
						$template->recipients = $emailaddr;

						$content = $template->render();

						// Set the email Subject.
						if (!empty($content['subject'])) {
							$email->setSubject($content['subject']);
						}
						else {
							throw new EmailTemplateException(__('Can not send emails without a subject'));
						}

						// Set the email Reply-To name.
						if (isset($content['reply-to-name'])) {
							$email->setReplyToName($content['reply-to-name']);
						}

						// Set the Reply-To address.
						if (isset($content['reply-to-email-address'])) {
							$email->setReplyToEmailAddress($content['reply-to-email-address']);
						}

						// Set the plain text part.
						if (isset($content['plain'])) {
							$email->setTextPlain($content['plain']);
						}

						// Set the HTML part.
						if (isset($content['html'])) {
							$email->setTextHtml($content['html']);
						}

						// Validate the email address and add it, or throw an exception.
						require_once(TOOLKIT.'/util.validators.php');
						if (General::validateString($emailaddr, $validators['email'])) {

							$email->recipients = array(
								$name => $emailaddr
							);
						}
						else {
							throw new EmailTemplateException(__('Email address invalid: %s',array($emailaddr)));
						}

						// Send the email.
						$email->send();
						// Count it in
						$sent++;
					} catch (EmailTemplateException $e) {
						// Log the exception, maybe its something we want to have a look at.
						Symphony::Log()->pushToLog(__('Email Template Manager: ').$e->getMessage(), null, true);
						continue;
					}
				}
			}
			else {
				throw new EmailTemplateException(__('Can not send an email to nobody, please set a recipient.'));
			}
		}
		catch (EmailTemplateException $e) {
			$context['errors'][] = array('etm-'.$template->getHandle(), false, $e->getMessage());
			return false;
		}
		catch (EmailValidationException $e) {
			$context['errors'][] = array('etm-'.$template->getHandle(), false, $e->getMessage());
			return false;
		}
		catch (EmailGatewayException $e) {
			$context['errors'][] = array('etm-'.$template->getHandle(), false, $e->getMessage());
			return false;
		}

		return array('total' => count($recipients), 'sent' => $sent);
	}
}