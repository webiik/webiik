---
layout: default
title: Webiik
---
# Introduction
<p class="intro">Webiik is the simplest PHP framework. Its code base has only 1 062 logical lines of code. Use&nbsp;Webiik to regain control of your application's code.</p> 

<a href="https://github.com/webiik" target="_blank"><img style="height: 30px;" src="https://img.shields.io/badge/-GitHub-black.svg?logo=github"/></a>
<a href="https://stackoverflow.com/questions/tagged/webiik" target="_blank"><img style="height: 30px;" src="https://img.shields.io/badge/-Stackoverflow-orange.svg"/></a>
 
| Framework | LLOC | Classes | Dependencies | Req/s |
| :-------- | ---: | ------: | -----------: | ----: |
| Webiik  | 10 765 (1 717*) | 505 (54*) | 22 (18*) | 1 239** (1 781*) |
| Slim | 4 797 | 168 | 9 | 998 |
| Lumen | 55 594 | 1 558 | 50 | 1 339 |
| Silex | 88 962 | 3 029 | 45 | 736 |
| Laravel | 80 271 | 2 457 | 43 | 🐌 329 |
| Symfony | 🤯 182 807 | 5 510 | 83 | 456** |

\* without Twig and PHPMailer library, \** with Twig rendering

LLOC = logical lines of code incl. vendor folder. Benchmark was performed by `./hey -n 200 -c 50`. All measurements were performed on a fresh installation of each framework. Each framework was set to production mode. Development dependencies were not installed. 

Highlights
----------
* ⚡️ lightning fast
* 💋️ simple and lean
* 🧘‍♀️️ extra flexible
* 🌐 ready for multilingual apps
* 📉 resource-efficient

Contributing
------------
I appreciate everyone who considers contributing. Currently, I’m looking for: 

*  [Grammar corrections and proofreading](https://github.com/webiik/webiik/blob/master/grammar.md).

If you decide to contribute, please contact me at jiri@mihal.me or [@JiriMihal](https://twitter.com/jirimihal).

Security Issues
---------------
If you discover a security vulnerability within Webiik, please send me an email with the vulnerability description to jiri@mihal.me.

License
-------
Webiik and all its components provided under [MIT license][1]. 

[1]: http://opensource.org/licenses/MIT
