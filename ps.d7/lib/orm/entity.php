<?php

namespace Ps\D7\ORM;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\SystemException;
use Ps\D7\Fields\D7EntityField;

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
                    'title' => 'Сущность',
                    'required' => true,
                ]),

                new StringField('NAME', [
                    'title' => 'Наименование',
                    'required' => true,
                ]),

                new IntegerField('SORT', [
                    'title' => 'Индекс сортировки',
                    'default_value' => 500,
                ]),
            ];
        } catch (SystemException $e) {
        }

        return [];
    }
}
