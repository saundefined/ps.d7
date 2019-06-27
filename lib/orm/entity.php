<?php

namespace Ps\D7\ORM;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\EntityError;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\SystemException;
use Ps\D7\Fields\D7EntityField;

Loc::loadMessages(__FILE__);

class EntityTable extends DataManager
{
    public static function getTableName() {
        return 'b_ps_d7_entities';
    }

    public static function getMap() {
        try {
            return [
                new IntegerField('ID', [
                    'title' => 'ID',
                    'primary' => true,
                    'autocomplete' => true,
                ]),

                new D7EntityField('ENTITY', [
                    'title' => Loc::getMessage('PS_D7_ENTITY_TITLE'),
                    'required' => true,
                ]),

                new StringField('NAME', [
                    'title' => Loc::getMessage('PS_D7_NAME_TITLE'),
                    'required' => true,
                ]),

                new IntegerField('SORT', [
                    'title' => Loc::getMessage('PS_D7_SORT_TITLE'),
                    'default_value' => 500,
                ]),
            ];
        } catch (SystemException $e) {
        }

        return [];
    }

    public static function onBeforeDelete(Event $event) {
        $result = new EventResult();
        $primary = $event->getParameter('primary');

        if ((int)$primary['ID'] === 1) {
            $result->addError(new EntityError(Loc::getMessage('PS_D7_SORT_DELETE_SYSTEM_ROW_ERROR')));
        }

        return $result;
    }

    public static function onBeforeUpdate(Event $event) {
        $result = new EventResult();
        $primary = $event->getParameter('primary');

        if ((int)$primary['ID'] === 1) {
            $result->addError(new EntityError(Loc::getMessage('PS_D7_SORT_UPDATE_SYSTEM_ROW_ERROR')));
        }

        return $result;
    }
}
