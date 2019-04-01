---
layout: default
title: Predefined Services
permalink: /predefined-services/
---
# Predefined Services
Webiik includes few predefined services to save your time. These services are optional and it's only up to you if you use them or not.

## Removing Predefined Services
If you don't need some of the predefined services, it's a good idea to remove it. But before you make it, be sure the other services don't rely on the service you want to remove. To completely remove the service follow these steps:

* remove service dependencies from **private/composer.json**
* remove service definition and dependencies from **private/app/config/container/services.*** or **private/app/config/container/models.***
* remove eventual service configuration from **private/app/config/resources.***
* in case of Translation Service remove **LoadTranslations** middleware from **private/app/config/middleware/middleware.php** 