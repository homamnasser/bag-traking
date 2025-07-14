<?php

namespace App\Traits;

trait PhotoTrait
{
    function saveImage($photo){
        $file_name = time().'.'.$photo -> getClientOriginalExtension();
        $file_name = $photo -> store('images','public');
        $photo -> move(public_path('images'),$file_name);

        return $file_name;
    }

    function upload($imgs)
    {
        $array = [];
        foreach ($imgs as $img) {
            $imge = $this->saveImage($img);
            $array[] = $imge;
        }
        return json_encode($array);
    }

}
