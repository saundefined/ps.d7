<?php

namespace Ps\D7\Form;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\ORM\Data\DataManager;
use CLightHTMLEditor;
use Ps\D7\Module;

class AdminForm extends \CAdminForm
{
    function AddDateTimeField($id, $label, $value, $required = false) {
        $html = CalendarDate($id, $value, $this->GetFormName(), 20);

        $value = htmlspecialcharsbx(htmlspecialcharsback($value));

        $this->tabs[$this->tabIndex]['FIELDS'][$id] = [
            'id' => $id,
            'required' => $required,
            'content' => $label,
            'html' => '<td>' . ($required ? '<span class="adm-required-field">' . $this->GetCustomLabelHTML($id,
                        $label) . '</span>' : $this->GetCustomLabelHTML($id, $label)) . '</td><td>' . $html . '</td>',
            'hidden' => '<input type="hidden" name="' . $id . '" value="' . $value . '">',
        ];
    }

    function AddHiddenField($id, $value) {
        $this->tabs[$this->tabIndex]['FIELDS'][$id] = [
            'id' => $id,
            'html' => '<input type="hidden" name="' . $id . '" value="' . $value . '">',
        ];
    }

    function AddD7EntityField($id, $label, $value, $required = false) {
        $event = new Event('ps.d7', 'registerEntities');
        $event->send();

        $classes = [];
        if ($event->getResults()) {
            foreach ($event->getResults() as $eventResult) {
                if ($eventResult->getType() === EventResult::SUCCESS) {
                    foreach ($eventResult->getParameters() as $class) {
                        Module::autoLoad($class);

                        if (new $class instanceof DataManager) {
                            $classes[$class] = $class;
                        }
                    }
                }
            }
        }

        $this->AddDropDownField($id, $label, $required, $classes, $value);
    }

    function AddLightEditorField($id, $label, $value, $required = false) {
        $this->BeginCustomField($id, $label, $required);
        ?>
        <tr>
            <td>
                <?= $required ? '<span class="adm-required-field">' .
                    $this->GetCustomLabelHTML($id, $label) . '</span>' :
                    $this->GetCustomLabelHTML($id, $label); ?>
            </td>
            <td>
                <?php

                $editor = new CLightHTMLEditor();
                $editor->Show([
                    'id' => $id,
                    'content' => $value,
                    'bBBCode' => true,
                    'bUseFileDialogs' => true,
                    'bUseMedialib' => true,
                    'arSmiles' => true,
                    'arFonts' => true,
                    'arFontSizes' => true,
                    'inputName' => $id,
                    'inputId' => $id,
                ]);
                ?>
            </td>
        </tr>
        <?
        $this->EndCustomField($id);
    }
}
