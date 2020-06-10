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

## Definition
It is defined in `private/config/container/services.php`. By default, the service View is defined to utilize [Twig template engine](https://github.com/twigphp/Twig). It reads templates from `private/frontend` and writes cache to `private/tmp/view`. Also, it extends Twig with the set of additional [template functions](/template-helpers) and variables: 

### getRoute
```
getRoute(): string
```
getRoute() returns current route name.
```
{% raw %}
{{ getRoute() }}
{% endraw %}
```

### getURL
```
getURL(string $route, array $parameters = [], string $lang = WEBIIK_LANG): string
```
getURL() returns URL by route name.
```
{% raw %}
{{ getURL('home') }}
{% endraw %}
```

### _t
```
_t(string $key, array|bool|null $context = null): string|array
```
_t() returns route and language related translations by key.
```
{% raw %}
{{ _t('message', {'name': 'Tom'}) }}
{% endraw %}
```

### getCSS
```
getCSS(string $route): string
```
getCSS() returns HTML tags with route related CSS.
```
{% raw %}
{{ getCSS(getRoute()) }}
{% endraw %}
```

### getJS
```
getJS(string $route): string
```
getJS() returns HTML tags with route related JS.
```
{% raw %}
{{ getJS(getRoute()) }}
{% endraw %}
```

### reactComponent
```
reactComponent(string $name, array $props, array $options = []): string
```
reactComponent() returns HTML tags with React component.
```
{% raw %}
{{ reactComponent("Meow", []) }}
{% endraw %}
```

### Variables
```
{% raw %}
{{ WEBIIK_DEBUG }}
{{ WEBIIK_LANG }}
{{ WEBIIK_BASE_URI }}
{{ WEBIIK_BASE_URL }}
{{ WEBIIK_BASE_PATH }}
{% endraw %}
```
Read more about the meaning of these variables [here](/constants).

## Changing the template engine
[The View component](https://github.com/webiik/view) allows you to easily change the template engine. So if you don't want to use Twig, just change the definition of the service View.

Please, follow these rules: 
* template files MUST be stored in `private/frontend`
* cache files MUST be stored in `private/tmp/view`
* in templates, you MUST provide Webiik`s [template functions](/template-helpers)
* in templates, you MUST provide Webiik`s [constants](/constants)  

## Usual Usage
In [route controller](/routing).

## Documentation
[Read documentation](https://github.com/webiik/view) to learn more about View component.