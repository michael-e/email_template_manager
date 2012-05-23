<?php

Class <!-- CLASS NAME --> extends EmailTemplate{


	public $datasources = Array(<!-- DATASOURCES -->);
	public $layouts = Array(<!-- LAYOUTS -->);
	public $subject = '<!-- SUBJECT -->';
	public $reply_to_name = '<!-- REPLYTONAME -->';
	public $reply_to_email_address = '<!-- REPLYTOEMAIL -->';
	public $recipients = '<!-- RECIPIENTS -->';

	public $editable = true;

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
}