<?php


namespace App\Http\Controllers;


use App\Test\Foo;
use Illuminate\Validation\ValidationException;
use Laras\Facades\ValidatorFactory;
use Laras\Support\Annotation\Inject;

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