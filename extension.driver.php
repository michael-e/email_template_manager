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
					'location'  => __('Blueprints'), // or 'Blueprints' or whatever
					'name'      => __('Email Templates'),
					'link'      => '/templates/'
				)
			);
		}
	}