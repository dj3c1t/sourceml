# Sourceml

A Php/MySql music sharing CMS

## Description

Sourceml is a music sharing web application. Install Sourceml on a web server,
login to your account and publish albums, songs and tracks.

This v3 version of Sourceml is based on [Symfony](https://symfony.com/) 3.4

Older versions are available in [Sourceml archives](http://www.sourceml.com/archives/).

## Installation

### Requirements

* php >= 7.0.8
* For waveforms generation, php will try to call the [sox](http://sox.sourceforge.net)
utility via a system command. If it fails, audio players will still be available,
but without waveforms.

### Get a copy of the repository

Download the zip archive from Github or clone a local copy :

```shell
git clone git@github.com:dj3c1t/sourceml.git
```

### Install composer libraries

If not installed yet, install [Composer](https://getcomposer.org)

then cd to the **sourceml** folder and run :

```shell
composer install
```

### Configure the server host

Configure a serveur host with the **public** directory (inside the **sourceml**
folder) as the document root.

> :warning:
>
> The **.env** file will contain the database connection informations
> and **MUST NOT be public**
>


If for some reason the server document root is set to the **sourceml** folder,
Sourceml will still work, with a **/public** prefix in the URI.

#### But make sure the **.env** file isn't accessible from a browser.

### Run the installer

Visit your Sourceml host with a browser. You should see the install form.

Set your database connection informations, choose a title for the website and a
username to start with, then click the install button.

Wait a bit, for the tables to be created in the database, and if you see a success
message you sould now be able to login with your username, access your account and add authors, albums and tracks...

## Read more about Sourceml

* [A more detailed Sourceml presentation](docs/about.sourceml.md)
* [Change Sourceml's default rendering](docs/change.ui.md)
* [Sourceml's website (fr)](http://sourceml.com/)