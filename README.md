# Kabuto

Kabuto is a PHP template engine.

## Syntax

Components use the `k-*` prefix by default:

```html
<k-alert type="error">Failed to save.</k-alert>
<k-store:provide name="cart" :value="$cart"><k-cart-summary /></k-store:provide>
```

Use a custom prefix by passing a configured parser to the engine:

```php
new TemplateEngine($renderer, parser: new Parser(componentPrefix: 'ui-'));
```

The legacy `x-*` component prefix is not enabled by default, so HTML attributes
such as `x-data` render as normal attributes. To parse legacy component tags,
configure `new Parser(componentPrefix: 'x-')` explicitly.

## Development

```sh
composer install
composer test
composer analyse
composer lint
composer format
```
