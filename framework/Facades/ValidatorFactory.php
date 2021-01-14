<?php


namespace MoneyMaker\Facades;

/**
 * Class ValidatorFactory
 * @package MoneyMaker\Facades
 * @method static \Illuminate\Validation\Validator make(array $data, array $rules, array $messages = [], array $customAttributes = [])
 */
class ValidatorFactory extends Facade
{
    /**
     * @return bool|string
     */
    public function getAccessor()
    {
        return 'validator';
    }
}