# sourceml
A PHP/MySQL CMS for music publication

## description
Sourceml is a web application for music publication. Install Sourceml on web server, login to your account and publish albums, songs and tracks.

This v3 version of Sourceml, based on [Symfony](https://symfony.com/) 3.4, is coming from the [v2 version of Sourceml](http://gitlab.dj3c1t.com/dj3c1t/sourceml), based on Symfony 2.

## installation

### requirements

php >= 7.0.8

For waveforms, php will try to call the [sox](http://sox.sourceforge.net) system command. If it fails, audio players will still be available, but without waveforms.

### clone the repository

```shell
git clone git@github.com:dj3c1t/sourceml.git
```

### install composer libraries

If not done yet, install [Composer](https://getcomposer.org), then cd to the project folder and run :

```shell
composer install
```

### configure the server host

Configure a serveur host with the **public** directory (inside the sourceml project) as the document root.

> :warning: The **.env** file will contain the database connection informations and **MUST NOT be public**.

If for some reason the server document root is set to the **sourceml** folder, Sourceml will still work, with a **/public** prefix in the URI. But make sure the **.env** file has a forbidden access from HTTP.

### run the installer

Visit your Sourceml host with a browser. You should see the install form.

Set your database connection informations, choose a title for the website and a user name to start with, then click the install button.

If you see a success page, you now have a new (empty) Sourceml installation. Login with your user name to access your sourceml account and add authors, albums and tracks...

