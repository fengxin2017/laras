<?php


namespace App\Http\Controllers;


use Illuminate\Validation\ValidationException;
use Laras\Contracts\Foundation\Application;
use Laras\Facades\ValidatorFactory;
use Laras\Http\Request;
use Laras\Support\Annotation\Inject;

/**
 * Class BaseController
 * @package App\Http\Controllers
 */
class BaseController
{
    /**
     * @Inject(Request::class)
     * @var Request $request
     */
    protected $request;

    /**
     * @Inject(Application::class)
     * @var Application $app
     */
    protected $app;

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