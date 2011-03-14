Email Template Manager
===========================

Version:	1.0beta   
Author:		Huib Keemink (creativedutchmen)

What's this?
------------------------

This extension will allow developers to add an interface to creating email templates in symphony.   
It does **NOT** have the functionality to override the ugly emails sent by symphony itself.  
Ofcourse, it is possible to send prettier emails using this extension, but it will require a custom event.

This extension is developed mainly for the Email Newsletter extension in mind.

Example usage
--------------------

    // Build the email output
    require_once(EXTENSIONS . '/email_templates/lib/class.emailtemplatemanager.php');
    
    $template = EmailTemplateManager::load('test');
    $template->addParams(array('entry-id'=>'21')); //optional
    $output = $template->render();
    
    // Send the email using the Core Email API
    $email = Email::create();
    try{
        $email->recipients = array(
            'John Doe' => 'john@example.com',
        );
        $email->subject    = $output['subject'];
        $email->text_plain = $output['plain'];
        $email->text_html  = $output['html'];
    
        $email->send();
    }
    catch(EmailGatewayException $e){
        throw new SymphonyErrorPage('Error sending email. ' . $e->getMessage());
    }
    catch(EmailException $e){
        throw new SymphonyErrorPage('Error sending email. ' . $e->getMessage());
    }

TODO
---------------

*	Update datasource name when datasource edited
*	Write a proper readme