<?
use App\Http\Controllers\Api\HuellaController;

Route::post('/huellas/registrar', [HuellaController::class, 'registrar']);
?>