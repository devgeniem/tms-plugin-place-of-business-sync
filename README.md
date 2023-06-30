# TMS Place of Business Sync

Sync place of business CPT posts from Tampere.fi Drupal site to WordPress

## Installation

`composer require devgeniem/tms-plugin-place-of-business-sync`

## Functionality

Adds cron job to sync place of business from Tampere.fi Drupal site to WordPress.
Supported languages are FI and EN. Unsupported languages are synced from EN language.

### Syncing with WP CLI

```
wp sync-place-of-business [--from=<from>] [--to=<to>]

OPTIONS

  [--from=<from>]
    The language to import from.
    ---
    default: fi
    ---

  [--to=<to>]
    The language to import to.
    ---
    default: fi
    ---
```

## Contributing

Contributions are highly welcome!
Just leave a pull request of your awesome well-written must-have feature.
