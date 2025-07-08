<?php

namespace Modules\Form\Entities;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormItem extends Model
{
	protected $casts = [
		'props' => 'array',
		'is_required' => 'boolean'
	];

	protected $hidden = ['created_at', 'updated_at'];

	public function form(): BelongsTo
	{
		return $this->belongsTo(Form::class);
	}
}
