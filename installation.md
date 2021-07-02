---
layout: default
title: Installation
permalink: /installation/
---
# Installation

## Requirements
* PHP >= 7.2
* [Composer](https://getcomposer.org/doc/00-intro.md)
* Web-server (Apache, Nginx or built-in PHP web server) 

## Installation Steps
1. Open your terminal and create a new Webiik project by issuing the Composer `create-project`:
   ```bash
   composer create-project webiik/webiik your-project-name
   ```
2. Configure your web-server to serve content from `your-project-name/public` folder or change `baseUri` configuration in `your-project-name/private/config/app.php`.

## Production
* Optimize autoloader and never install development dependencies: `composer install --optimize-autoloader --no-dev`
* Never use the built-in PHP web-server
* Make you sure your **private** folder is not publicly accessible
* Set write permissions to `your-project-name/private/tmp` and `your-project-name/public/_site` folders and sub-folders.
* Use SSL 