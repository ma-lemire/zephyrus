<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Feedback;
use Zephyrus\Application\Form;
use Zephyrus\Application\Rule;
use Zephyrus\Application\Session;
use Zephyrus\Utilities\Validator;

class FormTest extends TestCase
{
    public function testValidForm()
    {
        $form = new Form();
        $form->addFields([
            'username' => 'blewis'
        ]);
        $form->validate('username', Rule::notEmpty('username not empty'));
        self::assertTrue($form->verify());
    }

    public function testUnregistered()
    {
        $form = new Form();
        $form->addFields([
            'username' => 'blewis'
        ]);
        // simulate unchecked checkbox
        $form->validate('understand', Rule::notEmpty('you need to understand'));
        self::assertFalse($form->isRegistered('understand'));
        self::assertTrue($form->isRegistered('username'));
        $form->verify();
        self::assertEquals('you need to understand', $form->getErrors()['understand'][0]);
    }

    public function testErrors()
    {
        $form = new Form();
        $form->addFields([
            'username' => ''
        ]);
        $form->validate('username', Rule::notEmpty('username not empty'));
        $form->addError('name', 'err-1');
        self::assertFalse($form->verify());
        self::assertTrue(key_exists('username', $form->getErrors()));
        self::assertTrue(key_exists('name', $form->getErrors()));
        self::assertEquals('username not empty', $form->getErrors()['username'][0]);
        self::assertEquals('err-1', $form->getErrors()['name'][0]);
    }

    public function testFeedback()
    {
        Session::getInstance()->start();
        $form = new Form();
        $form->addFields([
            'username' => ''
        ]);
        $form->validate('username', Rule::notEmpty('username not empty'));
        self::assertFalse($form->verify());
        $form->registerFeedback();
        $feedback = Feedback::readAll()["feedback"]["error"];
        self::assertTrue(key_exists('username', $feedback));
        self::assertEquals('username not empty', $feedback['username'][0]);
        Session::kill();
    }

    public function testErrorTrigger()
    {
        $form = new Form();
        $form->addField('name', '');
        $form->addField('name2', 'bob*');
        $form->addField('price', '12.50e');
        $form->validate('name', new Rule(Validator::NOT_EMPTY, 'err_1'));
        $form->validateWhenFieldHasNoError('name', new Rule(Validator::ALPHANUMERIC, 'err_2'));
        $form->validate('name2', new Rule(Validator::NOT_EMPTY, 'err_11'));
        $form->validateWhenFieldHasNoError('name2', new Rule(Validator::ALPHANUMERIC, 'err_22'));
        $form->validate('price', new Rule(Validator::NOT_EMPTY, 'err_3'));
        $form->validateWhenFormHasNoError('price', new Rule(Validator::DECIMAL, 'err_4'));
        $form->verify();
        $errors = $form->getErrorMessages();
        self::assertEquals('err_1', $errors[0]);
        self::assertEquals('err_22', $errors[1]);
    }

    public function testReadValues()
    {
        $form = new Form();
        $form->addFields([
            'username' => 'blewis',
            'firstname' => 'bob'
        ]);
        self::assertEquals('blewis', $form->getValue('username'));
        self::assertEquals('bob', $form->getFields()['firstname']);
    }

    public function testInvalidForm()
    {
        $form = new Form();
        $form->addFields([
            'username' => ''
        ]);
        $form->validate('username', Rule::notEmpty('username not empty'));
        self::assertFalse($form->verify());
        self::assertEquals('username not empty', $form->getErrorMessages()[0]);
    }

    public function testOptionalField()
    {
        $form = new Form();
        $form->addFields([
            'email' => ''
        ]);
        $form->validate('email', Rule::email('email not valid'), true);
        self::assertTrue($form->verify());
    }

    public function testInvalidCallbackForm()
    {
        $form = new Form();
        $form->addFields([
            'username' => 'blewis'
        ]);
        $form->validate('username', new Rule(function ($value) {
            return $value == 'bob';
        }, 'username not valid'));
        self::assertFalse($form->verify());
        self::assertEquals('username not valid', $form->getErrorMessages()[0]);
    }

    public function testConfirmationCallbackForm()
    {
        $form = new Form();
        $form->addFields([
            'password' => 'omega123',
            'password-confirm' => 'omega'
        ]);
        $form->validate('password', new Rule(function ($value, $fields) {
            return $value == $fields['password-confirm'];
        }, 'password not valid'));
        self::assertFalse($form->verify());
        self::assertEquals('password not valid', $form->getErrorMessages()[0]);
    }

    public function testMemorization()
    {
        Form::removeMemorizedValue();
        $form = new Form();
        $form->addField('name', 'bob');
        $form->validate('name', new Rule(Validator::NOT_EMPTY, 'err_1'));
        $form->verify();
        self::assertEquals('bob', Form::readMemorizedValue('name'));
        self::assertEquals('lewis', Form::readMemorizedValue('gfdfg', 'lewis'));
    }

    public function testRemoveAllMemorized()
    {
        $_SESSION['_FIELDS'] = [
            'name' => 'bob',
            'price' => '12.5'
        ];
        Form::removeMemorizedValue();
        self::assertFalse(isset($_SESSION['_FIELDS']));
    }

    public function testBuildObject()
    {
        $form = new Form();
        $form->addFields(['name' => 'bob', 'price' => '10.00']);
        $class = new class() {
            private $name;
            private $price;

            public function getName()
            {
                return $this->name;
            }

            public function setName($name)
            {
                $this->name = $name;
            }

            public function getPrice()
            {
                return $this->price;
            }

            public function setPrice($price)
            {
                $this->price = $price;
            }
        };
        $form->buildObject($class);
        self::assertEquals('bob', $class->getName());
        self::assertEquals('10.00', $class->getPrice());
    }

    public function testBuildStdClass()
    {
        $form = new Form();
        $form->addFields(['name' => 'bob', 'price' => '10.00']);
        $class = $form->buildObject();
        self::assertEquals('bob', $class->name);
        self::assertEquals('10.00', $class->price);
    }

    public function testInvalidRuleField()
    {
        $form = new Form();
        $form->addField('name', '');
        $form->validate('bob', new Rule(Validator::ALPHANUMERIC, "err_1"));
        self::assertFalse($form->verify());
    }
}
