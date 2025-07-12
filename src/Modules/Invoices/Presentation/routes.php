<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Invoices\Presentation\Http\InvoiceController;
use Ramsey\Uuid\Validator\GenericValidator;

Route::pattern('invoice', (new GenericValidator)->getPattern());

Route::group(['prefix' => 'invoices'], static function (): void {
    Route::post('/', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::post('/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
});