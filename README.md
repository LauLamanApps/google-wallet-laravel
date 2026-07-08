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

| Key               | Default                               | Description                                                                       |
|-------------------|---------------------------------------|-----------------------------------------------------------------------------------|
| `service_account` | `env('GOOGLE_WALLET_SERVICE_ACCOUNT')` | Path to the Google Cloud service account JSON key file used to sign the JWTs      |
| `origins`         | `[]`                                  | Origins allowed to render the save urls; leave empty to allow opening from anywhere |

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

Credits
---

This package has been developed by [LauLaman][LauLaman].

[LauLamanAppsGoogleWalletPackage]: https://github.com/LauLamanApps/google-wallet
[GoogleWalletConsole]: https://pay.google.com/business/console
[GoogleWalletBrandGuidelines]: https://developers.google.com/wallet/generic/resources/brand-guidelines
[LauLaman]: https://github.com/LauLaman
