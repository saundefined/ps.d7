<?php

namespace Ps\D7\Fields;

use Bitrix\Main\ORM\Fields\ScalarField;
use Bitrix\Main\SystemException;

class FileField extends ScalarField
{
    public function cast($value) {
        return $value;
    }

    /**
     * @param mixed $value
     *
     * @return string
     * @throws SystemException
     */
    public function convertValueFromDb($value) {
        return $this->getConnection()->getSqlHelper()->convertFromDbString($value);
    }

    /**
     * @param string $value
     *
     * @return string
     * @throws SystemException
     */
    public function convertValueToDb($value) {
        return $this->getConnection()->getSqlHelper()->convertToDbString($value);
    }
}
