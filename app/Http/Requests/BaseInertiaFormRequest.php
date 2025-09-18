<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BaseInertiaFormRequest extends FormRequest
{
    protected function failedAuthorization()
    {
        return redirect()->back()->with('error', 'Anda tidak memiliki akses.');
    }
}
