<?php

namespace Ps\D7\Events;

use Ps\D7\ORM\EntityTable;

class Entity
{
    function onGetEntityList() {
        return [
            new EntityTable(),
        ];
    }
}
