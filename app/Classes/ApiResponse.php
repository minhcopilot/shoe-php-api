<?php

namespace App\Classes;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Exceptions\HttpResponseException;

class ApiResponse
{
    // Hàm rollback nếu có lỗi xảy ra
    public static function rollback($e, $message = "Something went wrong! Process not completed")
    {
        // Có thể thêm logic roll back cơ sở dữ liệu nếu cần
        Log::error($e);
        self::throw($e, $message);
    }

    // Hàm throw lỗi với thông báo tùy chỉnh
    public static function throw($e, $message = "Something went wrong! Process not completed")
    {
        Log::info($e);
        throw new HttpResponseException(response()->json(["message" => $message], 500));
    }

    // Hàm trả về response thành công
    public static function sendResponse($result, $message, $code = 200)
    {
        $response = [
            'success' => true,
            'data'    => $result
        ];

        if (!empty($message)) {
            $response['message'] = $message;
        }

        return response()->json($response, $code);
    }
    //Khoong tim thay
    public static function NoSearch($result, $message, $code = 404)
    {
        $response = [
            'success' => false, 
            'data'    => $result
        ];

        if (!empty($message)) {
            $response['message'] = $message;
        }

        return response()->json($response, $code);
    }
}
