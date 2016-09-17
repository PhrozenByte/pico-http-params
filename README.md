Pico HTTP Parameters
====================

This is the repository of Pico's official HTTP parameters plugin.

Pico is a stupidly simple, blazing fast, flat file CMS. See http://picocms.org/ for more info.

`PicoHttpParams` allows theme developers to access HTTP GET and HTTP POST parameters in Twig templates using the `url_param` resp. `form_param` functions. This makes developing awesome themes for your Pico website easier than ever before.

This plugin basically wraps around [PHP's `filter_var()` function](https://secure.php.net/manual/en/function.filter-var.php). Here's a excerpt from PHP's documentation about the [filter extension](https://secure.php.net/manual/en/intro.filter.php):

> This extension filters data by either validating or sanitizing it. This is especially useful when the data source contains unknown (or foreign) data, like user supplied input. For example, this data may come from an HTML form.
>
> There are two main types of filtering: validation and sanitization.
>
> [Validation](https://php.net/manual/en/filter.filters.validate.php) is used to validate or check if the data meets certain qualifications. For example, passing in `FILTER_VALIDATE_EMAIL` will determine if the data is a valid email address, but will not change the data itself.
>
> [Sanitization](https://php.net/manual/en/filter.filters.sanitize.php) will sanitize the data, so it may alter it by removing undesired characters. For example, passing in `FILTER_SANITIZE_EMAIL` will remove characters that are inappropriate for an email address to contain. That said, it does not validate the data.
>
> Flags are optionally used with both validation and sanitization to tweak behaviour according to need. For example, passing in `FILTER_FLAG_PATH_REQUIRED` while filtering an URL will require a path (like `/foo` in `http://example.org/foo`) to be present.

<small>– [Copyright](https://secure.php.net/manual/en/copyright.php) © 1997-2016 [The PHP Documentation Group](https://secure.php.net/credits.php), released under the [Creative Commons Attribution 3.0](https://creativecommons.org/licenses/by/3.0/) license</small>

Install
-------

Just [download the latest release](https://github.com/PhrozenByte/pico-http-params/releases/latest) and upload the `PicoHttpParams.php` file to the `plugins` directory of your Pico installation (e.g. `/var/www/html/pico/plugins/`). The plugin is also available on [Packagist.org](https://packagist.org/packages/phrozenbyte/pico-http-params) and may be included in other projects via `composer require phrozenbyte/pico-http-params`. The plugin requires Pico 1.0+

This plugin is a backport of an feature that will be included in Pico's core starting with Pico 1.1. Even though this plugin is compatible with Pico 1.1 (and later), it doesn't provide any functionality when used together with Pico 1.1 (and later). Therefore you can safely remove this plugin on Pico 1.1 installations if no other installed plugin depends on it. However, you are not required to. Put briefly, manually installing this plugin makes sense with Pico 1.0 only.

Config
------

You can't configure this plugin, it's a utility plugin for theme developers.

Usage
-----

**Heads up!** Input validation is hard! Always validate your input data the most paranoid way you can imagine. Always prefer validation filters over sanitization filters; be very careful with sanitization filters, you might create cross-site scripting vulnerabilities!

The `PicoHttpParams::getUrlParameter()` function resp. the `url_param` Twig function and the `PicoHttpParams::getFormParameter()` function resp. the `form_param` Twig function all accept the following parameters:

| Variable Type | Parameter Name | Description |
| ------------- | -------------- | ----------- |
| `mixed` | `$name` | name of the HTTP GET or HTTP POST variable |
| `int` \| `string` | `$filter = ''` | ID (int) or name (string) of the filter to apply; if omitted, all functions will return `false` |
| `mixed` \| `array` | `$options = null` | either a associative array of options to be used by the filter (e.g. `[ 'default': 42 ]`), or a scalar default value that will be returned when the HTTP GET or HTTP POST variable doesn't exist (optional) |
| `int` \| `string` \| `int[]` \| `string[]` | `$flags = null` | either a bitwise disjunction of flags or a string with the significant part of a flag constant (the constant name is the result of `FILTER_FLAG_` and the given string in ASCII-only uppercase); you may also pass an array of flags and flag strings (optional) |

With a validation filter passed in, all functions return the validated value of the HTTP GET or HTTP POST parameter, or, provided that the value wasn't valid, either the given default value or `false`. With a sanitization filter passed in, all functions return the sanitized value of the HTTP GET or HTTP POST parameter. If the HTTP GET or HTTP POST variable doesn't exist, all functions will always return either the provided default value or `null`.

Examples
--------

Pass the boolean HTTP GET parameter `expand` to expand a details section in your template:

```twig
<a href="{{ current_page.id|link('expand=yes') }}">Learn more...</a>

{% if url_param('expand', 'boolean') %}
    You're learning more right now! Isn't that great!?
{% endif %}
```

Ask a user about "the answer" using a HTML form and store his decision in the Twig variable `the_answer`. Use a [regular expression](https://en.wikipedia.org/wiki/Regular_expression) to allow just values that are actually present in the HTML form.

```twig
<form action="" method="POST">
    <label for="the_answer">What is the answer?</label>
    <select id="the_answer" name="the_answer">
        <option></option>
        <option value="band">a Northern Irish hard rock and blues-rock band</option>
        <option value="42">42</option>
        <option value="what">What the hell are you talking about?</option>
    </select>
    <input type="submit" />
</form>

{% set the_answer = form_param('the_answer', 'validate_regexp', { 'regexp': '/^(band|42|what)$/' }) %}
```

Ask a user how much he makes a year and claim that his amount is either sad or ridicilous because you make twice as much a year. Use the Twig variable `amount` and let the parameter default to `0`. Use the `FILTER_VALIDATE_FLOAT` (`float`) filter, but tweak its behaviour by passing the `FILTER_FLAG_ALLOW_THOUSAND` flag - this allows the user to enter their amount with a thousand separator (e.g. `12,345.00`).

```twig
<form action="" method="GET">
    <label for="amount">How much do you make a year?</label>
    <input id="amount" name="amount" type="text" />
    <input type="submit" />
</form>

{% set amount = url_param('amount', 'float', 0, 'allow_thousand') %}
{% if amount > 0 %}
    {% if amount < 10 %}
        You make just {{ amount }} cat pictures a year? Oh, that's sad... :-(
    {% else %}
        Impressive... Not! This is ridiculous! I make {{ amount * 2 }} cat pictures a year!
    {% endif %}
{% endif %}
```
