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
By default, View Service is defined to utilize Twig template engine, read templates from **app/private/frontend/views** and [Extensions](/extensions), write cache to **app/tmp/view** and to support Twig debug mode. It inherits configuration from the [Error Service](/error) to enable or disable Twig debug mode.

**Changing the template engine?**
* templates must be stored in **app/private/frontend/views**
* eventual cache files must be stored in **app/tmp/view**
* you should support loading templates from [Extensions](/extensions)  

## Usual Usage
In controller.

## Documentation
[Read documentation](https://github.com/webiik/components/blob/master/src/Webiik/View/README.md) to learn more about View component.