<?php

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
	}