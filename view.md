---
layout: default
title: View
permalink: /view/
---
# View
## Service Name in Container
Webiik\View\View

## Purpose
Template engine incorporation. Template rendering.  

## Configuration
Not available.

## Definition
By default, View Service is defined to utilize Twig template engine. It reads templates from **app/private/frontend/views** and [Extensions](/extensions), writes cache to **app/tmp/view** and supports Twig debug mode.

## Extras
Twig in Webiik comes with these additional functions and variables.

###getRoute
```
getRoute(): string
```
getRoute() returns current route.
```twig
{{ getRoute() }}
```

###getURL
```
getURL(string $route, array $parameters = [], string $lang = WEBIIK_LANG): string
```
getURL() returns URL by route.
```twig
{{ getURL('home') }}
```

###_t
```
_t(string $key, array|bool|null $context = null): string|array
```
_t() returns route related translations by key.
```twig
{{ _t('message', {'name': 'Tom'}) }}
```

###Variables
```twig
{{ WEBIIK_DEBUG }}
{{ WEBIIK_LANG }}
{{ WEBIIK_BASE_URI }}
{{ WEBIIK_BASE_URL }}
```
Read more about the meaning of these variables [here](/constants).


## Changing the template engine
You can change the template engine by [updating the configuration](/container) in **private/app/config/container/services.php**.

You should follow these rules: 
* templates should be stored in **app/private/frontend/views**
* eventual cache files should be stored in **app/tmp/view**
* you should support loading templates from [Extensions](/extensions)  

## Usual Usage
In controller.

## Documentation
[Read documentation](https://github.com/webiik/components/blob/master/src/Webiik/View/README.md) to learn more about View component.