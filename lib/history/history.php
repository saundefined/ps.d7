<?php

namespace Ps\D7\History;

use Bitrix\Main\Engine\CurrentUser;
use Ps\D7\ORM\VersionTable;

class History
{
    /** @var string */
    private $entity;

    /**
     * @var int
     */
    private $elementId = 0;

    /**
     * History constructor.
     *
     * @param $entity string
     * @param $elementId int
     */
    public function __construct($entity, $elementId) {
        $this->entity = $entity;
        $this->elementId = $elementId;
    }

    public function addVersion($new, $old = []) {
        if (isset($old['ID'])) {
            unset($old['ID']);
        }

        $isChanged = false;

        foreach ($new as $id => $value) {
            if (sha1($value) !== sha1($old[$id])) {
                $isChanged = true;
            }
        }

        if ($isChanged) {
            $userId = CurrentUser::get()->getId();

            try {
                VersionTable::add([
                    'ENTITY' => $this->entity,
                    'ELEMENT_ID' => $this->elementId,
                    'USER_ID' => $userId,
                    'DATA' => $new
                ]);
            } catch (\Exception $e) {
            }
        }
    }
}
