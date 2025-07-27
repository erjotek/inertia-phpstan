# Inertia PHPStan Extension

A PHPStan extension that validates Inertia.js page existence on disk when used in Laravel controllers.

## Features

- Validates `Inertia::render()` static calls
- Validates `inertia()` helper function calls
- Validates `$this->inertia()` method calls in controllers
- Supports multiple page directory configurations
- Supports various file extensions (.vue, .jsx, .tsx, .js, .ts)
- Handles both dot notation (`Auth.Login`) and slash notation (`Auth/Login`) for page names

## Installation

```bash
composer require --dev adrum/inertia-phpstan
```

## Configuration

Add the extension to your `phpstan.neon`:

```neon
includes:
    - vendor/adrum/inertia-phpstan/extension.neon
```

## Usage

The extension will automatically validate that Inertia pages exist on disk when analyzing your controllers:

```php
class UserController extends Controller
{
    public function dashboard()
    {
        // ✓ Will pass if resources/js/Pages/Dashboard.vue exists
        return Inertia::render('Dashboard');
    }

    public function profile()
    {
        // ✗ Will fail if resources/js/Pages/Profile/Edit.vue doesn't exist
        return Inertia::render('Profile/Edit');
    }
}
```

## Page Directory Configuration

The extension looks for pages in these directories by default:

- `resources/js/Pages`
- `resources/js/pages`
- `resources/ts/Pages`
- `resources/ts/pages`
- `resources/vue/Pages`
- `resources/vue/pages`
- `resources/react/Pages`
- `resources/react/pages`

## Supported File Extensions

- `.vue`
- `.jsx`
- `.tsx`
- `.js`
- `.ts`

## Example

See the `examples/` directory for a sample controller and PHPStan configuration.
