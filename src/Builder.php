<?php
namespace rOpenDev\Form;

use Illuminate\Validation\Validator;
use Symfony\Component\Translation\TranslatorInterface;
use rOpenDev\Form\adamwathanFormExtended\FormBuilder;

/**
 * PHP Class to use easily AdamWathan\Form\FormBuilder.
 * NibbleForm + AdamWathan\Form\FormBuilder = rOpenDev\Form
 */
class Builder
{
    use HTMLBuilderTrait;

    const DEFAULT_CHECKBOX_VALUE = 1;

    /** @var string **/
    protected $name;

    /** @var \AdamWathan\Form\FormBuilder **/
    public $formBuilder;

    /** @var array index fields add to FormBuilder **/
    protected $fields = [];

    /** @var array index field's labels **/
    protected $fieldsLabels = [];

    /** @var array index fields constraints **/
    protected $fieldsConstraints = [];

    /** @var array contain values for element can be checked (radio, checbox...) **/
    protected $fieldsValuesForCanBeChecked = [];
    protected $fieldsIsChecked = [];
    protected $fieldsLabelForCanBeChecked = [];
    protected $nameForCanBeChecked = [];

    /** @var array contain html attributes allowed **/
    protected $allowedAttributes = [
        /*xHTML attributes*/
        'class', 'id', 'value', 'disabled', 'size', 'readonly', 'maxlength', 'style', 'href', 'onclick',
        /*Html5Attributes*/
        'autocomplete', 'autofocus', 'form', 'formaction', 'formenctype', 'formmethod', 'formnovalidate', 'formtarget',
        'height', 'width', 'list', 'min', 'max', 'multiple', 'pattern', 'placeholder', 'required', 'step',
        /*Relative to php class*/
        'defaultValue', 'optional', 'disable', 'enable',
        'check', 'uncheck', 'defaultToChecked',
        'text', 'encapsuler', 'tag',
    ];

    /** @var array **/
    protected $attributesAndContraintsEquivalence = [
        'pattern'   => 'regex',
        'maxlength' => 'max',
    ];

    /** @var array contain constraint to transmit to illuminate/validator [source http://laravel.com/docs/master/validation#available-validation-rules] */
    protected $allowedConstraints = [
        'accepted', 'active_', 'after', 'alpha', 'alpha_dash', 'alpha_num', 'array', 'before', 'between', 'boolean',
        'confirmed', 'date', 'date_format', 'different', 'digits', 'digits_between', 'email', 'image', 'in',
        'integer', 'ip', 'max', 'mimes', 'min', 'not_in', 'numeric', 'regex', 'required', 'required_if', 'required_with',
        'required_with_all', 'required_without', 'required_without_all', 'same', 'size', 'timezone',
        /*DataBase RELATIVE*/
        'unique', 'exists',
    ];

    /** @var \Symfony\Component\Translation\TranslatorInterface */
    protected $translator;

    /** @var \Illuminate\Validation\Validator */
    protected $validator;

    /** @var array */
    protected $errorMessages = [
        'token' => 'csrf.error',
    ];

    /** @var string */
    protected $csrf;

    /** @var array Contain array instance **/
    protected static $instance = [];

    /**
     * Constructor
     *
     * @param string $name
     * @param \Symfony\Component\Translation\TranslatorInterface $translator
     *
     * @return self
     */
    public function __construct($name, TranslatorInterface $translator)
    {
        $this->translator = $translator;
        $this->formBuilder = new FormBuilder();
        $this->name = $name;
    }

    /**
     * Singleton method
     *
     * @param string $name
     *
     * @return self
     */
    public static function getInstance($name = '', TranslatorInterface $translator)
    {
        if (!isset(self::$instance[$name])) {
            self::$instance[$name] = new Builder($name, $translator);
        }

        return self::$instance[$name];
    }

    /**
     * Add multiple fields
     *
     * @params array $fields
     */
    public function addFields($fields)
    {
        foreach ($fields as $name => $attributes) {
            $this->addField($name, $attributes);
        }

        return $this;
    }

    /**
     * Create a new field via adamwathanFormExtended\FormBuilder
     *
     * @param string $name
     * @param array  $attributes
     * @param string $currentClassName
     *
     * @throws \LogicException If there is no type specified
     *
     * @return AdamWathan\Form\Elements\Element
     */
    protected function field($name, &$attributes, $currentClassName = null)
    {
        $currentClassName = $currentClassName === null ? $name : $currentClassName;

        if (!isset($attributes['type'])) {
            throw new \LogicException('Field `'.$name.'` doesn\'t have `type` attribute specified. You MUST specified it !');
        }

        $type = strtolower($attributes['type']);

        if (!isset($attributes['id'])) {
            $attributes['id'] = $name;
        }

        if (isset($attributes['options'])) {
            $field = $this->formBuilder->$type($name, $attributes['options']);
            unset($attributes['options']);
        } else {
            $field = $this->formBuilder->$type($name);
        }


        if ($this->canBeChecked($field)) {
            $this->fieldsValuesForCanBeChecked[$currentClassName] = $attributes['value'] = isset($attributes['value']) ? $attributes['value'] : self::DEFAULT_CHECKBOX_VALUE;
            $this->fieldsLabelForCanBeChecked[$currentClassName]  = isset($attributes['label']) ? $attributes['label'] : null;
            $this->nameForCanBeChecked[$currentClassName]  = $name;
        }

        unset($attributes['type']);

        if (isset($attributes['label'])) {
            $this->fieldsLabels[$currentClassName] = $this->formBuilder->label($attributes['label'])->forId($name);
            if (isset($attributes['labelAttributes'])) {
                $this->addAttributes($this->fieldsLabels[$currentClassName], $attributes['labelAttributes']);
                unset($attributes['labelAttributes']);
            }
            unset($attributes['label']);
        }

        if (isset($attributes['fieldFormat'])) {
            $this->fieldsFormat[$currentClassName] = $attributes['fieldFormat'];
            unset($attributes['fieldFormat']);
        }

        return $field;
    }

    protected static function isACanBeCheckedField($attributes)
    {
        return isset($attributes['options']) && isset($attributes['type']) && $attributes['type'] != 'select';
    }

    /**
     * @param string $name
     */
    protected function encapsFields($name, $attributes)
    {
        if ($attributes !== null)  {
            if (is_string($attributes)) {
                return $this->addField('e'.$name.'2', ['type' => 'separator', 'fieldFormat' => $attributes]);
            }
            if (isset($attributes['encaps'])) {
                $encaps = explode(':input', $attributes['encaps']);
                if (count($encaps) === 2) {
                    if (strpos($encaps[0], ':label') !== false && isset($attributes['label'])) {
                        $encaps[0] = str_replace(':label', $this->formBuilder->label($attributes['label'])->render(), $encaps[0]);
                    }
                    $this->addField('e'.$name.'1', ['type' => 'separator', 'fieldFormat' => $encaps[0]]);
                    return $encaps[1];
                }
            }
        }
    }

    /**
     * Add a field to the form instance and the validator
     * constraints by the way.
     *
     * @param string $name
     * @param array  $attributes MUST contain type (relative to Elements)
     *
     * @return AdamWathan\Form\Elements\Element
     */
    public function addField($name, $attributes = [])
    {
        if (self::isACanBeCheckedField($attributes)) {
            $encaps = $this->encapsFields($name, $attributes);
            $c = 0;
            foreach ($attributes['options'] as $key => $value) {
                if (!is_array($value)) {
                    $value = ['value' => $key === $c ? $value : $key, 'label' => $value];
                }
                $subAttr = array_diff_key($attributes, array_flip(['options']));
                $subAttr = array_merge($subAttr, $value);
                $currentClassName = $name.'-'.$subAttr['value'];
                $this->fields[$currentClassName] = $this->field($name, $subAttr, $currentClassName);
                $this->fieldsConstraints[$currentClassName] = [];
                $this->addAttributesForField($currentClassName, $attributes);
                ++$c;
            }
            $this->encapsFields($name, $encaps);
        } else {
            $this->fields[$name] = $this->field($name, $attributes);
            $this->fieldsConstraints[$name] = [];
            $this->addAttributesForField($name, $attributes);
        }

        // ? Return Field element
        return isset($this->fields[$name]) ? $this->fields[$name] : null;
    }

    /**
     * Add attributes and constraints from $attributes array
     *
     * @param  string $name
     * @param  array  $attributes
     * @return void
     */
    protected function addAttributesForField($name, $attributes)
    {
        foreach ($attributes as $key => $value) {
            if (is_int($key)) {
                $key = $value;
                $value = null;
            }

            if (in_array($key, $this->allowedAttributes)) {
                if ($value === null) {
                    $this->fields[$name]->$key();
                } else {
                    $this->fields[$name]->$key($value);
                }
            }

            if (in_array($key, $this->allowedConstraints)) {
                $this->fieldsConstraints[$name][] = $key.($value !== null ? ':'.$value : '');
            }
        }
    }

    /**
     * @param string $name
     *
     * @return AdamWathan\Form\Elements\Element
     */
    public function getField($name)
    {
        return $this->fields[$name];
    }

    /**
     * @param string $name
     *
     * @return array
     */
    public function getFieldConstraints($name)
    {
        return $this->fieldsConstraints[$name];
    }

    /**
     * Active csrf protection
     *
     * @return self
     */
    public function setCsrf()
    {
        session_status() == PHP_SESSION_NONE ? session_start() : null;

        $tokenName = $this->getTokenName();
        $this->csrf = isset($_SESSION['csrf']) && $_SERVER['REQUEST_METHOD'] == 'POST' ? $_SESSION['csrf'] : sha1(microtime().$this->name);

        $this->addField($tokenName, ['type' => 'hidden', 'value' => $this->csrf, 'in' => $this->csrf, 'fieldFormat' => ':input']);
        $this->setErrorMessage($tokenName.'.in', $this->translator->trans($this->errorMessages['token']));

        return $this;
    }

    /**
     * Add an error messages corresponding to the filters
     *
     * @param string $key
     * @param string $value
     *
     * @return self
     */
    public function setErrorMessage($key, $value)
    {
        $this->errorMessages[$key] = $value;
    }

    /**
     * Generate a name for csrf token
     *
     * @return string
     */
    public function getTokenName()
    {
        return sha1('token'.$this->name);
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    public function validate($data = null)
    {
        $data = $data === null ? $_POST : $data;

        $this->validator = new Validator($this->translator, $data, $this->fieldsConstraints, $this->errorMessages);

        return $this->validator->fails() ? false : true;
    }

    /**
     * The function self::validate() need to be call before this one else it will return null
     *
     * @return \Illuminate\Validation\Validator|null
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * Load data in the form
     *
     * @param array|object $data
     *
     * @return self
     */
    public function addData($data)
    {
        $data = (object) $data;
        $this->formBuilder->bind($data);

        foreach ($data as $name => $value) {
            $this->addDataFor($name, $value);
        }

        return $this;
    }

    /**
     * @param string $name
     * @param string $value
     */
    protected function addDataFor($name, $value)
    {
        if (isset($this->fields[$name])) {
            if ($this->canBeChecked($this->fields[$name])) {
                $this->addDataForCanBeChecked($name, $value);
            }
            elseif (method_exists($this->fields[$name], 'select')) {
                $this->fields[$name]->select($value);
            }
            else {
                $this->fields[$name]->value($value);
            }
        } elseif ($this->nameForCanBeChecked !== null && in_array($name, $this->nameForCanBeChecked)) {
            foreach (array_intersect($this->nameForCanBeChecked, [$name]) as $nName  => $v) {
                $this->addDataForCanBeChecked($nName, $value);
            }
        }
     }

     protected function addDataForCanBeChecked($name, $value)
     {
         if ($value == $this->fieldsValuesForCanBeChecked[$name]) {
            $this->fields[$name]->check();
            $this->fieldsIsChecked[$name] = true;
        } else {
            $this->fields[$name]->uncheck();
            $this->fieldsIsChecked[$name] = false;
        }
     }

    /**
     * @param \AdamWathan\Form\Elements\Element $field
     *
     * @return bool
     */
    protected function canBeChecked($field)
    {
        return method_exists($field, 'check');
    }

    /**
     * Return data from $_POST
     * @return array
     */
    public function getData()
    {
        $data = array_intersect_key($_POST, array_diff_key($this->fields, [$this->getTokenName() => 1]));

        foreach ($this->fields as $name => $field) {
            if (!isset($data[$name])) {
                if (method_exists($this->fields[$name], 'check')) {
                    $data[$name] = 0;
                }
            }
        }

        return $data;
    }
}
