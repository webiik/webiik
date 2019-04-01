---
layout: default
title: Installation
permalink: /installation/
---
# Installation

## Requirements
* PHP >= 7.2
* [Composer](https://getcomposer.org/doc/00-intro.md)
* Web server (Apache, Nginx or built-in PHP web server) 

## Installation Steps
1. Open your terminal and create a new Webiik project by issuing the Composer `create-project`:
   ```bash
   composer create-project webiik/webiik webiik
   ```
2. Install project dependencies:
   ```bash
   cd webiik/private
   composer install
   ```
   
## Production
* Optimize autoloader and never install development dependencies: `composer install --optimize-autoloader --no-dev`
* Never use the built-in PHP web server
* Make you sure your **private** folder is not publicly accessible
* Use SSL 