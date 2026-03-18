<?php

declare(strict_types=1);

namespace Osoobe\DimePay\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CallbackController extends Controller
{
    public function handle(Request $request): \Illuminate\Http\JsonResponse
    {
        // Default implementation — consumers should override this
        // controller in their own routes to handle the redirect.
        return response()->json([
            'status' => $request->query('status'),
            'token'  => $request->query('token'),
        ]);
    }
}
