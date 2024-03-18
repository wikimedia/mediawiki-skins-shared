# About

This repository provides common code, that is shared between skins but not mature enough to be upstreamed to MediaWiki core or a Composer library.

It is currently used by the Minerva and Vector 2022 skins.

It should not be installed in the mediawiki/skins folder - it is intended only for use as a submodule in your skin.


# Installation

In your skin you can install and make use of the code by doing the following:
```
git submodule add https://gerrit.wikimedia.org/r/mediawiki/skins/shared
```
