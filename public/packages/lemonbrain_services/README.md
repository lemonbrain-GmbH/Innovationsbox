# Lemonbrain Services Package

## Table of contents

1. [Versions](#versions)
1. [Back-up c5 CLI](#back-up-c5-cli)
1. [Mail Overrider](#mail-overrider)
   - [Usage](#usage)
1. [CONST Helpers](#const-helpers)
1. [Telephonelink Helper](#telephonelink-helper)
1. [E-Mail Address Helper](#e-mail-address-helper)
1. [Purify, Escaping, Sanitize Helper](#purify-escaping-sanitize-helper)
1. [PHP Dateformatter](#php-dateformatter)
1. [Javascript vh calculator](#javascript-vh-calculator)
1. [Javascript uriParam](#javascript-uriparam)
1. [Javascript fade](#javascript-fade)
1. [Javascript HTML Sanitizer](#javascript-html-sanitizer)
1. [Async ReCaptcha](#async-recaptcha)
1. [lb.util.swipe](#lbutilswipe)
1. [Lemonbrain modules (lb)](#lemonbrain-modules-lb)
1. [Task Automation](#task-automation)
1. [Using Bun to Build Typescript](#bun)
1. [Using Output Buffer Decorator](#outputBuffer)

Package to customise site blocks.

<h3 id="versions">Versions</h3>

While there are some braking changes from v3.x.x to v4.x.x, the space on v5.x.x is for concrete >v9 and PHP8. The v5 stuff will live on the `concrete-v9` branch for now.

<h3 id="back-up-c5-cli">Back-up c5 CLI</h3>

_All variables are taken from `.env` file_

Please see the `.env.dist.example` file for reference.

The `.env.dist` file is the skeleton file, to be filled in. Please copy and rename .`env`

Added the following to the c5 CLI options:

| Command                                  | Description                                                                                                                                                                                         |
| ---------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| lemonbrain:backup:archive                | Archives the backup directory and then deletes the directory after compressing                                                                                                                      |
| lemonbrain:backup:bundle                 | Start backup process. Dump the database, copy files and directories. <br/>Note:`composer.json` and `composer.lock`, `.htacces` and also `robots.txt` files will be saved if listed in the .env file |
| lemonbrain:backup:copy-files-directories | Back up your site's files and directories, listed as arrays as your dot env file.                                                                                                                   |
| lemonbrain:backup:mkdir                  | Create a directory for the files and directories.                                                                                                                                                   |
| lemonbrain:backup:mysqldump              | Back up your site's database.                                                                                                                                                                       |

<h3 id="mail-overrider">Mail Overrider</h3>

_(author to blame: donat)_

To override some Mail templates from a Package, some Magic is required. lemonbrain_services hijacks the MailService and
substitutes the loading of the template with a function that can read from a config to load specific templates from
packages.

<h4 id="usage">Usage:</h4>

Write to the app config wich template should be loaded from what pacakge. You might want to do this in your package
controller:

`Config::save('mail_overrider.template.forgot_password', $this->pkgHandle);`

This example will load the 'forgot_password' from the package it is called from. The template has to be there, there is
no fallback if it does not exist: `/package/mail/forgot_password.php`

<h3 id="const-helpers">CONST Helpers</h3>

Helper constants to build SQL-Queries without typo mistake, cause of prebuild support.
`\Helpers\Constants\Concrete`
`\Helpers\Constants\Sql`

<h3 id="telephonelink-helper">Telephonelink Helper</h3>

`App::make(Helpers\Links\Telephone::class)->transformToLink(+41 xx xxx xx xx);`

usage:

```php
<?= App::make(Helpers\Links\Telephone::class)->transformToLink(
    '+41 55 640 12 34',
    t('Tel Link Title'),
    t('Tel Link DisplayText'),
    'tel-link-class',
    'telephone'
) ?>
```

where the last parameter is the itemprop value of a meta tag that will be created if that parameter is present. it would look like this: (needs an enclosing tag with `itemscope` and `itemtype="http://schema.org/ContactPoint"` or something to be schema.org compliant)

```html
<meta itemprop="telephone" content="+41 55 640 12 34" />
```

<h3 id="e-mail-address-helper">E-Mail Address Helper</h3>

`\Helpers\Links\EmailAddressObfuscated::findAndReplaceWithLinks($contentWithMailaddressAsSimpleText);`

```
$this->requireAsset('javascript', 'spa');
$this->requireAsset('javascript', 'spa-util');
$this->requireAsset('javascript', 'spa-util-mailAddr');
```

usage:

```php
<?= App::make(Helpers\Links\EmailAddressObfuscated::class)->findAndReplaceWithLinks(
    'text with email@address.com and other stuff',
    t('Email Display Text'),
    'email-link-class',
) ?>
```

```php
<?= App::make(Helpers\Links\EmailAddressObfuscated::class)->transformToLink(
    'email@address.com',
    t('Email Link Title'),
    t('Email DisplayText'),
    'email-link-class',
    'email',
    '?subject=Subject&body=Body'
) ?>
```

where the seciond to last parameter is the itemprop value of a meta tag that will be created if that parameter is present. it would look like this: (needs an enclosing tag with `itemscope` and `itemtype="http://schema.org/ContactPoint"` or something to be schema.org compliant)

```html
<meta itemprop="email" content="email@address.com" />
```

you want to use this to obfuscate the mail address throughout.

<h3 id="purify-escaping-sanitize-helper">Purify, Escaping, Sanitize Helper</h3>

```
echo App::make(\Helpers\HTML::class)->allow_only('This is <br> and so on', array("br"));
echo App::make(\Helpers\HTML::class)->allow_only('This is <b>bold</b> and <a href="http://www.#links">this</a> is <i>italic</i>.', array("b","a"));
```

- on the client when we parse something to the html-dom. we use one of this two function, if possible always h() or the
  new one
- when we get inputs from the client on the server. as sanitizer from forms or other ajax requests. always Input
  Filter (which are not deprecated, <https://www.php.net/manual/en/book.filter.php>) or h() or then the new one
- and when we build a sql request manually as a string. but here you could also use the querybuilder it has it
  implemented already. so use querybuilder or h() or the new one

<h3 id="php-dateformatter">PHP Dateformatter</h3>

`echo App::make(\Helpers\DateFormat::class)->format($date, $pattern)`

<h3 id="javascript-vh-calculator">Javascript vh calculator</h3>

If you require `$this->requireAsset('javascript', 'lb-shell')` the on resize handler will calculate you a variabla which
also works on ios `document.documentElement.style.setProperty('--vh', '${vh}px');` use it like this `var(--vh, 1vh)` in
your css

<h3 id="javascript-uriparam">Javascript uriParam</h3>

same API as the old uriAnchor #! beside that configModule is not anymore needed and there is no dependency to other
jquery libaries (jquery.uriAnchor)
`$this->requireAsset('javascript', 'lb-util-uriParam');`

- initModule: call before use to init the module
- changeAnchorPart: use to change the url params. use key value
  map `{menu: 'open'}` [arg_link: to open it on a new main url, goesToHisotry: to exlclude the call from history api]
- registerFunction: register a listener `registerFunction('menu', callbackFunction)`
- removeFunction: remove listener
- getCurrentValue: get the current value from the url for a key `getCurrentValue('menu')`,

<h3 id="javascript-fade">Javascript fade</h3>

show and hide fading function, i'm sure you will get it :)
`$this->requireAsset('javascript', 'lb-util-fade');`

<h3 id="javascript-html-sanitizer">Javascript HTML Sanitizer</h3>

Mainly to sanitize ajax fetched html-content (wysiwyg editor and the likes).

usage:
```js
  const sanitizedHTMLtoAttachViaInnerHTML = sanitizeHTML(unpredictableHTMLstring, {
    allowedTags: ['b', 'i', 'em', 'strong', 'a', 'p', 'br', 'ul', 'ol', 'li'],
    allowedAttrs: {
      'a': ['href', 'title', 'target', 'rel'],
      'img': ['src', 'alt', 'title', 'width', 'height']
    }
  });

```

There is also the method `sanitizePictureHTML` that can be used on fetched `<picture>` elements (output from ImageBrewer)to make sure only allowed tags and attributes are kept. Set all parameters like alt-text and so on in the call to the ImageBrewer.

usage:
```js
  const sanitizedPictureHTMLtoAttachViaInnerHTML = sanitizePictureHTML(somewhatUnpredictablePictureHTMLstringFromImageBrewer);
```

<h3 id="async-recaptcha">Async ReCaptcha</h3>

for example service 7000

<h3 id="lbutilswipe">lb.util.swipe</h3>

We can use swipe utility with binding event types to html elements (formerly it was global window object). when getting
the event on the specified element, it executes the handler.

example from tattoo

    window.spa.util.swipe.initModule (
      htmlElementMap.myElement,
      EVENTS.SWIPE_LEFT,
      _swipeLeftHandler);

<h3 id="lemonbrain-modules-lb">Lemonbrain modules (lb)</h3>

<h4>Cookies block</h4>

Please use `lemonbrain_services` v3.1.0, when using the lb modules in your package e.g.

```php
$this->requireAsset('javascript', 'lb');
$this->requireAsset('javascript', 'lb-util');
...
```

As the older version (< v3.1.0) of `lemonbrain_services` require `spa`,

```php
$this->requireAsset('javascript', 'spa');
$this->requireAsset('javascript', 'spa-util');
$this->requireAsset('javascript', 'spa-util-uriAnchor');
```

<h3 id="task-automation">Task Automation (v9+)</h3>

~~ConcreteCMS V9 requires a taskrunner to be called every minute to delegate the task automation. Exigo allows only url's to be CRON'd so hit `/taskrunner/minuteman` every minute:~~

<h3 id="bun">BUN</h3>
Documentation
https://bun.sh/docs/bundler

<h4>Installation</h4>
Istallation for cli use `curl -fsSL https://bun.sh/install | bash`

<h4>Build</h4>
Build the files `ts_build.sh`

<h4>Adding new Asset to the build script (ts_build.sh)</h4>
```
bun build ./js/src/accept_cookies/accept_cookies.ts --outdir ./js/min/accept_cookies --minify --watch --entry-naming [dir]/[name].min.[ext] &
bun build ./js/src/google.ts --outdir ./js/min --minify --watch --entry-naming [dir]/[name].min.[ext]
```

<h3 id="outputBuffer">Using Output Buffer Decorator</h3>
Sometimes you want the inc() output buffer as return value. Than it makes sense to use this decorator for it.

```
// 1) One-off capture
$html = OutputBuffer::capture([$this, 'inc'], 'view.php', ['foo' => 'bar']);

// 2) Reusable decorated callable
$incAsString = new OutputDecoratedCallable([$this, 'inc']);
$html = $incAsString('view.php', ['foo' => 'bar']);

// 3) Capture + also keep original return value (if any)
[$out, $ret] = (function () {
    $res = OutputBuffer::captureWithReturn([$this, 'inc'], 'view.php', ['foo' => 'bar']);
    return [$res['output'], $res['return']];
})();

```
