<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'transaction_date',
        'category',
        'description',
        'income',
        'expense',
        'memo',
        'parent_id',
        'item_name',
        'quantity',
        'unit_price',
        'image_path'
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'income' => 'decimal:0',
        'expense' => 'decimal:0',
        'unit_price' => 'decimal:0'
    ];

    /**
     * Get the parent transaction
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'parent_id');
    }

    /**
     * Get the child transactions
     */
    public function children(): HasMany
    {
        return $this->hasMany(Transaction::class, 'parent_id');
    }
}
