<?php

namespace Modules\Form\Entities;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FormAssociation extends Model
{
	public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }
}
