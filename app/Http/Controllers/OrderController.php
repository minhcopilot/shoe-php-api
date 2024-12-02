<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Size;
use App\Models\Cart;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    // Create Order
    public function createOrder(Request $request)
    {
        // Validate dữ liệu đầu vào
        $request->validate([
            'payment_method' => 'required|string|in:COD,MOMO,Card', // Chỉ chấp nhận 3 phương thức thanh toán
            'address' => 'required|string',
            'status' => 'nullable|string|in:Chờ xác nhận,Đã xác nhận,Đang Giao hàng,Huỷ,Thành công',
            'sdt' => ['required', 'string', 'regex:/^\d{10}$/'],  // Validate số điện thoại (10 chữ số)
        ]);

        $user = Auth::user();

        // Lấy tất cả sản phẩm trong giỏ hàng của người dùng
        $cartItems = Cart::where('user_id', $user->id)->get();

        // Kiểm tra xem giỏ hàng có sản phẩm không
        if ($cartItems->isEmpty()) {
            return response()->json([
                'message' => 'Your cart is empty.',
                'error' => true,
            ], 400);
        }

        // Tính tổng giá trị đơn hàng
        $totalPrice = 0;
        foreach ($cartItems as $item) {
            $product = Product::findOrFail($item->product_id);
            $totalPrice += $product->price * $item->quantity;
        }

        // Xử lý trạng thái đơn hàng (mặc định là 'Chờ xác nhận')
        $status = $request->status ?? 'Chờ xác nhận';

        // Xử lý theo phương thức thanh toán
        if ($request->payment_method === 'MOMO') {
            // Logic xử lý MOMO nếu cần
            // Ví dụ: gọi API MOMO hoặc xác thực thanh toán
        }

        // Tạo đơn hàng mới
        $order = Order::create([
            'user_id' => $user->id,
            'total_price' => $totalPrice,
            'payment_method' => $request->payment_method,
            'status' => $status,
            'address' => $request->address,
            'sdt' => $request->sdt,
        ]);

        // Tạo OrderItem từ giỏ hàng
        foreach ($cartItems as $item) {
            $product = Product::findOrFail($item->product_id);
            $size = Size::findOrFail($item->size_id);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'size_id' => $item->size_id,
                'quantity' => $item->quantity,
                'price' => $product->price,
            ]);
        }

        // Sau khi tạo đơn hàng xong, xóa tất cả sản phẩm trong giỏ hàng
        Cart::where('user_id', $user->id)->delete();

        $this->updateUserCart($user);

        return response()->json([
            'message' => 'Order created successfully.',
            'order' => $order,
            'error' => false,
        ], 201);
    }

    public function updateOrderStatus(Request $request, $orderId)
    {
        // Lấy user hiện tại
        $user = Auth::user();
    
        // Lấy đơn hàng theo `orderId`, kiểm tra quyền truy cập
        $order = Order::findOrFail($orderId);
        if (!$user->is_admin && $order->user_id !== $user->id) {
            return response()->json([
                'message' => 'Bạn không có quyền cập nhật đơn hàng này.',
                'error' => true,
            ], 403);
        }
    
        // Xác thực dữ liệu đầu vào
        $validatedData = $request->validate([
            'address' => 'required_if:is_admin,false|string|max:255', // User cần cung cấp địa chỉ
            'sdt' => ['nullable', 'string', 'regex:/^\d{10}$/'],      // Số điện thoại (tùy chọn, phải là 10 chữ số)
            'status' => 'nullable|string|in:Chờ xác nhận,Đã xác nhận,Đang giao hàng,Huỷ,Thành công', // Trạng thái hợp lệ (tùy chọn)
        ]);
    
        // Kiểm tra quyền admin
        if ($user->is_admin) {
            // Admin được phép cập nhật trạng thái đơn hàng
            if ($request->has('status')) {
                $order->status = $request->status;
            }
        } else {
            // User chỉ được phép cập nhật địa chỉ và số điện thoại
            if ($request->has('address')) {
                $order->address = $validatedData['address'];
            }
            if ($request->has('sdt')) {
                $order->sdt = $validatedData['sdt'];
            }
        }
    
        // Lưu thay đổi vào cơ sở dữ liệu
        $order->save();
    
        // Trả về phản hồi JSON
        return response()->json([
            'message' => 'Đơn hàng đã được cập nhật thành công.',
            'order' => $order,
            'error' => false,
        ], 200);
    }
    


    
    public function getOrderHistory()
{
    $user = Auth::user();
    
    if ($user->is_admin) {
        // Admin có thể lấy tất cả đơn hàng
        $orders = Order::with('orderItems.product', 'orderItems.size')->get();
    } else {
        // User chỉ có thể lấy đơn hàng của chính mình
        $orders = Order::where('user_id', $user->id)
            ->with('orderItems.product', 'orderItems.size')
            ->get();
    }

    return response()->json([
        'orders' => $orders,
        'error' => false,
    ]);
}

    // Get Order Detail
    public function getOrderDetail($orderId)
    {
        $user = Auth::user();

        // Tìm đơn hàng theo ID và thuộc về người dùng hiện tại
        $order = Order::where('user_id', $user->id)
            ->with('orderItems.product', 'orderItems.size') // Tải thông tin sản phẩm và kích thước
            ->findOrFail($orderId); // Nếu không tìm thấy đơn hàng thì trả về lỗi 404

        // Chuyển đổi các orderItems để hiển thị thông tin chi tiết về sản phẩm và kích thước
        $order->orderItems->transform(function ($item) {
            $item->product_name = $item->product->name;   // Thêm tên sản phẩm vào OrderItem
            $item->size_name = $item->size->name;          // Thêm tên kích thước vào OrderItem
            $item->price = $item->product->price;          // Thêm giá sản phẩm vào OrderItem
            $item->image = $item->product->image;          // Thêm ảnh sản phẩm vào OrderItem
            $item->quantity = $item->quantity;
            $item->sdt = $item->sdt;
            return $item;
        });

        return response()->json([
            'order' => $order,
            'error' => false,
        ]);
    }

    // Delete Order
    public function deleteOrder($orderId)
    {
        $user = Auth::user();
        $order = Order::where('user_id', $user->id)->findOrFail($orderId);

        // Xóa đơn hàng và các mục liên quan
        $order->delete();

        return response()->json([
            'message' => 'Order deleted successfully.',
            'error' => false,
        ]);
    }

    // Search Orders by Status
    public function searchOrders(Request $request)
{
    // Xác thực dữ liệu đầu vào
    $request->validate([
        'status' => 'required|string',
    ]);

    // Lấy người dùng đang đăng nhập
    $user = Auth::user();

    // Tìm kiếm đơn hàng của người dùng theo trạng thái
    $orders = Order::where('user_id', $user->id)
        ->where('status', $request->status)
        ->with('orderItems.product', 'orderItems.size')
        ->get();

    // Trả về kết quả
    return response()->json([
        'orders' => $orders,
        'error' => false,
    ]);
}


    // Update User Cart
    private function updateUserCart(User $user)
    {
        // Cập nhật giỏ hàng trong thông tin người dùng
        $user->update(['cart' => []]);
    }
}
