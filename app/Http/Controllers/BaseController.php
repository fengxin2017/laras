<?php


namespace App\Http\Controllers;


use Illuminate\Validation\ValidationException;
use Laras\Facades\ValidatorFactory;

/**
 * Class BaseController
 * @package App\Http\Controllers
 */
class BaseController
{
    /**
     * Controller constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param array $parameters
     * @param $rules
     * @return bool
     * @throws ValidationException
     */
    public function validate(array $parameters, array $rules)
    {
        $validator = ValidatorFactory::make($parameters, $rules);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return true;
    }
}