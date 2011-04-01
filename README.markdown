Email Template Manager
===========================

Version:	2.0   
Author:		Huib Keemink (creativedutchmen)

What's this?
------------------------

Using this extension is it possible to let Symphony send pretty emails using XSLT.   
This extension uses the Email API which is introduced in Symphony 2.2, earlier versions of Symphony are not supported.

What's new in this version?
-----------------------

 - Event filters are supported, so custom events are no longer needed to send emails.
 - It is possible to select which layouts to use (Html, Plain or both).

How do I use this?
--------------------

### Setting up the template

Before anything, you will have to setup your templates.   
Templates can be bundled with other extensions, and you can create your own.

If you have not created or downloaded any templates, this is the time to create one.
To start, go to Blueprints->Email Templates. This will list all your installed templates.

To create a new template, click the "Create new" button. You will be presented by a configuration screen with a number of options:

#### Template Settings

**Name**   
**Datasources**, because Email Templates work like pages, you can attach datasources to your Template, and you will be able to use XSLT to create your layouts.   
**Layouts**, the Email Template Manager allows you to set two layouts for each template, a Plain and a HTML version. If you want, you can use only one of them.

#### Email Settings

**Subject**, you can set your subject here. If you want to make your subject dynamic, you can do two things:   

1.	Use a variable in the param pool, like this: `{$website-name}'s newsletter of {$today}`, which will turn into something like: `Amazing website's newsletter of 2011-04-01`.
2.	Use XPath to select the data from your XML, like so: `SALE! {/data/sale/entry/title} for only {/data/sale/entry/price}!`, which will turn into: `SALE! Websites for only &euro;10.000!`

At this point, it is not possible to mix the two syntaxes.

**Congratulations!** You have now created your first Email Template.   
Ofcourse, you have not yet defined the layout of your template, so let's do that now.

### Setting up the layout(s)

Before you can send your templates, you will want to define how they look, and what they contain.

Editing a Layout works just like a regular Symphony page, so this will be easy.

#### Previewing / debugging your layout

Just like normal pages, it is possible to preview and debug your template, to see if you like its looks.   
To preview, simply click on the "Preview template" button. Because getting it just right on the first try isn't likely, you will probably want to debug your layout.

This is also possible, by appending ?debug to your URL (assuming you have the Debug Devkit installed!).

### Using an event filter

The event filters are the easiest way to start sending pretty emails using Symphony.   
Once you have setup your email template, you can attach the filter to your event.

More details are given in the documentation page of your event.

#### Debugging event data

Because the Email Templates are no regular pages, you will *not* be able to debug the event data in your XML easily.   

### Using a custom event

This is the most advanced (and interesting!) way to retrieve / access your templates.   

#### Example usage of a custom event

    // Build the email output
    require_once(EXTENSIONS . '/email_template_manager/lib/class.emailtemplatemanager.php');
    
    $template = EmailTemplateManager::load('YOUR_TEMPLATE_NAME');
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

