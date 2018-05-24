<?php
/**
 * amoCRM API contacts tests
 * @author Vlad Ionov <vlad@f5.com.ru>
 */
namespace Tests\Cases;

use PHPUnit\Framework\Assert;
use Ufee\Amo\Services;
	
require_once __DIR__ . '/../TestCase.php';

class ContactsTest extends \Tests\TestCase
{
    public function testGetContactsService()
    {
		Assert::assertInstanceOf(
			Services\Contacts::class, $this->amo->contacts()
		);
    }
	
    public function testCreateOneContact()
    {
		$model = $this->amo->contacts()->create();
		$model->name = 'Test CreateOneContact '.time();
		$has_created = $model->save();

		Assert::assertTrue(
			($has_created && is_numeric($model->id))
		);
    }
	
    public function testUpdateContactName()
    {
		$model = $this->amo->contacts()->create();
		$model->name = 'Test UpdateContact '.time();
		$model->save();
		
		$model->name = 'Test UpdateContact NEW';
		$model->save();
		$model = $this->amo->contacts()->find($model->id);
		
		Assert::assertEquals(
			$model->name, 'Test UpdateContact NEW'
		);
    }
	
    public function testCreateTwoContacts()
    {
		$create_models = [
			$this->amo->contacts()->create(),
			$this->amo->contacts()->create()
		];
		$create_models[0]->name = 'Test CreateTwoContacts 1 '.time();
		$create_models[1]->name = 'Test CreateTwoContacts 2 '.time();
		$has_created = $this->amo->contacts()->add($create_models);
		
		Assert::assertTrue(
			($has_created && is_numeric($create_models[0]->id) && is_numeric($create_models[0]->id))
		);
    }
}
