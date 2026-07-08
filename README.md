Google Wallet Laravel
===============
This package provides Laravel integration for the [LauLamanApps Google Wallet Package][LauLamanAppsGoogleWalletPackage]:
generate "Add to Google Wallet" save links (RS256-signed JWTs) from your Laravel application.

Requirements
---
- PHP 8.1+
- Laravel 11.x, 12.x or 13.x

Installation
---
```bash
composer require laulamanapps/google-wallet-laravel
```

The service provider (`LauLamanApps\GoogleWalletLaravel\GoogleWalletServiceProvider`) is registered automatically via Laravel package auto-discovery.

Run Tests
---
```bash
composer install
./bin/phpunit
```

Configuration
---
Publish the config file:

```bash
php artisan vendor:publish --tag=google-wallet-config
```

This creates `config/google-wallet.php`:

| Key                     | Default                                       | Description                                                                       |
|-------------------------|-----------------------------------------------|-----------------------------------------------------------------------------------|
| `service_account`       | `env('GOOGLE_WALLET_SERVICE_ACCOUNT')`         | Path to the Google Cloud service account JSON key file used to sign the JWTs      |
| `origins`               | `[]`                                          | Origins allowed to render the save urls; leave empty to allow opening from anywhere |
| `callback.enabled`      | `env('GOOGLE_WALLET_CALLBACK_ENABLED', false)` | Expose the save/delete callback endpoint (see below)                              |
| `callback.issuer_id`    | `env('GOOGLE_WALLET_ISSUER_ID')`               | Your issuer id; required when callbacks are enabled (signatures are verified against it) |
| `callback.environment`  | `'production'`                                | `'production'` or `'test'`: which Google root signing keys to verify against      |
| `callback.route_prefix` | `'/google-wallet'`                            | Prefix for the callback route                                                     |

Add the ENV variable to your `.env` file:
```dotenv
###> laulamanapps/google-wallet-laravel ###
GOOGLE_WALLET_SERVICE_ACCOUNT=storage/keys/google-wallet.json
###< laulamanapps/google-wallet-laravel ###
```

Get a service account
---
1. Sign up for a Google Wallet issuer account in the [Google Pay & Wallet Console][GoogleWalletConsole] and note your issuer id.
2. Create a Google Cloud service account with the Wallet Object Issuer role and download its JSON key file.
3. Link the service account to your issuer account in the console.

Creating a save link
---
The package registers `LauLamanApps\GoogleWallet\SaveUrlFactory` as a singleton, configured with your service account and origins. Inject it anywhere:

```php
namespace App\Http\Controllers;

use LauLamanApps\GoogleWallet\Object\Barcode;
use LauLamanApps\GoogleWallet\Object\BarcodeType;
use LauLamanApps\GoogleWallet\Object\GenericClass;
use LauLamanApps\GoogleWallet\Object\GenericObject;
use LauLamanApps\GoogleWallet\PassPayload;
use LauLamanApps\GoogleWallet\SaveUrlFactory;

final class WalletPassController
{
    public function __construct(
        private readonly SaveUrlFactory $saveUrlFactory,
    ) {
    }

    public function save()
    {
        // Ids are '<issuerId>.<identifier>'
        $object = new GenericObject('3388000000012345678.member-0001', '3388000000012345678.membership');
        $object->setCardTitle('ACME Membership');
        $object->setHeader('John Doe');
        $object->setBarcode(new Barcode(BarcodeType::QrCode, 'member-0001'));

        $payload = new PassPayload();
        $payload->addGenericClass(new GenericClass('3388000000012345678.membership'));
        $payload->addGenericObject($object);

        return redirect()->away($this->saveUrlFactory->create($payload));
    }
}
```

Render the url as an [Add to Google Wallet button][GoogleWalletBrandGuidelines] and you are done.

See the [core package documentation][LauLamanAppsGoogleWalletPackage] for all pass types (generic, event ticket, offer, loyalty, transit), fields, images and colors.
The service provider also registers `LauLamanApps\GoogleWallet\ServiceAccount` and `LauLamanApps\GoogleWallet\JwtSigner` as singletons in case you want to sign a JWT yourself.

Save/delete callbacks
---
Google can notify your application whenever a user saves or deletes a pass. Enable the callback endpoint in your `.env` file:

```dotenv
###> laulamanapps/google-wallet-laravel ###
GOOGLE_WALLET_CALLBACK_ENABLED=true
GOOGLE_WALLET_ISSUER_ID=3388000000012345678
###< laulamanapps/google-wallet-laravel ###
```

When enabled, the package exposes `POST /google-wallet/callback` (route name `google-wallet.callback`; change the prefix with the `callback.route_prefix` config key). Point Google at it by setting the callback url on your pass classes — it is available on all five class types and must be `https://`:

```php
$class = new GenericClass('3388000000012345678.membership');
$class->setCallbackUrl('https://example.com/google-wallet/callback');
// or: $class->setCallbackUrl(route('google-wallet.callback'));
```

Every incoming callback is verified before anything is dispatched: the endpoint is public and anyone can POST to it, so the controller runs the raw request body through the core package's `CallbackVerifier` (Google's `ECv2SigningOnly` signature scheme, checked against your issuer id and Google's root signing keys). Requests that do not verify are logged at warning level and rejected with a `400` — no event is dispatched for them. Set the `callback.environment` config key to `'test'` to verify against Google's test root signing keys during integration testing.

Verified callbacks dispatch a Laravel event: `LauLamanApps\GoogleWalletLaravel\Events\PassSavedEvent` when a user saves a pass and `LauLamanApps\GoogleWalletLaravel\Events\PassDeletedEvent` when a user deletes one. Listen to them like any other event:

```php
namespace App\Listeners;

use LauLamanApps\GoogleWalletLaravel\Events\PassSavedEvent;

final class MarkPassAsSaved
{
    public function handle(PassSavedEvent $event): void
    {
        $event->getObjectId();      // '3388000000012345678.member-0001'
        $event->getClassId();       // '3388000000012345678.membership'
        $event->getCallbackEvent(); // the verified LauLamanApps\GoogleWallet\Callback\CallbackEvent
    }
}
```

Callbacks are delivered at least once and may arrive more than once: use `$event->getCallbackEvent()->getNonce()` to deduplicate.

Credits
---

This package has been developed by [LauLaman][LauLaman].

[LauLamanAppsGoogleWalletPackage]: https://github.com/LauLamanApps/google-wallet
[GoogleWalletConsole]: https://pay.google.com/business/console
[GoogleWalletBrandGuidelines]: https://developers.google.com/wallet/generic/resources/brand-guidelines
[LauLaman]: https://github.com/LauLaman
