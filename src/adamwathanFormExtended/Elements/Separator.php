<?php
namespace rOpenDev\Form\adamwathanFormExtended\Elements;

class Separator extends \AdamWathan\Form\Elements\Element
{
    /** @var string */
    private $tag = 'h3';

    /** @var bool */
    private $encapsuler = true;

    /** @var string */
    private $text;

    /*
     * @param string $tag
     */
    public function tag($str)
    {
        $this->tag = $str;
    }

    /*
     * @param bool $bool
     */
    public function encapsuler($bool)
    {
        $this->encapsuler = $bool;
    }

    public function render()
    {
        $result = '<'.$this->tag;
        $result .= $this->renderAttributes();
        $result .= '>';
        if ($this->encapsuler) {
            $result .= $this->text;
            $result .= '</'.$this->tag.'>';
        }

        return $result;
    }

    /**
     * @param string $text
     *
     * @return self
     */
    public function text($text)
    {
        $this->text = $text;

        return $this;
    }
}
