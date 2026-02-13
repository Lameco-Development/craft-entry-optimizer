<?php

declare(strict_types=1);

use craft\ecs\SetList;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function(ECSConfig $ecsConfig): void {
    $ecsConfig->paths([
        __DIR__ . '/src',
        __FILE__,
    ]);

    $ecsConfig->sets([
        // Use Craft 5 coding standards. Fallback to CRAFT_CMS_4 if this constant doesn't exist.
        defined('SetList::CRAFT_CMS_5') ? SetList::CRAFT_CMS_5 : SetList::CRAFT_CMS_4,
    ]);
};
