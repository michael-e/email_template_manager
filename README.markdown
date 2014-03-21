Email Template Manager
===========================

Author: Huib Keemink (creativedutchmen)

Contents
------------
- 1\. What's this?
- 2\. Understanding the basics
    - 2.1 Templates and layouts
    - 2.2 Subject, recipients and reply-to
    - 2.3 Parameters
- 3\. Tutorials
    - 3.1 Contact Form


1\. What's this?
------------------------

Using this extension it is possible to let Symphony send pretty emails using XSLT. Currently only S2.2.x is supported.

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

The subject and reply-to settings in the config panel can contain XPath and parameters.
If your XPath returns more than one piece of data, only the first result is used - you can not have more than one subject.

For the recipients field, on the other hand, you can select more than one recipient with a single piece of XPath.
You can use the `Name <email@domain.com>`, `username`, `<email@domain.com>` and `"Name" <email@domain.com>` syntaxes.
Also, you can mix sources by combining queries with a comma: `username, email@domain.com, {/data/recipients/entry/email}` will create a valid list.

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

Using symphony and the "Send Notification Email" filter, it was already possible to send a quick preview of a response in a contact form.
With ETM you are able to take this concept a few steps further.

In this tutorial we will:

-	Send an email to an author in the symphony installation with a summary of the data in the contact form.
-	Email the visitor that his request for information has been received.

####3.1.1 Setting up the section####

To store all requests, we will create a section called 'Responses'. Add three fields to this section: **Name** (text-input, required), **Email** (text-input, email, required) and **Body** (textarea).

####3.1.2 Setting up the datasources####

Because we want our email to the author to contain pieces of the response, we will have to create a new datasource called **'Responses'** that gets its information from the 'Responses' section.
At this moment, this datasource will return all responses ever created. This is not what we want - we want to only load the response we are emailing the author about.

To do this, the ETM has a parameter you can use to filter your datasource: `$etm-entry-id`, this will contain the entry id of the entry created by the event.
You can filter your datasource using this parameter (remember to filter by System ID).

In the email, we want to include the body, the email address and the name of the response, so include those in your `Included Elements` selectbox.

####3.1.3 Setting up the first template settings (notification)#####

Now that we have created our section and our datasource, we can use this data to create a nice-looking email.
To do this, create a new Email Template (Blueprints->Email Templates->Create New).

This first email will be sent to the author, and will notify the author of a new response created. A nice name for this template can be **'Response-Notification'**.

Next, select your 'Responses' datasource we created before in the `Datasources` selectbox.

To keep things simple, select `Plain only` in the `Layouts` dropdown menu. If you want, you can also create a HTML template here, all the concepts are the same.

Now for the interesting part. In the normal "Send Notification Email" filter, the subject would be predefined and static. With the ETM, you can set your own subject, and it can be dynamic, too.
To see what happens, use `A new response has been posted by {/data/responses/entry/name}` as your subject.
In this example, we have used the recipients name in the subject, creating a subject like: `A new response has been posted by Huib Keemink`, cool eh?

Next, in the `Recipients` box, you can type the username of an author in Symphony. In my installation this is `huib`, but it can be anything that you have set.
For more information about how to use the `Recipients` box, please look at section `2.2` of this manual.

We have now setup all required settings for the Template, but there are two options left: `Reply-To Name` and `Reply-To Email Address`.
To make replying extra easy, we can set these to contain the visitor's name and email. To do this, simply use `{/data/responses/entry/name}` in the `Reply-To Name` field, and `{/data/responses/entry/email}` in the `Reply-To Email Address` field.

####3.1.3 Setting up the first template layout (notification)#####

Now that we have configured the template to have a proper name, be sent to the right email address, have a descriptive subject and make replying extra easy,
it's time to create our layout.

Because we are only sending a plain email, you will have to configure only the Plain layout.
In the `Body` textarea, you can insert your XSLT that will eventually be sent to the email address you provided. Below is an example of what you could use:

	<?xml version="1.0" encoding="UTF-8"?>
	<xsl:stylesheet version="1.0"
		xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output method="text"
		omit-xml-declaration="yes"
		encoding="UTF-8"
		indent="no" />

	<xsl:template match="/">
		Woohoo! Somebody wants information from us!

		To be more precise, <xsl:value-of select="/data/responses/entry/name" /> asked this:

		<xsl:value-of select="/data/respones/entry/body" />

		---------------------------
		To respond, you can send an email to: <xsl:value-of select="/data/responses/entry/email"/> or reply to this email.

	</xsl:template>
	</xsl:stylesheet>

####3.1.4 Setting up the second template settings (thank you message)#####

First, create a new email template, and name it `Response Thankyou`.
For this template, we can do pretty much all you want, the only thing that is really important is the `recipients` setting.
Because we want this template to be sent to the sender of the form, we can use some XPath to select the email from the event.

The ETM does not directly include the POST data in the event XML, so `{/data/events/_eventname_/post-data}` will not work.

However, since we have already filtered the Responses datasource to only display this piece of information, we can use that.
So, in the recipients pane, type: `{/data/responses/entry/name} <{/data/responses/entry/email}>`.

####3.1.4 Setting up the second template layout (thank you message)#####

We have now setup this template, all we need to do is edit the layout of this email.

If you have selected to use only a Plain layout, as you did with the notification template, you can use something like this for the layout XSLT:

	<?xml version="1.0" encoding="UTF-8"?>
	<xsl:stylesheet version="1.0"
		xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output method="text"
		omit-xml-declaration="yes"
		encoding="UTF-8"
		indent="no" />

	<xsl:template match="/">
		Dear <xsl:value-of select="/data/responses/entry/name" />,

		Thank you for your interest in <xsl:value-of select="$website-name"/>.
		We have received your inquiry, and will respond as quick as we can - usually within 24 hours.

		Regards,

		The ETeaM
	</xsl:template>
	</xsl:stylesheet>

####3.1.5 Setting up the event####

Ok, so we have setup our section, our datasource and our template, let's make it work!

In the event editor, select your templates in the list of event filters (they will be named `Send Email Template: Response-Notification` and `Send Email Template: Response Thankyou`).
Now we are nearly done setting everything up, all we need to do is attach the event to a page and include the form (as usual).

If everything went OK, submitting the form with a valid email should send out two emails: one to an author on the website, and one to the sender of the form. If it doesn't, please report your bugs at the [bugtracker](https://github.com/creativedutchmen/email_template_manager/issues)

####3.1.6 Conclusion####

In this (short) tutorial, we have looked at some of the basics of the ETM: creating templates, editing the layouts, setting dynamic recipients, subjects and reply-to headers.
This tutorial has been written with the questions asked on the forum in mind. If you feel some parts have not been explained well, or should be added, feel free to [post your remarks](http://symphony-cms.com/discuss/thread/64323/).
