<?php

namespace Modules\Form\Entities;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Starter\Entities\Category;

class FormWidget extends Model
{

    protected $casts = [
        'props' => 'array'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
