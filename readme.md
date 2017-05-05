[![Release](https://img.shields.io/github/release/ivinteractive/kirbycms-redirects.svg)](https://github.com/ivinteractive/kirbycms-redirects/releases)
[![License](https://img.shields.io/github/license/ivinteractive/kirbycms-redirects.svg)](https://raw.githubusercontent.com/ivinteractive/kirbycms-redirects/master/license.md)

# Redirecty

When updating a non-CMS site to Kirby, we usually spend a lot of time updating old links to the pages' new URIs. We can add routing for old URIs that match patterns, but some links won't follow those patterns. Redirecty does the following:

- 301 redirects for listed URIs
- Content updates to search for and replace old links
- CSV/JSON import/export of the redirects list

The plugin requires Kirby 2.3.0+.

## Installation

Download the zip and extract the files to /site/plugins/redirecty, or if you're using it as a submodule run this from your site root:

```
git submodule add https://github.com/ivinteractive/kirbycms-redirects ./site/plugins/redirecty
```

## Adding your redirects

The plugin includes and registers a redirects blueprint if no alternate blueprint is provided (see ```redirecty-template``` below). Create a redirects page using this template and add your redirects (oldbad_uri.html => wonderful-new-kirby-uri). Call the ```redirecty()``` function at the top of your header snippet, and you're all set! Test your redirects out to make sure everything's set -- the redirects should show up with a 301 in the Developer tools Network tab.

![Redirects panel screenshot](https://raw.githubusercontent.com/ivinteractive/kirbycms-redirects/dev/sample.png)

## Link updating

301 redirects are nice, but wouldn't it be great to get the actual links within your content updated, too? For this, you'll need to set up a couple of config variables, and then go to [http://yourdomain.com/redirecty](yourdomain.com/redirecty).

We recommend using dry runs at first and reviewing the update reports to make sure that Redirecty isn't operating overzealously -- if old URIs that are getting updated are single words, please be aware that those words will be updated *everywhere in your content!*

### Options

##### redirecty
Type: ```Boolean``` Default: ```false```

Turns the link updater URI on or off. By default, the link updater will just return a 404. **Required for updating links and CSV/JSON exports.**

##### redirecty-dryrun

Type: ```Boolean``` Default: ```true```

Whether or not the plugin should actually update the pages when this is run. Default is set to give you a preview before going ahead with the changes. **Required for updating links.**

### Additional Options

These won't be necessary to change from the defaults in most cases, but they're available in case that's necessary:

#### Link updating options

##### redirecty-uri

Type: ```String``` Default: ```redirecty```

The URI where the link updater will get run from.

##### redirecty-noauth

Type: ```Boolean``` Default: ```true```

By default, the redirecty URI only returns a result if you're logged in and have panel access. This could most likely be set to false if you're in a dev environment and you can trust everyone with access to the site, but otherwise, it's better to make sure you're the only one with access to the link updating functionality. **Note:** The same auth/no-auth logic applies to the CSV/JSON export functionality.

##### redirects-list-uri

Type: ```String``` Default: ```redirects```

The URI for the source of all your redirects (using the redirects template). **Note:** If you're not using the default URI, this needs to get set for both the link updater *and* the redirection to work.

##### redirecty-case

Type: ```Boolean``` Default: ```true```

Case-sensitivity. Defaults to case-sensitive to avoid unintended changes. If you're noticing that the link updater is reporting fewer replacements than pages (e.g. 'Redirecty would have made 
0 replacements in 1 page.'), try setting this to false. **Note:** This is also used in 301 redirects.

#### Redirection options

##### redirecty-home

Type: ```Boolean``` Default: ```true```

Whether the homepage's URL (i.e. '/home') should have a 301 redirect to the base URL.

##### redirecty-self

Type: ```String``` Default: ```Homepage```

Where the redirects list should redirect to. Set to whatever URI the redirects list page should redirect to, or set to false to not have it redirect at all. Added so you don't have to remember to redirect the list itself.

##### redirecty-subfolder

Type: ```Boolean``` Default: ```false```

Whether to include the base path removal in the redirect matching. Change this to true if your site exists in a subfolder.

##### redirecty-multi

Type: ```Integer``` Default: ```302```

The type of redirect used for versions of pages that are not the default language on a multilanguage setup. The default language will still get a 301 redirect.

#### Import options

Upload a CSV or JSON file to the redirects list page in the Kirby panel -- the file will automatically be parsed and the page will be updated.

##### redirecty-import

Type: ```String``` Default: ```append```

How CSV/JSON imports should modify the redirects list. Default just appends to the existing list, change to ```replace``` to have the imported redirects replace the current list.

##### redirecty-import-save

Type: ```Boolean``` Default: ```false```

By default, CSV/JSON files used for imports get deleted immediately afterwards. Set this to true if you want them to stick around.

##### redirecty-csv

Type: ```String``` Default: ```redirecty-csv```

The URI where you can get CSV exports of the redirects list.

##### redirecty-json

Type: ```String``` Default: ```redirecty-json```

The URI where you can get JSON exports of the redirects list.

##### redirecty-template

Type: ```String``` Default: ```redirects```

The name of the template you're using for the redirects list. This is used as a check to make sure that the CSV/JSON import hook only runs on your redirects page (parsing the file, updating the page, and deleting the uploaded file), as well as a check on whether the blueprint included with the plugin should be registered. If no alternate blueprint is set in the config file, the default redirects template will be registerd.

##### redirecty-widget

Type: ```Boolean``` Default: ```true```

Whether or not to show the dashboard widget.

##### redirecty-widget-count

Type: ```Integer``` Default: ```5```

The maximum amount of redirects to show in the dashboard widget. If set to 0, it hides the redirects preview and CSV/JSON download links in the widget.