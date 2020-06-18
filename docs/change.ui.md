# Change Sourceml default's rendering

[back to README](../README.md)

## In short

For now there is no theme management mechanism in Sourceml.

If you want to change CSS rules, or HTML code, you will have to make your changes
in Sourceml files.

## What Sourceml files are used in the default rendering ?

### Public files

If you want to change public files, like css, javascript or image files, you will
find all those files in the **public** directory.

#### Css files

Regarding css rules, in most cases the file to edit will be one amoung the following :

* public/app/css/style.css
* public/sources/css/admin.css
* public/sources/css/sources.css

### Template files

Every piece of HTML code that Sourceml send to a browser is coming from a
[Twig](https://twig.symfony.com/) template file. Those files are located in the
**templates** folder, with a **.twig** extension. If you want to change the HTML
code, make your changes in the corresponding **.twig** file.

In the **templates** directory, twig files are stored in three main folders :

| folder | description |
| --- | --- |
| **App** | twig files for the global shape of the website |
| **Sources** | twig files for almost everything else |
| **JQFileUpload** | twig files for the upload forms |

Each of them containing files and sub-folders (...) with names that hopfully will be
helpfull.

A good start would be to take a look at the twig file that define de global HTML
structure :

* templates/App/base.html.twig