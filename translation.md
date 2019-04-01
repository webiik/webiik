---
layout: default
title: Translation
permalink: /translation/
---
# Translation
## Service Name in Container
Webiik\Translation\Translation

## Purpose
Translations, text formatting.  

## Configuration
Not available.

## Definition
By default, Translation Service is defined to return translations for the current language.

Webiik utilizes application middleware `Webiik\Middleware\Core\LoadTranslations:run` to automatically fill the Translation Service with texts from translation files according to the current route and language:

* translation files must be stored in **app/private/translations**
* each language must have own folder e.g. **app/private/translations/en**
* translation filename must match the route name. For example, when route name is **home**, translation filename will be **home.php**
* translation file must return an associative array
* translation file with name **_shared.php** will be always loaded
* same rules are valid for [Extensions](/extensions)

## Usual Usage
In controller. Used by LoadTranslations middleware.

## Documentation
[Read documentation](https://github.com/webiik/components/blob/master/src/Webiik/Translation/README.md) to learn more about Translation component.