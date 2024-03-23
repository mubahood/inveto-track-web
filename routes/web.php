<?php

use App\Models\Gen;
use Illuminate\Support\Facades\Route;

// Route get generate-models
Route::get('generate-models', function () {
    $id = request('id');
    $gen = Gen::find($id);
    if ($gen == null) {
        return die('Gen not found');
    }
    $gen->gen_model();
    return die('generate-models');
});


/* Route::get('/', function () {
    return die('welcome');
});
Route::get('/home', function () {
    return die('welcome home');
});
 */