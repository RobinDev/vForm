<?php
/********** Init Stuff ***************/
include '../vendor/autoload.php';

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\PhpFileLoader;

$translator = new Translator('en');
$translator->setFallbackLocales(['en', 'en_US']);
$translator->addLoader('php', new PhpFileLoader());
$translator->addResource('php', __DIR__.'/languages/en.php', 'en');
/********** /Init Stuff ***************/

/********** Demo start here ***************/
header('Content-Type: text/html; charset=utf-8');

$form = new \rOpenDev\Form\Builder('myFirstForm', $translator);

$fields = [
    'username' => ['type' => 'text', 'required', 'min' => 4, 'max' => 80, 'alpha_num', 'label' => 'Username'],
    'email'    => ['type' => 'email', 'required', 'email', 'label' => 'Email'],
    'password' => ['type' => 'password', 'required', 'min' => 4, 'max' => 200, 'label' => 'Password'],
    'sep01'    => ['type' => 'separator', 'tag' => 'h3', 'text' => 'Hello World!'],
    'radioB'   => ['type' => 'radio', 'options' => [['label' => 'Forever', 'value' => '1'], ['label' => 'Just Five Minuts', 'value' => '2']], 'required', 'fieldFormat' => '<label class="btn btn-primary :active"><input type="radio" id=":name" name=":name" value=":value">:plainLabel</label>'],
    'radioB2'   => ['type' => 'radio', 'options' => [ 1 => 'Forever', 2 => 'Just For Five Minuts'], 'required', 'fieldFormat' => '<label class="btn btn-primary :active"><input type="radio" id=":name" name=":name" value=":value">:plainLabel</label>'],
];

$_SERVER['REQUEST_METHOD'] == 'POST' ? $form->addData(array_intersect_key($_POST, $fields)) : null;
$form->addFields($fields);
$form->addField('submit', ['type' => 'submit', 'value' => 'Me connecter']);
$form->setCsrf();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($form->validate()) {
        echo 'Form validée';
        exit;
    } else {
        foreach ($form->getValidator()->messages()->all() as $message) {
            echo $message.'<br>';
        }
    }
}

echo $form->render();

/**/
