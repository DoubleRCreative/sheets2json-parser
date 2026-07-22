<?php 

namespace App\Http\Requests\Transformers;

abstract class RequestTransformer
{
    abstract static function transform(array $data);

}