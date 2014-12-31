<?php
namespace rOpenDev\Form\Tests;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\PhpFileLoader;

class FormBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->translator = new Translator('en');
        $this->translator->setFallbackLocales(['en', 'en_US']);
        $this->translator->addLoader('php', new PhpFileLoader());
        $this->translator->addResource('php', __DIR__.'/languages/en.php', 'en');
    }

    /**
     * This is only testing separator and if class is loading without error...
     */
    public function test1()
    {
        $form = new \rOpenDev\Form\Builder('myFirstForm', $this->translator);

        $fields = [
            'username' => ['type' => 'text', 'required', 'min' => 4, 'max' => 80, 'alpha_num', 'label' => 'Username'],
            'email'    => ['type' => 'email', 'required', 'email', 'label' => 'Email'],
            'password' => ['type' => 'password', 'required', 'min' => 4, 'max' => 200, 'label' => 'Password'],
            'sep01'    => ['type' => 'separator', 'tag' => 'h3', 'text' => 'Hello World!'],
        ];

        $form->addFields($fields);
        $form->addField('submit', ['type' => 'submit', 'value' => 'Me connecter']);
        //$form->setCsrf();
        $this->assertSame($form->render(), '<form method="POST" action=""><div class=form-group><label for="username">Username</label><input type="text" name="username" required="required" min="4" max="80" id="username"></div><div class=form-group><label for="email">Email</label><input type="email" name="email" required="required" id="email"></div><div class=form-group><label for="password">Password</label><input type="password" name="password" required="required" min="4" max="200" id="password"></div><div class=form-group><h3 id="sep01">Hello World!</h3></div><div class=form-group><button type="submit" id="submit">Me connecter</button></div></form>');
    }
}
