<?php

namespace Modules\Form\Entities;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Starter\Entities\BaseModel;

class FormValue extends BaseModel
{

	protected $model_name = "表单值";

	protected $casts = [
		'value' => 'array'
	];

	protected $accessors = [
		'value' => 'file|*'
	];

	public function filler(): Model|MorphTo
	{
		return $this->morphTo();
	}

	public function service(): Model|MorphTo
	{
		return $this->morphTo();
	}


	public function form(): BelongsTo
	{
		return $this->belongsTo(Form::class);
	}

	public function formItem(): BelongsTo
	{
		return $this->belongsTo(FormItem::class, 'form_item_id');
	}
}
