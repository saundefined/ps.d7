<?php

namespace Ps\D7\ORM;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Ps\D7\Fields\D7EntityField;

Loc::loadMessages(__FILE__);

class VersionTable extends DataManager
{
    public static function getTableName() {
        return 'b_ps_d7_versions';
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
                    'title' => 'Таблица',
                    'required' => true,
                ]),

                new DatetimeField('DATE', [
                    'title' => 'Дата',
                    'required' => true,
                    'default_value' => new DateTime()
                ]),

                new IntegerField('USER_ID', [
                    'title' => 'Пользователь',
                    'required' => true,
                ]),

                new TextField('DATA', [
                    'title' => 'Данные',
                    'required' => true,
                    'serialized' => true
                ]),
            ];
        } catch (SystemException $e) {
        }

        return [];
    }
}
