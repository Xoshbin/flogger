# Flogger

**Flogger** is a beautiful and easy-to-use log viewer specifically designed for Filament admin panels. It provides a clean interface to view, filter, and manage your Laravel application logs directly from your Filament dashboard.

## Features

- **Integrated UI**: Seamlessly integrates with the Filament admin panel.
- **Log Viewing**: View daily log files with a clean, color-coded interface.
- **Filtering**: Automatically categorizes logs by level (e.g., Info, Error, Warning, Debug) with distinct styling.
- **Details**: Expand individual log entries to view the full stack trace or message.
- **Copy Functionality**: Easily copy full log details to your clipboard with a single click.
- **File Management**: Delete old log files directly from the viewer to free up space.
- **Navigation**: Easily navigate between different dates/log files.

## Installation

You can install the package via composer:

```bash
composer require xoshbin/flogger
```

## Usage

### Registering the Plugin

To use Flogger in your Filament panel, you need to register the `Flogger` plugin in your panel provider (e.g., `AdminPanelProvider`).

```php
use Xoshbin\Flogger\Flogger;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugin(Flogger::make());
}
```

Since Flogger automatically registers its service provider, this will enable the Log Viewer page in your specified panel.

### Accessing the Log Viewer

1.  Log in to your Filament Admin Panel.
2.  Navigate to the **Settings** group in the sidebar.
3.  Click on **Log Viewer**.

### Configuration

You can customize Flogger's behavior by publishing the configuration file:

```bash
php artisan vendor:publish --tag="flogger-config"
```

This will create a `config/flogger.php` file where you can specify patterns for files to exclude from the viewer (e.g., to ignore temporary schedule logs).

```php
'exclude_files' => [
    'schedule-*',
],
```

The Log Viewer works by reading standard Laravel log files located in `storage/logs`. Ensure your application is configured to use the `daily` or `single` log channel, although `daily` provides the best experience with the file selector.

If you need to publish assets (though usually handled automatically):

```bash
php artisan vendor:publish --tag="flogger-assets"
```

## Development

If you want to contribute or modify the package's styling:

1.  **Install dependencies**:
    ```bash
    npm install
    ```

2.  **Modifying Assets**:
    *   **Blade Templates**: Changes to `resources/views` are reflected immediately. No build step required (just refresh your browser).
    *   **CSS Styles**: If you modify `resources/css/flogger.css` or use new Tailwind classes in your views, you must recompile the CSS:
        ```bash
        npm run build
        ```
    *   The compiled CSS is located at `resources/dist/flogger.css` and is automatically registered by the service provider.

## Credits

-   [Khoshbin](https://github.com/Xoshbin)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
