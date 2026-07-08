<?php

declare(strict_types=1);

namespace LauLamanApps\GoogleWalletLaravel\Events;

/**
 * Dispatched when Google notifies you that a user deleted a pass from their wallet.
 */
final class PassDeletedEvent extends AbstractPassEvent
{
}
