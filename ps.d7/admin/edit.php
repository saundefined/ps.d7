<?php

use Bitrix\Main\Context;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\ScalarField;
use Ps\D7\Exception\ElementNotFoundException;
use Ps\D7\Exception\EntityNotDefinedException;
use Ps\D7\Exception\EntityNotFoundException;
use Ps\D7\Form\AdminForm;
use Ps\D7\ORM\EntityTable;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/prolog.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

Loader::includeSharewareModule('ps.d7');

$request = Context::getCurrent()->getRequest();

try {
    $entityId = $request->get('ENTITY_ID');
    if (!$entityId) {
        throw new EntityNotDefinedException('Не указана сущность');
    }

    $row = EntityTable::getList([
        'filter' => ['ID' => $entityId]
    ])->fetch();

    if (!$row['ID']) {
        throw new EntityNotFoundException('Сущность не найдена');
    }

    $elementId = $request->get('ID');

    /** @var DataManager $class */
    $class = $row['ENTITY'];

    Loader::autoLoad($class);

    $entity = $class::getEntity();
    $fields = $entity->getFields();

    if ($elementId > 0) {
        $element = $class::getList([
            'filter' => ['ID' => $elementId]
        ])->fetch();

        if (!$element['ID']) {
            throw new ElementNotFoundException('Элемент не найден');
        }
    }

    $APPLICATION->SetTitle($elementId > 0 ? 'Редактирование элемента' : 'Добавление элемента');

    $formId = 'form_' . mb_strtolower($entity->getCode()) . '_edit';

    $menu = [];

    $menu[] = [
        'TEXT' => 'Список элементов',
        'LINK' => 'ps_d7_admin.php?lang=' . LANGUAGE_ID . '&ENTITY_ID=' . $entityId . '&set_default=Y',
        'ICON' => 'btn_list',
        'TITLE' => 'Список элементов',
    ];
    $menu[] = ['SEPARATOR' => 'Y'];

    $menu[] = [
        'TEXT' => 'Добавить',
        'LINK' => 'ps_d7_edit.php?lang=' . LANGUAGE_ID . '&ENTITY_ID=' . $entityId,
        'ICON' => 'btn_new',
        'TITLE' => 'Добавить новый элемент',
    ];

    $menu[] = ['SEPARATOR' => 'Y'];

    $context = new CAdminContextMenu($menu);
    $context->Show();

    if (
        $request->isPost() &&
        ($request->getPost('save') !== '' || $request->getPost('apply') !== '' || $request->getPost('save_and_add') !== '') &&
        check_bitrix_sessid()
    ) {
        $ID = 0;
        $values = $request->getPostList()->toArray();

        if ($values['ID']) {
            $ID = $values['ID'];
            unset($values['ID']);
        }

        $allowedFields = array_keys($fields);
        foreach ($values as $k => $value) {
            if (!in_array($k, $allowedFields, false)) {
                unset($values[$k]);
            }
        }

        if ($ID > 0) {
            $el = $class::update($ID, $values);
        } else {
            $el = $class::add($values);
        }

        if ($el->isSuccess()) {
            $redirect_url_list = '/bitrix/admin/ps_d7_admin.php?lang=' . LANG . '&ENTITY_ID=' . $entityId;
            $redirect_url_edit = '/bitrix/admin/ps_d7_edit.php?lang=' . LANG . '&ENTITY_ID=' . $entityId;

            if ($request->getPost('save') !== '') {
                LocalRedirect($redirect_url_list);
            } elseif ($request->getPost('apply') !== '') {
                LocalRedirect($redirect_url_edit . '&ID=' . $el->getId() . '&' . $tabControl->ActiveTabParam());
            } elseif ($request->getPost('save_and_add') !== '') {
                LocalRedirect($redirect_url_edit . '&ID=0&' . $tabControl->ActiveTabParam());
            }
        } else {
            $e = new CAdminException([['text' => implode('<br />', $el->getErrorMessages())]]);
            $message = new CAdminMessage('Ошибка сохранения', $e);
            echo $message->Show();
        }
    }

    $tabs = [];
    $tabs[] = [
        'DIV' => 'edit1',
        'TAB' => 'Элемент',
        'ICON' => 'main_user_edit',
        'TITLE' => 'Редактирование элемента'
    ];

    $tabControl = new AdminForm($formId, $tabs);

    $tabControl->BeginPrologContent();

    CAdminCalendar::ShowScript();

    $tabControl->EndPrologContent();
    $tabControl->BeginEpilogContent();

    echo bitrix_sessid_post();

    $tabControl->EndEpilogContent();

    $tabControl->Begin([
        'FORM_ACTION' => $APPLICATION->GetCurPage() . '?id=' . $elementId . '&lang=' . LANG
    ]);

    $tabControl->BeginNextFormTab();

    $tabControl->AddHiddenField('ENTITY_ID', $entityId);

    if ($elementId > 0) {
        $tabControl->AddHiddenField('ID', $elementId);
    }

    $event = new Event('ps.d7', 'registerCustomField');
    $event->send();

    $customEntity = [];
    if ($event->getResults()) {
        foreach ($event->getResults() as $eventResult) {
            if ($eventResult->getType() === EventResult::SUCCESS) {
                /** @var Event $parameters */
                $parameters = $eventResult->getParameters();

                if (class_exists($parameters['ENTITY']) && is_callable($parameters['HANDLER'])) {
                    $customEntity[$parameters['ENTITY']] = $parameters['HANDLER'];
                }
            }
        }
    }

    foreach ($fields as $field) {
        if ($field instanceof ScalarField &&
            !$field->isAutocomplete()
        ) {
            $class = get_class($field);
            $value = $element[$field->getName()];

            if ($customEntity[$class]) {
                call_user_func($customEntity[$class], $tabControl, $field, $value);
            } else {
                switch ($class) {
                    case 'Bitrix\Main\ORM\Fields\EnumField':
                        // todo:
                        break;
                    case 'Bitrix\Main\ORM\Fields\BooleanField':
                        $tabControl->AddCheckBoxField($field->getName(), $field->getTitle(), $field->isRequired(),
                            $field->getDefaultValue(), $value);
                        break;
                    case 'Bitrix\Main\ORM\Fields\DateField':
                        $tabControl->AddCalendarField($field->getName(), $field->getTitle(), $value,
                            $field->isRequired());
                        break;
                    case 'Bitrix\Main\ORM\Fields\DatetimeField':
                        $tabControl->AddDateTimeField($field->getName(), $field->getTitle(), $value,
                            $field->isRequired());
                        break;
                    case 'Bitrix\Main\ORM\Fields\StringField':
                        $tabControl->AddEditField($field->getName(), $field->getTitle(), $field->isRequired(), [],
                            $value);
                        break;
                    case 'Bitrix\Main\ORM\Fields\TextField':
                        // todo: LHE
                        break;
                    case 'Ps\D7\Fields\FileField':
                        // todo:
                        break;
                    case 'Ps\D7\Fields\D7EntityField':
                        $event = new Event('ps.d7', 'registerEntities');
                        $event->send();

                        $classes = [];
                        if ($event->getResults()) {
                            foreach ($event->getResults() as $eventResult) {
                                if ($eventResult->getType() === EventResult::SUCCESS) {
                                    foreach ($eventResult->getParameters() as $class) {
                                        if (is_subclass_of($class, '\\Bitrix\\Main\\ORM\\Data\\DataManager')) {
                                            $classes[] = $class;
                                        }
                                    }
                                }
                            }
                        }

                        $tabControl->AddDropDownField($field->getName(), $field->getTitle(), $field->isRequired(),
                            $classes, $value);
                        break;
                    default:
                        $tabControl->AddEditField($field->getName(), $field->getTitle(), $field->isRequired(), [],
                            $value);
                        break;
                }
            }
        }
    }


    $tabControl->Buttons([
        'disabled' => false,
        'btnSaveAndAdd' => true,
        'btnSave' => true,
        'btnApply' => true,
        'btnCancel' => true,
        'back_url' => 'ps_d7_list.php?lang=' . LANGUAGE_ID . '&ENTITY_ID=' . $entityId,
    ]);

    $tabControl->Show();
} catch (Exception $exception) {
    $e = new CAdminException([['text' => $exception->getMessage()]]);
    $message = new CAdminMessage('Ошибка', $e);
    echo $message->Show();
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
