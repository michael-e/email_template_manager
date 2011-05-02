Email Template Manager
===========================

Version:	3.0beta   
Author:		Huib Keemink (creativedutchmen)

1\. What's this?
------------------------

Using this extension is it possible to let Symphony send pretty emails using XSLT.   
Currently only S2.2.1 is supported.

2\. Understanding the basics
----------------------------

Before using this extension, you should be familiar with the Symphony CMS.
If you are not comfortable with datasources, events and parameters, please read the Symphony docs first.

###2.1 Templates & layouts###

Templates created by this extension are similar to traditional Symphony pages.
Just like with normal pages, you can attach datasources, whose xml you can process using XSLT.

However, there are a few differences, too. Most emails will consist of two layouts: HTML and Plain.
Every template created by the ETM has the option to select one or two layouts.
For each layout, you will be able to set an XSLT template.

**Warning: although it is possible, sending HTML-only templates is not recommended!**

###2.2 Subject, recipients and reply-to###

The subject and reply-to settings in the config panel can contain XpPath and parameters.
If your XPath returns more than one piece of data, only the first result is used - you can not have more than one subject.

For the recipients field, on the other hand, you can select more than one recipient with a single piece of XPath.
You can use the `Name <email@domain.com>`, `username`, `<email@domain.com>` and `"Name" <email@domain.com>` syntaxes.
Also, you can mix sources by combining queries with a comma: `username, email@domain.com, {/data/recipients/entry/email}` will create a valid list.

**Warning: when selecting recipients with a name and email: `{/data/recipients/name} <{/data/recipients/email}>`, both the name and email xpaths should return the same number of results**
**Warning: it is not possible to mix the parameters and xpath syntax in one query: `{/data/$param}` will not work**   

###2.3 Parameters (event filters only!)###

If you are using filters, the ETM will automatically add a few parameters that you can use to filter your datasources:

	-	`$etm-entry-id` will contain the id of the entry inserted by the event.
		You can use this parameter to filter a datasource and to email the data entered by the user.
		We will see this in action in the Contact Form in the Tutorials section of this manual.
	-	`$etm-recipient` will contain the email address of the recipient of the email.
		If you are sending to more than one person, the ETM will loop over your recipients, and set this value for every email.
		Again, you will be able to filter your datasources with this parameter to include more information about the recipient.

3\. Tutorials
--------------

###3.1 Contact form###

Coming up.

Changelog
-----------------------

[3.0]
	
 - Added looping over recipients, so multiple customised emails are possible
 - Moved filter preferences from POST values to configuration menu
 - Added recipients and reply-to values to generate() function
 - Added compatibility with the Members extension

[2.2]

 - Fixed sorting order of event filters
 - Fixed bug with field[] syntax in frontend
 - Fixed bug with demo code with more than one template installed

[2.1]   

 - SQL Injection flaw fixed
 
[2.0]   

 - Event filters are supported, so custom events are no longer needed to send emails.
 - It is possible to select which layouts to use (Html, Plain or both).