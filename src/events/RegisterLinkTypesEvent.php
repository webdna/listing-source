<?php
namespace kuriousagency\listingsource\events;

use yii\base\Event;

class RegisterLinkTypesEvent extends Event
{
    // Properties
    // =========================================================================

    public $types = [];
}
