<?php

namespace App\Models;

use App\Http\Controllers\WalletController;
use App\Http\Controllers\WalletTransactionController;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    // توا اهني في model 
    // بنحط جميع الحقول الي مجودة في الجدول متعي لكن المفاتيح الاجنبية مش ضروري انديرهم
    protected $fillable=[
        'balance',
        'user_id',
        'project_id',
        
    ];

    // بالاضافة الي بناء العلاقات 
    
    public function user(){
        return $this->belongsTo(User::class);
    }
    // توا منطقيا المحفظة عندها اكتر من عملية 
    // ف الكود هدا ايقول عندي علاقة عديدة 
    public function walletTransactions(){
        return $this->hasMany(WalletTransactionController::class);
    }
    // علاقة واحد لواحد بين المحفظة و المشروع
    public function project(){
        return $this->belongsTo(Project::class);
    }

}
