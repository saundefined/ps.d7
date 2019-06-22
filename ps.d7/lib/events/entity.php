<?php

namespace Ps\D7\Events;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Ps\D7\ORM\EntityTable;

class Entity
{
    function registerBaseEntity(Event $event) {
        $event->addResult(new EventResult(EventResult::SUCCESS, [
            EntityTable::class,
        ]));

        return $event;
    }
}
