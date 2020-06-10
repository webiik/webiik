---
layout: default
title: Session
permalink: /session/
---
# Session
## Service Name in Container
Webiik\Session\Session

## Purpose
Securely manages PHP sessions.

> ⚠️ Always use this service to work with PHP sessions. Never use PHP sessions directly! 

## Configuration
Available in `private/config/resources.php`. Session cookie parameters are inherited from the service [Cookie](/cookie).

## Usual Usage
In [route controller](/routing).

## Documentation
[Read documentation](https://github.com/webiik/session) to learn more about Session component.