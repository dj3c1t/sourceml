# Change Sourceml's default rendering

[back to README](../README.md)

## Overview

There is a basic theme management mechanism in Sourceml.

If you want to change Sourceml's default rendering, you can copy the Sourceml file
that you want to edit in your own theme folder and make your changes in this one.
This way you will be able to upgrade your Sourceml installation without loosing
your changes.

## Make your own theme

### Declare the theme in the application configuration

Edit the **config/packages/app.yaml** file and change this line :

```
    sourceml_theme: null
```

To :

```
    sourceml_theme: my_theme
```

Choose your own theme name for *my_theme*

#### Empty the configuration cache

Delete the cache folder to force Sourceml to reload the configuration
(you can safely remove the **dev** folder) :

```
var/cache/dev
```

### Change public files

All public files in Sourceml (css, javascript, image files..) are stored in the
**public** directory.

Create a new folder with your theme's name in the **public/themes** directory :

```
public/themes/my_theme
```

Let's say you want to change this file :

```
public/app/css/style.css
```

Copy it to your theme's folder, keeping the orginal path and file name :

```
public/themes/my_theme/app/css/style.css
```

And that's it. Your file will be used instead of the Sourceml's default one.

### Change template files

Every piece of HTML code that Sourceml send to a browser is coming from a
[Twig](https://twig.symfony.com/) template file. Those files are located in the
**templates** folder, with a **.twig** extension.

Create a new folder with your theme's name in the **templates/themes** directory :

```
templates/themes/my_theme
```

Let's say now you want to change this file :

```
templates/App/base.html.twig
```

Copy it to your theme's folder, keeping the orginal path and file name :

```
templates/themes/my_theme/App/base.html.twig
```

And this file will be used instead of the Sourceml's one.

#### Empty the template cache

If you don't see any change after editing your custom twig file, try to delete
the cache folder (you can safely remove the **dev** folder) :

```
var/cache/dev
```

## What Sourceml files are used in the default rendering ?

### Public files

Public files are relatively easy to locate, using a browser inspection tool.

Regarding css rules, in most cases the file to edit will be one amoung the following :

* public/app/css/style.css
* public/sources/css/admin.css
* public/sources/css/sources.css

### Template files

In the **templates** directory, twig files are stored in three main folders :

| folder | description |
| --- | --- |
| **App** | twig files for the global shape of the website |
| **Sources** | twig files for almost everything else |
| **JQFileUpload** | twig files for the upload forms |

Each of them containing files and sub-folders (...) with names that hopfully will be
helpfull...

A good start would be to take a look at the twig file that defines de global HTML
structure :

* templates/App/base.html.twig

#### A more formal way to find twig files

Sourceml is based on Symfony. A more formal way to find the twig file you're looking
for would be to track the process leading to this file, following Symfony's mechanisms.

A quick overview could be something like :

1. Starting from the URL in the browser, you can find the corresponding *route*
definition, in a .yaml file, in the **config/routes** directory.
2. The *route* definition tells what *action* is called in which *controller*
3. Looking in this *action*'s code, you will see which twig file is used.
4. Then follow twig inclusions in the twig file if the HTML part you're looking for is
not in it.

But Symfony is also very well documented, and you will find plenty of explanations,
with code samples, in the [Symfony documentation](https://symfony.com/doc/current/index.html).
