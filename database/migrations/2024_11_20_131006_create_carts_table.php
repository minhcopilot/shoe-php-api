<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCartsTable extends Migration
{
    public function up()
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');  // Liên kết với người dùng
            $table->foreignId('product_id')->constrained()->onDelete('cascade'); // Liên kết với sản phẩm
            $table->foreignId('size_id')->constrained()->onDelete('cascade'); // Liên kết với kích thước sản phẩm
            $table->integer('quantity')->default(1); // Số lượng sản phẩm
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('carts');
    }
};
