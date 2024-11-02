Webhooks for Bricks forms
=========================

Lets you add a webhook to a form on submission using the custom action

Installation:

-   Download Latest release

    -   Upload Latest Version

    -   Activate

**Using Webhook for bricks forms:**

Navigate to Bricks ----\> Webhook for Forms

![](https://github.com/stingray82/repo-images/raw/main/webhook-for-bricks-forms/Viewing Plugin.png)

**Settings Page:**

![](https://github.com/stingray82/repo-images/raw/main/webhook-for-bricks-forms/Webhook Settings.png)

To add a form you will add a form ID in (1) and a Webhook Url (2) and click
(save (4)

You can delete a hook by finding it in the settings and hitting delete (5)

Debug Settings are enabled by pushing (3)

**Finding your Form ID**

**Front End:**

![](https://github.com/stingray82/repo-images/raw/main/webhook-for-bricks-forms/Form Details.png)

If you inspect a form you will get the ID on the data-element-id

**Within Builder:**

![](https://github.com/stingray82/repo-images/raw/main/webhook-for-bricks-forms/Builder - Form ID.png)

Select the form and look a the default class in this example \#brxe-4a8d1d

**Important:**

For your forms to work you must make sure you add Custom to your Actions this
will trigger the hook `bricks/form/custom_action`

Which is what this plugin uses to process your form, you set this in form
Actions its in the dropdown list

\<Image 1\>

![](https://github.com/stingray82/repo-images/raw/main/webhook-for-bricks-forms/Custom Action 1.png)

And you need to make sure it is selected as per the below image and been saved

![](https://github.com/stingray82/repo-images/raw/main/webhook-for-bricks-forms/Custom Action Set.png)

**Support Me:  **

[Buy Me a Coffee](https://buymeacoffee.com/techarticlesuk)

[Subscribe to my YouTube Channel](https://www.youtube.com/@techarticlesuk)

 
