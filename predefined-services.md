---
layout: default
title: Predefined Services
permalink: /predefined-services/
---
# Predefined Services
Webiik includes predefined services to save your time. These services are optional and it's only up to you if you use them or not.

## Removing Predefined Services
If you don't need some of the predefined services, it's a good idea to remove it. But before you make it, be sure the other services don't rely on the service you want to remove. To completely remove the service follow these steps:

* remove service dependencies from `composer.json`
* remove service definition and dependencies from `private/config/container/services` or `private/config/container/models`
* remove eventual service configuration from `private/config/resources`
* if you remove the Translation, remove also the `LoadTranslations` middleware from `private/config/middleware/middleware.php` 