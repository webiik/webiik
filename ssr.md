---
layout: default
title: Ssr
permalink: /ssr/
---
# Ssr
## Service Name in Container
Webiik\Ssr\Ssr

## Purpose
Server-side rendering of javascript UI components.  

## Definition
It is defined in `private/config/container/services.php`. By default, Ssr service is defined to render javascript using the PHP extension V8JS and to utilize React.

> You can change the definition to use NodeJs or different UI library than React.

## Usual Usage
By the service [TemplateHelpers](/template-helpers) and TwigExtension.

## Documentation
[Read documentation](https://github.com/webiik/ssr) to learn more about Ssr component.