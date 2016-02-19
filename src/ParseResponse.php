<?php

namespace LaraParse;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class ParseResponse extends JsonResponse
{
    public function __construct($data = null, $status = 200, $headers = array(), $options = 0)
    {
        if($data instanceof \Exception){
            $data = array(
                "code" => $data->getCode(),
                "error" => $data->getMessage()
            );
        }else{
            // move the prepare function here
            Carbon::setToStringFormat('Y-m-d\\TH:i:s.z\Z');
            $data = ParseHelper::prepareResponse($data);
        }

        parent::__construct($data, $status, $headers, $options);
    }
}