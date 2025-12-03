# Homepage Configuration

The homepage of your node can be customized to display a set of clickable application blocks. This is controlled by the `homepage_apps` array in your `config.inc.php` file. By modifying this array, you can add, remove, or reorder the apps to fit your needs.

## Default Configuration

If the `homepage_apps` array is not defined in your configuration, it will default to showing only the "Explorer" app. The default configuration, as defined in `config.default.php`, is as follows:

```php
$_config['homepage_apps'] = [
    "explorer" => [
        "title" => "Explorer",
        "url" => "/apps/explorer",
        "icon_type" => "fa",
        "icon" => "fas fa-binoculars",
        "condition" => true
    ],
    "miner" => [
        "title" => "Miner",
        "url" => "/apps/miner",
        "icon_type" => "fa",
        "icon" => "fas fa-hammer",
        "condition" => "miner_enabled"
    ],
    "dapps" => [
        "title" => "Dapps",
        "url" => "/dapps.php?url={dapps_id}",
        "icon_type" => "fa",
        "icon" => "fas fa-cubes",
        "condition" => "dapps_enabled",
        "tooltip" => "Decentralized apps"
    ],
    "exchange" => [
        "title" => "Exchange",
        "url" => "https://klingex.io/trade/PHP-USDT?ref=3436CA42",
        "icon_type" => "img",
        "icon" => "https://klingex.io/symbol.svg",
        "target" => "_blank",
        "condition" => true,
        "tooltip" => "Exchange"
    ],
    "docs" => [
        "title" => "Docs",
        "url" => "/apps/docs",
        "icon_type" => "fa",
        "icon" => "fas fa-file-alt",
        "condition" => true
    ]
];
```

## App Properties

Each app in the array is an associative array with the following properties:

-   `title`: (string) The text displayed on the app block.
-   `url`: (string) The URL the app block links to.
-   `icon_type`: (string) The type of icon to use. Can be `'fa'` for a Font Awesome icon or `'img'` for an image URL.
-   `icon`: (string) The identifier for the icon. For `'fa'`, this is the Font Awesome class (e.g., `fas fa-binoculars`). For `'img'`, this is the full URL to the image.
-   `condition`: (mixed) Determines whether the app block is displayed.
    -   `true`: The app is always displayed.
    -   `'miner_enabled'`: The app is displayed only if mining is enabled on the node.
    -   `'dapps_enabled'`: The app is displayed only if Dapps are enabled on the node.
-   `target`: (string, optional) The `target` attribute for the link. Use `'_blank'` to open the link in a new tab.
-   `tooltip`: (string, optional) Text to display as a tooltip when the user hovers over the app block.

## Customization

To customize the homepage apps, you should copy the `homepage_apps` array from `config.default.php` to your `config.inc.php` file and modify it.

### Adding a New App

To add a new app, simply add a new entry to the array. For example, to add a link to a block explorer:

```php
$_config['homepage_apps']['my-explorer'] = [
    "title" => "My Explorer",
    "url" => "https://my-custom-explorer.com",
    "icon_type" => "fa",
    "icon" => "fas fa-search",
    "condition" => true,
    "target" => "_blank"
];
```

### Removing an App

To remove an app, the recommended way is to copy the `homepage_apps` array to your `config.inc.php` and then either delete the entire block for the app you wish to remove, or comment it out.

**Example: Commenting out the "Exchange" App**
```php
    // "exchange" => [
    //     "title" => "Exchange",
    //     "url" => "https://klingex.io/trade/PHP-USDT?ref=3436CA42",
    //     "icon_type" => "img",
    //     "icon" => "https://klingex.io/symbol.svg",
    //     "target" => "_blank",
    //     "condition" => true,
    //     "tooltip" => "Exchange"
    // ],
```
