<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {

    $action = new  \App\Actions\CreateProductOnBlingErpAction();

    dd($action->execute([]));
    // return view(view: 'welcome');
});
