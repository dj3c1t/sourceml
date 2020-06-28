# Upgrade a Sourceml installation

[back to README](../README.md)

This page describes how to upgrade from a version 2 or 3 of Sourceml to the
lastest version. From version 1, there is a documentation on Sourceml's website that details all
the steps to
[upgrade from a version 1 to a version 2 of Sourceml](http://sourceml.com/index.php?id=23&e=pages/view/page) (fr).

## Upgrade from a Sourceml 3 installation

If your installation is already a version 3 of Sourceml, follow those steps :

##### Backup everything

Dump the database and copy all site's files and folders in a safe place.

##### Remove all files, keep the database

Double check that there is a copy of all files then delete them from the
website. Don't empty the database, keep the datas in here. Just remove all
files and folders.

##### Copy new Sourceml's files

Download and extract the files from the new Sourceml archive, but don't start a
new installation.

##### Restore the .env files

From the backup, restore the **.env** file. Replace the one in the new
installation with this one.

##### Restore the medias folder

Then restore the **medias** folder :

```
public/medias
```

##### Restore the themes

Finally, and if some [themes](change.ui.md) have been added, restore the
**themes** folders :

```
public/themes
```

and

```
templates/themes
```

And you should have your Sourceml installation upgraded.

## Upgrade from Sourceml 2 to Sourceml 3

If your installation is a version 2 of Sourceml, you can follow the steps above
to upgrade to a version 3, but with two differences, because neither Sourceml 2
have a theme mechanism, nor have a **.env** file.

### About themes

As there is no theme mecanism in Sourceml 2, all changes made on Sourceml's
default rendering will be lost (unless you adapt them into a
[new theme](change.ui.md)).

### About the .env file

In Sourceml 2, there is no **.env** file. So you will have to edit the default
one provided by Sourceml 3.

This file is edited by Sourceml's installer, and contains the database
connection informations and a parameter that says weither or not the installer
has to be run.

#### The database connection informations

Take a look, in the backup, at the **parameters.yml** file :

```
app/config/parameters.yml
```
This file contains the database connection informations, in a
[yaml](https://yaml.org/) syntax :

```
parameters:
    database_host: localhost
    database_port: null
    database_name: sourceml_db_name
    database_user: sourceml_db_user
    database_password: Sc7gWDUoYkTQQjdK
```

In the new **.env** file, find this line :

```
DATABASE_URL=mysql://db_user:db_password@localhost/db_name
```

And change **db_user**, **db_password**, **localhost** and **db_name** with the
informations found in the **parameters.yml** file.

With the above values, that would be :

```
DATABASE_URL=mysql://sourceml_db_user:Sc7gWDUoYkTQQjdK@localhost/sourceml_db_name
```
#### The installer status

Finally disable the Sourceml installer, to skip the install page.

At the end of the **.env** file, change :

```
SOURCEML_RUN_INSTALLER=true
```

To :

```
SOURCEML_RUN_INSTALLER=false
```

### Finish Sourceml's upgrade

Don't forget to restore your **medias** folder :

```
public/medias
```

And you should have your installation upgraded from Sourceml 2 to Sourceml 3.
