<?php

Class <!-- CLASS NAME --> extends EmailTemplate{

	public $about = Array(
		'name' => '<!-- NAME -->',
		'version' => '<!-- VERSION -->',
		'author' => array(
			'name' => '<!-- AUTHOR NAME -->',
			'website' => '<!-- AUTHOR WEBSITE -->',
			'email' => '<!-- AUTHOR EMAIL -->'
		),
		'release-date' => '<!-- RELEASE DATE -->'
	);
	
	public $config = Array(
		'datasources' => Array(<!-- DATASOURCES -->
		),
		'layouts' => Array(<!-- LAYOUTS -->
		),
		'subject' => '<!-- SUBJECT -->',
		'editable' => true
	);
	
}