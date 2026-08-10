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
        Schema::create('organization_members', function (Blueprint $table) {
             $table->id();
            
            // المستخدم المضاف
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // المنظمة التي سينتمي لها
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            // حالة العضوية (معلق، مقبول، مرفوض)
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            // دوره داخل هذه المنظمة (عضو عادي أو مشرف)
            $table->string('role', 20)->default('member');
            
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
        Schema::dropIfExists('organization_members');
    }
};
