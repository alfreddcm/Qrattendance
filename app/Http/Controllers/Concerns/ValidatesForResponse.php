<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

trait ValidatesForResponse
{
    /**
     * Validate request and return JSON response for AJAX or redirect for web
     * Returns null on success or a Response/Redirect on failure
     */
    private function validateForResponse(Request $request, array $rules)
    {
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            // Return JSON when this is an explicit AJAX request (X-Requested-With) or
            // when the request carries JSON (e.g., tests using postJson or fetch/axios with JSON body).
            // This avoids using wantsJson() which may be true for Accept: application/json on normal forms.
            if ($request->ajax() || $request->isJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // return the validated data on success
        return $validator->validated();
    }
}
