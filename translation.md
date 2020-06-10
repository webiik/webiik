---
layout: default
title: Translation
permalink: /translation/
---
# Translation
## Service Name in Container
Webiik\Translation\Translation

## Purpose
Translations and text parsing.  

## Definition
It is defined in `private/config/container/services.php`. By default, the service Translation is defined to return translations related to [WEBIIK_LANG](/constants).

Webiik uses application middleware that automatically fills the Translation with content from translation files according to the current route and language:

* Translation files must be stored in `/private/translations`
* Each language must have own folder eg. `/private/translations/en`
* Translation filename must match the route name. For example, when route name is `home`, translation filename must be named `home.php`
* Every translation file must return an associative array
* The translation file `_app.php` is always loaded

## Usual Usage
In [route controller](/routing). Also, it is used by the [application middleware](/middleware) LoadTranslations.

## Documentation
[Read documentation](https://github.com/webiik/components/blob/master/src/Webiik/Translation/README.md) to learn more about Translation component.