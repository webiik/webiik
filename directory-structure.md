---
layout: default
title: Directory Structure
permalink: /directory-structure/
---
# Directory Structure
Fresh Webiik application has the following directory structure: 
```console
.
..
├── private (this folder must be not accessible from the web)
|   ├── app
|   |   ├── code (your code, PSR-4 autoloaded)
|   |   |   ├── controllers (route controllers, namespace Webiik\Controller)
|   |   |   |   ├── Home.php
|   |   |   |   ├── P404.php
|   |   |   |   └── P405.php
|   |   |   ├── middleware (namespace Webiik\Middleware)
|   |   |   |   └── Core
|   |   |   |       ├── LoadTranslations.php (loads translations)
|   |   |   |       └── SetSecurityHeaders.php (sets basic security headers)
|   |   |   ├── models (namespace Webiik\Model)
|   |   |   ├── components
|   |   |   └── traits (namespace Webiik\Trait)
|   |   ├── config
|   |   |   ├── container (definition of services)
|   |   |   |   ├── models.php
|   |   |   |   └── services.php
|   |   |   ├── routes
|   |   |   |   └── routes.en.php (definition of routes)
|   |   |   ├── middleware
|   |   |   |   └── middleware.php (registration of middleware)
|   |   |   ├── app.php (configuration of app)
|   |   |   └── resources.php (configuration of services, models and middleware)
|   |   ├── frontend (templates and assets)
|   |   |   ├── assets
|   |   |   |   └── app
|   |   |   |       ├── font (css font files)
|   |   |   |       ├── img (images)
|   |   |   |       ├── scss (scss files)
|   |   |   |       |   └── home.scss (SCSS file included in Webpack entries)
|   |   |   |       ├── js (js or ts files)
|   |   |   |       |   └── home.tsx (TypeScript file included in Webpack entries)
|   |   |   |       ├── main.scss (SCSS file included in Webpack entries)
|   |   |   |       ├── main.tsx (TypeScript file included in Webpack entries)
|   |   |   |       ├── package.json (NPM file)
|   |   |   |       ├── postcss.config.js (Webpack's postcss-loader config)
|   |   |   |       ├── tsconfig.json (Webpack's ts-loader config)
|   |   |   |       └── webpack.config.js (Webpack config)
|   |   |   ├── base.twig (shared Twig template)
|   |   |   └── home.twig (home page Twig template)
|   |   ├── translation (files for middleware LoadTranslations)
|   |   |   └── en
|   |   |       ├── _shared.php (always loaded translations)
|   |   |       └── home.php (route name related translations)
|   |   └── app.php (initialization of the Webiik application)
|   ├── extensions (Webiik extensions)
|   ├── tmp (always store all temporary files here)
|   |   ├── logs (log files, error.log etc.)
|   |   ├── session (PHP sessions)
|   |   └── view (processed templates)
|   └── composer.json
├── public (this folder must be accessible from the web)
|   ├── assets (processed static files)
|   |   ├── app (static files related to Webiik app)
|   |   |   ├── css
|   |   |   ├── font
|   |   |   ├── img
|   |   |   └── js
|   └── index.php
└── .gitignore
```