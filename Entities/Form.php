<?php

namespace Modules\Form\Entities;


use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Starter\Entities\BaseModel;

class Form extends BaseModel
{

	protected $model_name = '动态表单';

	protected $casts = [
		'is_active' => 'boolean',
	];

	public function department(): BelongsTo
	{
		return $this->belongsTo(Department::class);
	}

	public function creator(): BelongsTo
	{
		return $this->belongsTo(User::class, 'creator_id');
	}

	public function items(): HasMany
	{
		return $this->hasMany(FormItem::class)->orderByDesc('sort_order');
	}

	public function associations(): HasMany
	{
		return $this->hasMany(FormAssociation::class);
	}
}
