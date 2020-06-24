# Upgrade a Sourceml installation

[back to README](../README.md)

This page describes how to upgrade from version 2 or 3 of Sourceml to the
lastest version.

From version 1, there is a dedicated documentation on Sourceml's website that
details all the steps to
[upgrade from version 1 to version 2](http://sourceml.com/index.php?id=23&e=pages/view/page) (fr).

## Upgrade a Sourceml 3 installation

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

Finally, and if some [themes](docs/change.ui.md) have been added, restore the
**themes** folders :

```
public/themes
```

and

```
templates/themes
```

And you should have your Sourceml installation upgraded.

## Upgrade from version 2 to version 3

You can follow the steps for the version 3, to uprade from version 2 to version 3, with
few differences.

There is no **.env** file in Sourceml 2. And there is no theme mechanism.

### Edit the .env file

There is no **.env** file in version 2. So you will have to edit the one in the
new installation, and change two things in it. The database connection
informations and the Sourceml installer status.

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

Edit the new **.env** file, and change this line :

```
DATABASE_URL=mysql://db_user:db_password@localhost/db_name
```

Change **db_user**, **db_password**, **localhost** and **db_name** with the
informations from the **parameters.yml** file.

With the values from the above sample, that would be :

```
DATABASE_URL=mysql://sourceml_db_user:Sc7gWDUoYkTQQjdK@localhost/sourceml_db_name
```
#### The installer status

Finally disable the Sourceml installer, to skip the install page. To do so edit
the **.env** file and change (at the end of the file) :

```
SOURCEML_RUN_INSTALLER=true
```

To :

```
SOURCEML_RUN_INSTALLER=false
```
### About themes

Regarding themes and adjustments made on a version 2 of Sourceml, there is
nothing like a migration tool to get them working with a version 3.

However the code of versions 2 and 3 of Sourceml are very similar, and some
changes made on a v2 installation may need only little adjustments to work with
a v3. In short... HTML code haven't changed but pathes have changed.
