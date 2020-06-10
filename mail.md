---
layout: default
title: Mail
permalink: /mail/
---
# Mail
## Service Name in Container
Webiik\Mail\Mail

## Purpose
Sending emails.  

## Configuration
Available in `private/config/resources.php`.

## Definition
It is defined in `private/config/container/services.php`. By default, Mail Service is defined to utilize [PHPMailer](https://github.com/PHPMailer/PHPMailer) for sending emails.  

## Usual Usage
In [route controller](/routing). Also, it is used by [Log Service](/log).

## Documentation
[Read documentation](https://github.com/webiik/mail) to learn more about Mail component.