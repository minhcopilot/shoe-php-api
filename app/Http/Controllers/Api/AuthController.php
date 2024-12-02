<?php
namespace App\Http\Controllers\Api;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Api\Auth\LoginRequest;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\Api\Auth\RegisterRequest;

class AuthController extends Controller
{
    protected $authService;
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    public function register(RegisterRequest $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'is_admin' => 'required|in:1,0',
            'password_confirmation' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
        $emailExists = User::where('email', $request->email)->exists();

        if ($emailExists) {
            return response()->json(['message' => 'Email already exists in the system.'], 409); // Trả về mã lỗi 409 nếu email trùng
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'User registered successfully. Please verify your email before logging in.',
            'user' => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $validatedData = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);
        $user = User::where('email', $validatedData['email'])->first();

        if (!$user || !Hash::check($validatedData['password'], $user->password)) {
            return response()->json(['message' => 'Email or password is incorrect'], 401);
        }
        $token = $user->createToken('YourAppName')->plainTextToken;   
        $result = ['message' => 'Login success',
                'code' => 200,
                'token' => $token,
                'user' => $user
       ];
        return response()->api_success($result['message'], $result);
    }
    public function logout()
    {
        $result = $this->authService->logout();
        if ($result) {
            return response()->api_success('Logout success');
        }
        return response()->api_error('Logout failed');
    }

    // Gửi email chứa link đặt lại mật khẩu
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Reset link sent to your email.']);
        }

        return response()->json(['message' => 'Unable to send reset link.'], 500);
    }

    // Xử lý reset mật khẩu
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|confirmed|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $response = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = bcrypt($password);
                $user->save();
            }
        );

        return $response == Password::PASSWORD_RESET
            ? response()->json(['status' => trans($response)], 200)
            : response()->json(['error' => trans($response)], 400);
    }

    public function getUser(Request $request)
    {
        // Lấy thông tin người dùng từ access token
        $user = $request->user();

        if ($user) {
            return response()->json([
                'status' => 200,
                'message' => 'User retrieved successfully',
                'data' => $user,
            ]);
        }

        return response()->json([
            'status' => 401,
            'message' => 'Unauthenticated',
        ], 401);
    }
}