<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Actions\AttachCallbackMercadoLivreProductToProductCentralAction;

class ProductController extends Controller
{
    public function proccessMlProduct(Request $request)
    {

        app(AttachCallbackMercadoLivreProductToProductCentralAction::class)->execute($request->all());
    }
}
