<?php

use App\Models\FinancialReport;
use App\Models\Gen;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

Route::get('financial-report', function () {
    $id = request('id');
    $rep = FinancialReport::find($id);
    if ($rep == null) {
        return die('Gen not found');
    }

    $pdf = App::make('dompdf.wrapper');
    $company = $rep->company;

    //check fi has logo and if it exisits
    if ($company->logo != null) {
        //$company->logo = public_path() . '/storage/' . $company->logo;
    } else {
        $company->logo = null;
    }

    $pdf->loadHTML(view('reports.financial-report', [
        'data' => $rep,
        'company' => $company
    ]));

    $model = $rep;
    $pdf->render();
    $output = $pdf->output();
    $store_file_path = public_path('storage/files/report-' . $model->id . '.pdf');
    file_put_contents($store_file_path, $output);
    $model->file = 'files/report-' . $model->id . '.pdf';
    $model->file_generated = 'Yes';


    return $pdf->stream();

    //view reports.financial-report
    return view('reports.financial-report', ['data' => $rep]);
});

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