<?php
namespace rOpenDev\Form;

trait HTMLBuilderTrait
{
    /** @var array $formAttributes */
    protected $formAttributes = [];

    /** @var string html */
    protected $defaultFieldFormat = '<div class=form-group>:label:input</div>';

    /** @var array */
    protected $fieldsFormat = [];

    /**
     * Set <form> attributes
     *
     * @param array $attr
     * @param bool  $reset TRUE if you want to remove previous setted attributes
     *
     * @return self
     */
    public function setFormAttributes($attr, $reset = false)
    {
        if($reset === true) {
            $this->formAttributes = $attr;
        }
        else {
            $this->formAttributes = array_merge($this->formAttributes, $attr);
        }

        return $this;
    }

    public function setDefaultFieldFormat($html)
    {
        $this->defaultFieldFormat = $html;
    }

    /**
     * @param object|null $tmpl     Contain template engine instance (eg. : new League\Plates\Engine('/path/to/templates'))
     *                              I should do an interface... meanwhile, you just need a method `render` with two args, the view
     *                              and the data we pass to the view
     * @param string|null $tmplView (eg. : 'form')
     *
     * @return string
     */
    public function render($tmpl = null, $tmplView = null)
    {
        $_SESSION['csrf'] = $this->csrf;

        $data = [];
        $data['formOpener']   = $this->getFormOpener();
        $data['form']         = $this->formBuilder;
        $data['fields']       = $this->fields;
        $data['fieldsLabels'] = $this->fieldsLabels;
        $data['defaultFieldFormat'] = $this->defaultFieldFormat;

        $data['fieldsFormatted'] = [];
        foreach ($this->fields as $name => $field) {
            $data['fieldsFormatted'][$name] = str_replace(
                [':label',                                                                   ':input'],
                [isset($this->fieldsLabels[$name]) ? $this->fieldsLabels[$name]->render() : '', $field->render()],
                isset($this->fieldsFormat[$name]) ? $this->fieldsFormat[$name] : $this->defaultFieldFormat
            );
        }

        isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST' ? $this->addData(array_intersect_key($_POST, array_diff_key($this->fields, [$this->getTokenName() => 1]))) : null;

        if ($tmpl !== null) {
            return $tmpl->render($tmplView, $data);
        } else {
            return self::defaultRenderEngine($data);
        }
    }

    protected function getFormOpener()
    {
        $opener = $this->formBuilder->open();

        $this->addAttributes($opener, $this->formAttributes);

        return $opener;
    }

    /**
     * @param \AdamWathan\Form\Elements\Element $element
     * @param array                             $attributes
     *
     * @return void
     */
    protected function addAttributes(\AdamWathan\Form\Elements\Element $element, $attributes)
    {
        foreach ($attributes as $key => $value) {
            is_int($key) ? $element->$value() : $element->$key($value);
        }
    }

    /**
     * @param array $data
     *
     * @return string
     */
    protected static function defaultRenderEngine($data)
    {
        extract($data);

        $html = $formOpener->render();
        foreach ($fieldsFormatted as $name => $field) {
            $html .= $field;
        }
        $html .= $form->close();

        return $html;
    }
}
