<?php

namespace Ps\D7\Form;

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
}
