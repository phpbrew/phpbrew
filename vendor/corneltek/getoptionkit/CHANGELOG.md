CHANGELOG
==================

## v2.2.4 - Fri Oct  2 15:53:33 2015

- ContinuousOptionParser improvements.

## v2.2.2 - Tue Jul 14 00:15:26 2015

- Added PathType.

## v2.2.1 - Tue Jul 14 00:17:19 2015

Merged PRs:

- Commit 7bbb91b: Merge pull request #34 from 1Franck/master

   added value type option(s) support, added class RegexType

- Commit 3722992: Merge pull request #33 from 1Franck/patch-1

   Clarified InvalidOptionValue exception message


## v2.2.0 - Thu Jun  4 13:51:47 2015

- Added more types for type constraint option. url, ip, ipv4, ipv6, email by @1Franck++
- Several bug fixes by @1Franck++



## v2.1.0 - Fri Apr 24 16:43:00 2015

- Added incremental option value support.
- Fixed #21 for negative value.
- Used autoloading with PSR-4

## v2.0.12 - Tue Apr 21 18:51:12 2015

- Improved hinting text for default value
- Some coding style fix
- Added default value support
- Updated default value support for ContinuousOptionParser
- Added getValue() accessor on OptionSpec class
- Merged pull request #22 from Gasol/zero-option. @Gasol++
    - Fix option that can't not be 0
