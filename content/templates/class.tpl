<?php

class <!-- CLASS NAME --> extends EmailTemplate
{
    public $datasources = array(<!-- DATASOURCES -->);
    public $layouts = array(<!-- LAYOUTS -->);
    public $subject = '<!-- SUBJECT -->';
    public $reply_to_name = '<!-- REPLYTONAME -->';
    public $reply_to_email_address = '<!-- REPLYTOEMAIL -->';
    public $recipients = '<!-- RECIPIENTS -->';
    public $attachments = '<!-- ATTACHMENTS -->';

    public $editable = true;

    public $about = array(
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
