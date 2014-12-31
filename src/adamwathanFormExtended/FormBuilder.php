<?php
namespace rOpenDev\Form\adamwathanFormExtended;

class FormBuilder extends \AdamWathan\Form\FormBuilder
{
    public function separator()
    {
        return new \rOpenDev\Form\adamwathanFormExtended\Elements\Separator();
    }

    public function number($name)
    {
        return new \rOpenDev\Form\adamwathanFormExtended\Elements\Number($name);
    }
}
