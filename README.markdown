Email Template Manager
===========================

Version:	3.0beta   
Author:		Huib Keemink (creativedutchmen)

1. What's this?
------------------------

Using this extension is it possible to let Symphony send pretty emails using XSLT.   
Currently only S2.2.1 is supported.

2. How do I use this?
--------------------

### 2.1. Setting up the template

Before anything, you will have to setup your templates.   
Templates can be bundled with other extensions, and you can create your own.

If you have not created or downloaded any templates, this is the time to create one.
To start, go to Blueprints->Email Templates. This will list all your installed templates.

To create a new template, click the "Create new" button. You will be presented by a configuration screen with a number of options:

#### 2.2. Template Settings

**Name**   
**Datasources**, because Email Templates work like pages, you can attach datasources to your Template, and you will be able to use XSLT to create your layouts.   
**Layouts**, the Email Template Manager allows you to set two layouts for each template, a Plain and a HTML version. If you want, you can use only one of them.

#### 2.3. Email Settings

**Subject**, you can set your subject here. If you want to make your subject dynamic, you can do two things:   

1.	Use a variable in the param pool, like this: `{$website-name}'s newsletter of {$today}`, which will turn into something like: `Amazing website's newsletter of 2011-04-01`.
2.	Use XPath to select the data from your XML, like so: `SALE! {/data/sale/entry/title} for only {/data/sale/entry/price}!`, which will turn into: `SALE! Websites for only &euro;10.000!`

At this point, it is not possible to mix the two syntaxes in one query. So `{/data/$param}` is not supported.

**Recipients**, the recipients of your email. It is possible to send the email to more than one recipient, but keep in mind this is not a mass mailer.
You can use the same syntax as in the subject. Your XPath can return more than one result.

**Reply-To Name &amp; Reply-To Email Address**, this optional setting will set the reply-to value of your email. For configuration help, see subject.

**Congratulations!** You have now created your first Email Template.   
Ofcourse, you have not yet defined the layout of your template, so let's do that now.

### 2.4. Setting up the layout(s)

Before you can send your templates, you will want to define how they look, and what they contain.

Editing a Layout works just like a regular Symphony page, so this will be easy.

#### 2.4.1. Previewing / debugging your layout

Just like normal pages, it is possible to preview and debug your template, to see if you like its looks.   
To preview, simply click on the "Preview template" button. Because getting it just right on the first try isn't likely, you will probably want to debug your layout.

This is also possible, by appending ?debug to your URL (assuming you have the Debug Devkit installed!).

3. Sending emails using the Members extension
-------------------------

Since v3 it is possible to use the ETM together with the Members extension.

### 3.1. Example: sending Members' email ###

Forthcoming (due to changes in Members API)

4. Sending emails
--------------------

### 4.1. Using an event filter

The event filters are the easiest way to start sending pretty emails using Symphony.   
Once you have setup your email template, you can attach the filter to your event.

#### 4.1.1. Example: contact form ####

To use the ETM for contact forms, you will have to setup a few things first.
As always, you will have to create your section to contain the submitted data.

Fo

### 4.2. Using a custom event

This is the most advanced (and interesting!) way to retrieve / access your templates.   

#### 4.2.1. Example: custom event

    // Build the email output
    require_once(EXTENSIONS . '/email_template_manager/lib/class.emailtemplatemanager.php');
    
    $template = EmailTemplateManager::load('YOUR_TEMPLATE_NAME');
    $template->addParams(array('entry-id'=>'21')); //optional
    $output = $template->render();
    
    // Send the email using the Core Email API
    $email = Email::create();
    try{
        $email->recipients = $output['recipients'];
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

Changelog
-----------------------

[3.0]
	
	Moved filter preferences from POST values to configuration menu
	Added recipients and reply-to values to generate() function
	Added compatibility with the Members extension

[2.2]

    Fixed sorting order of event filters
    Fixed bug with field[] syntax in frontend
    Fixed bug with demo code with more than one template installed

[2.1]   

 - SQL Injection flaw fixed
 
[2.0]   

 - Event filters are supported, so custom events are no longer needed to send emails.
 - It is possible to select which layouts to use (Html, Plain or both).