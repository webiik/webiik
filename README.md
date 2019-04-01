Webiik
======
Webiik is the simplest PHP framework. Its code base has only 1 062 logical lines of code. Use&nbsp;Webiik to regain control of your application's code. 
 
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

Documentation
-------------
Become the Webiik master within a few days, just read the straightforward [documentation][1].

Contributing
------------
I appreciate everyone who considers contributing. Currently, I’m looking for: 

*  Grammar corrections and proofreading of `*.md` files.

If you decide to contribute, send pull request or contact me at jiri@mihal.me or [@JiriMihal](https://twitter.com/jirimihal).

Security Issues
---------------
If you discover a security vulnerability within Webiik, please send me an email with the vulnerability description to jiri@mihal.me.

License
-------
Webiik and all its components are provided under [MIT license][2]. 

[1]: https://www.webiik.com
[2]: http://opensource.org/licenses/MIT