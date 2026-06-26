<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('organization_users', function (Blueprint $table) {
             $table->id();
            
            // المستخدم المضاف
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // المنظمة التي سينتمي لها
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            
            // دوره داخل هذه المنظمة (عضو عادي أو مشرف)
            $table->enum('role', ['عضو', 'مشرف',"مدير مالي"])->default('عضو');
            
            // تاريخ الانضمام
            $table->timestamp('joined_at')->useCurrent();
            
            // منع إضافة نفس المستخدم لنفس المنظمة أكثر من مرة
            $table->unique(['organization_id', 'user_id']);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_users');
    }
};
